<?php

namespace Drupal\bulk_user_registration\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for contentimport routes.
 */
class UserImportController extends ControllerBase {

  /**
   * Get All Content types.
   */
  public static function getAllUserRoleTypes() {
    $userRoleName = user_role_names();

    return $userRoleName;
  }

}
