<?php

namespace Drupal\invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the access control handler for invite routes.
 */
class InviteAccessController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function withdrawInviteAccess(AccountInterface $account) {
    $badge_admin = $account->hasPermission('administer invite settings');
    if ($badge_admin) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\invite\InviteInterface $invite */
      $invite_from_url = \Drupal::routeMatch()->getParameter('invite');
      return AccessResult::allowedIf($account->id() && $account->id() == $invite_from_url->getOwnerId() && $account->hasPermission('resend own invitations'))
        ->cachePerPermissions()
        ->cachePerUser();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resendInviteAccess(AccountInterface $account) {
    $invite_admin = $account->hasPermission('administer invite settings');
    if ($invite_admin) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\invite\InviteInterface $invite */
      $invite_from_url = \Drupal::routeMatch()->getParameter('invite');
      return AccessResult::allowedIf($account->id() && $account->id() == $invite_from_url->getOwnerId())
        ->cachePerPermissions()
        ->cachePerUser();
    }
  }

}
