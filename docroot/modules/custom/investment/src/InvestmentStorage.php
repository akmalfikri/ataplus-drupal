<?php

namespace Drupal\investment;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class InvestmentStorage extends SqlContentEntityStorage implements InvestmentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(InvestmentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {investment_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {investment_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(InvestmentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {investment_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('investment_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
