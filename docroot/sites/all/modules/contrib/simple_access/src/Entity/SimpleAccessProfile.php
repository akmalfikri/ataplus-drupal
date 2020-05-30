<?php
/**
 * @file
 *
 * Provides Drupal\simple_access\Entity\SimpleAccessProfile.
 */

namespace Drupal\simple_access\Entity;


use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\simple_access\SimpleAccessProfileInterface;

/**
 * Defines SimpleAccessProfile config entity.
 *
 * @ConfigEntityType(
 *   id = "simple_access_profile",
 *   label = @Translation("Access profile"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\simple_access\Controller\SimpleAccessProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_access\Form\SimpleAccessProfileAddForm",
 *       "edit" = "Drupal\simple_access\Form\SimpleAccessProfileEditForm",
 *       "delete" = "Drupal\simple_access\Form\SimpleAccessProfileDeleteForm",
 *     }
 *   },
 *   config_prefix = "profile",
 *   admin_permission = "manage simple access",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/simple-access/profiles/{simple_access_group}/edit",
 *     "delete-form" = "/admin/config/content/simple-access/profiles/{simple_access_group}/delete"
 *   }
 * )
 */
class SimpleAccessProfile extends ConfigEntityBase implements SimpleAccessProfileInterface {

  /**
   * Profile Id
   */
  public $id;

  /**
   * Profile Label
   */
  public $label;

  /**
   * Profile Weight
   */
  public $weight;

  /**
   * Profile access
   */
  public $access;

  /**
   * @return mixed
   */
  public function getAccess() {
    return $this->access;
  }

  /**
   * @param mixed $access
   */
  public function setAccess($access) {
    $this->access = $access;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Remove all records relating to this access group.
    db_delete('simple_access_node_profile')
      ->condition('pid', $this->id())
      ->execute();

    parent::delete();
  }

  /**
   * @param \Drupal\user\UserInterface|NULL $account
   * @param $op
   *
   * @return array
   */
  public function buildGrant(AccountProxyInterface $account = NULL, $op) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    foreach (array_filter($this->access) as $gid => $access) {
      $group = SimpleAccessGroup::load($gid);
      if (isset($access[$op]) && $access[$op] && $group->buildGrant($account, $op)) {
        return ['simple_access_profile:' . $this->id() => ['0']];
      }
    }
  }
}