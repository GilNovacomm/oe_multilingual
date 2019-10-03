<?php

namespace Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LanguageNegotiationUrlSuffix for identifying language via URL suffix.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix::METHOD_ID,
 *   types = {
 *     \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *     \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *     \Drupal\Core\Language\LanguageInterface::TYPE_URL
 *   },
 *   weight = -10,
 *   name = @Translation("URL suffix"),
 *   description = @Translation("Language from the URL (Path suffix)."),
 *   config_route_name = "oe_multilingual_url_suffix.negotiation_url_suffix"
 * )
 */
class LanguageNegotiationUrlSuffix extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'oe-multilingual-url-suffix-negotiation-method';

  /**
   * The suffix delimiter.
   */
  const SUFFIX_DELIMITER = '_';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    $config = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
    if ($request && $this->languageManager && $config) {
      $request_path = urldecode(trim($request->getPathInfo(), '/'));
      $parts = explode(static::SUFFIX_DELIMITER, $request_path);
      $suffix = array_pop($parts);

      // Search suffix within added languages.
      $negotiated_language = FALSE;
      foreach ($this->languageManager->getLanguages() as $language) {
        if (isset($config[$language->getId()]) && $config[$language->getId()] == $suffix) {
          $negotiated_language = $language;
          break;
        }
      }

      if ($negotiated_language) {
        $langcode = $negotiated_language->getId();
      }
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $url_suffixes = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
    if (!empty($url_suffixes) && is_array($url_suffixes)) {

      // Split the path by the defined delimiter.
      $parts = explode(static::SUFFIX_DELIMITER, trim($path, '/'));

      // Suffix should be the last part on the path.
      $suffix = array_pop($parts);

      // If the suffix is one of the configured language suffix, rebuild the
      // path to remove it.
      if (array_search($suffix, $url_suffixes)) {
        $path = '/' . implode(static::SUFFIX_DELIMITER, $parts);
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $languages = array_flip(array_keys($this->languageManager->getLanguages()));
    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }

    // Append suffix to path.
    $config = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
    if (isset($config[$options['language']->getId()])) {
      $path .= static::SUFFIX_DELIMITER . $config[$options['language']->getId()];
    }

    if ($bubbleable_metadata) {
      $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
    }

    return $path;
  }

}
