<?php

namespace Drupal\investment;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\investment\Entity\InvestmentInterface;

/**
 * Defines the storage handler class for Investment entities.
 *
 * This extends the base storage class, adding required special handling for
 * Investment entities.
 *
 * @ingroup investment
 */
interface InvestmentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Investment revision IDs for a specific Investment.
   *
   * @param \Drupal\investment\Entity\InvestmentInterface $entity
   *   The Investment entity.
   *
   * @return int[]
   *   Investment revision IDs (in ascending order).
   */
  public function revisionIds(InvestmentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Investment author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Investment revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\investment\Entity\InvestmentInterface $entity
   *   The Investment entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(InvestmentInterface $entity);

  /**
   * Unsets the language for all Investment with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
