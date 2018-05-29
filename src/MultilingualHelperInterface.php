<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for multilingual helper service.
 */
interface MultilingualHelperInterface {

  /**
   * Extracts an entity from the current route.
   *
   * @return null|\Drupal\Core\Entity\EntityInterface
   *   Returns the entity or null if no entity was found.
   */
  public function getEntityFromCurrentRoute(): ?EntityInterface;

  /**
   * Returns the entity translation for the current language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose translation will be returned.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity translation.
   */
  public function getCurrentLanguageEntityTranslation(EntityInterface $entity): EntityInterface;

  /**
   * Returns the list of language names in their native translations.
   *
   * @return array
   *   An array of language codes and language names.
   */
  public function getLanguageNameList(): array;

}
