<?php

namespace Drupal\investment;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Investment entity.
 *
 * @see \Drupal\investment\Entity\Investment.
 */
class InvestmentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\investment\Entity\InvestmentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished investment entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published investment entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit investment entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete investment entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add investment entities');
  }

}
