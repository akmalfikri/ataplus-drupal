<?php


namespace Drupal\user_default_page;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;

/**
 * Provides a listing of User default page entities.
 */
class UserDefaultPageConfigEntityListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['roles'] = $this->t('Roles');
    $header['users'] = $this->t('User Id(s)');
    $header['login_path'] = $this->t('Login Path');
    $header['logout_path'] = $this->t('Logout Path');
    $header['login_message'] = $this->t('Login Message');
    $header['logout_message'] = $this->t('Logout Message');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $user_values = $entity->get_users();
    $uids = explode(',',$user_values);
    $default_users = User::loadMultiple($uids);
    $default_value = EntityAutocomplete::getEntityLabels($default_users);
    $row['label'] = $entity->label();
    $row['roles'] = implode(',',$entity->get_user_roles());
    $row['users'] = $default_value;
    $row['login_path'] = $entity->get_login_redirect();
    $row['logout_path'] = $entity->get_logout_redirect();
    $row['login_message'] = $entity->get_login_redirect_message();
    $row['logout_message'] = $entity->get_logout_redirect_message();
    return $row + parent::buildRow($entity);
  }

}
