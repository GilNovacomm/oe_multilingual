<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\Gettext;

/**
 * Creates the batches for importing the local translations.
 *
 * Local translations are interface translations that are "shipped" by an
 * extension by specifying the location of the strings inside its info file.
 */
class LocalTranslationsBatcher {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module extensions list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extensions list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The profile extensions list.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected $profileExtensionList;

  /**
   * LocalTranslationsBatcher constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extensions list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extensions list.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   *   The profile extensions list.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ThemeHandlerInterface $themeHandler, LanguageManagerInterface $languageManager, ModuleExtensionList $moduleExtensionList, ThemeExtensionList $themeExtensionList, ProfileExtensionList $profileExtensionList) {
    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
    $this->languageManager = $languageManager;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->profileExtensionList = $profileExtensionList;
  }

  /**
   * Creates and sets the batch for importing local translations.
   *
   * @param array $langcodes
   *   The optional langcodes to import in.
   *
   * @see \Drupal\locale\Form\TranslationStatusForm::submitForm()
   */
  public function createBatch(array $langcodes = []): void {
    $this->moduleHandler->loadInclude('locale', 'inc', 'locale.compare');
    $this->moduleHandler->loadInclude('locale', 'inc', 'locale.bulk');

    if (!$langcodes) {
      $languages = $this->languageManager->getLanguages();
      $langcodes = [];
      foreach ($languages as $language) {
        if ($language->getId() === 'en') {
          continue;
        }

        $langcodes[] = $language->getId();
      }
    }

    $extensions = $this->getExtensionsToTranslate();
    if (!$extensions || !$langcodes) {
      return;
    }

    // Build operations for local translation batch import.
    $operations = [];
    foreach ($extensions as $extension) {
      $operations[] = [
        [get_class($this), 'importProjectPoFiles'],
        [$extension, $langcodes],
      ];
    }

    $batch = [
      'operations' => $operations,
      'title' => t('Updating translations.'),
      'progress_message' => '',
      'error_message' => t('Error importing translation files'),
    ];

    batch_set($batch);
    // Update config translations.
    if ($batch = locale_config_batch_update_components([], $langcodes)) {
      batch_set($batch);
    }

  }

  /**
   * Creates an array of modules, themes and profiles to be translated.
   *
   * These are the ones which contain local translations.
   *
   * @return array
   *   The extensions.
   */
  protected function getExtensionsToTranslate(): array {
    $extensions = [];
    $all_extensions = array_merge($this->moduleExtensionList->getList(), $this->themeExtensionList->getList(), $this->profileExtensionList->getList());
    foreach ($all_extensions as $name => $module) {
      if (!isset($module->info['interface translation project'])) {
        continue;
      }

      // The module handler checks also profiles.
      if (!$this->moduleHandler->moduleExists($name) && !$this->themeHandler->themeExists($name)) {
        continue;
      }

      $extensions[] = $name;
    }

    return array_intersect_key(locale_translation_project_list(), array_combine($extensions, $extensions));
  }

  /**
   * Implements callback_batch_operation().
   *
   * Import po files of the project for allowed languages.
   */
  public static function importProjectPoFiles($extension, $langcodes, &$context): void {
    $files = file_scan_directory(
      \Drupal::service('file_system')->dirname($extension['info']['interface translation server pattern']),
      '/.*-(' . implode('|', $langcodes) . ')\.po/'
    );
    foreach ($files as $file) {
      preg_match('/(' . implode('|', $langcodes) . ')$/', $file->name, $matches);
      $file->langcode = $matches[0];
      Gettext::fileToDatabase($file, [
        'overwrite_options' => [
          'not_customized' => TRUE,
        ],
      ]);

      $context['message'] = t('Imported translation for %project (%langcode).', ['%project' => $extension['name'], '%langcode' => $file->langcode]);
    }
  }

}
