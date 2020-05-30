<?php

namespace Drupal\user_default_page\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;


/**
 * Class UserDefaultPageConfigEntityForm.
 *
 * @package Drupal\user_default_page\Form
 */
class UserDefaultPageConfigEntityForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    
    $user_default_page_config_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $user_default_page_config_entity->label(),
      '#description' => $this->t("Label for the User default page."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_default_page_config_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_default_page\Entity\UserDefaultPageConfigEntity::load',
      ],
      '#disabled' => !$user_default_page_config_entity->isNew(),
    ];
    $form['roles_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('User / Role'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $roles = ['' => '-Select-'];
    foreach (user_roles(TRUE) as $role) {
      $roles[$role->id()] = $role->label();
    }
    $form['roles_fieldset']['user_roles'] = [
      '#title' => $this->t('User Roles'),
      '#type' => 'select',
      '#description' => $this->t("Select user roles"),
      '#options' => $roles,
      '#default_value' => $user_default_page_config_entity->get_user_roles(),
      '#multiple' => TRUE,
    ];
    $form['roles_fieldset']['markup'] = [
      '#markup' => '<b>'.$this->t('Select Role or User or both.').'</b>',
    ];
    $user_values = $user_default_page_config_entity->get_users();
    $uids = explode(',',$user_values);
    $default_users = User::loadMultiple($uids);
    $form['roles_fieldset']['users'] = [
      '#type' => 'entity_autocomplete',
	  '#target_type' => 'user',
	  '#selection_settings' => ['include_anonymous' => FALSE],
	  '#title' => $this->t('Select User'),
	  '#description' => $this->t('Type Username here. Add multiple users as comma separated.'),
	  '#tags' => TRUE,
	  '#default_value' => $default_users,
    ];
    $form['login_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Login'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['login_fieldset']['login_redirect'] = [
      '#title' => $this->t('Redirect to URL'),
      '#type' => 'textfield',
      '#size' => 64,
      '#description' => $this->t("Enter the internal path."),
      '#default_value' => $user_default_page_config_entity->get_login_redirect(),
    ];
    $form['login_fieldset']['login_redirect_message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#description' => $this->t("Enter the message to be displayed."),
      '#default_value' => $user_default_page_config_entity->get_login_redirect_message(),
    ];
    $form['logout_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Logout'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['logout_fieldset']['logout_redirect'] = [
      '#title' => $this->t('Redirect to URL'),
      '#type' => 'textfield',
      '#size' => 64,
      '#description' => $this->t("Enter the internal path."),
      '#default_value' => $user_default_page_config_entity->get_logout_redirect(),
    ];
    $form['logout_fieldset']['logout_redirect_message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#description' => $this->t("Enter the message to be displayed."),
      '#default_value' => $user_default_page_config_entity->get_logout_redirect_message(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get Values.
    $values = $form_state->getValues();
    $form_id = $values['form_id'];
    $roles = $values['user_roles'];
    $users = $values['users'];
    if (($roles == NULL) && ($users == NULL)) {
      $form_state->setErrorByName('user_roles', $this->t("Select atleast one role / user"));
      $form_state->setErrorByName('users', $this->t(""));
    }
    if ($form_id != 'user_default_page_config_entity_edit_form') {
      // Load all entities belongs to "user_default_page_config_entity".
      $entities_load = \Drupal::entityTypeManager()->getStorage('user_default_page_config_entity')->loadMultiple();
      $user_roles = $values['user_roles'];
      $current_user=$users[0]['target_id'];
      // Check roles for any existence.
      foreach ($entities_load as $entity) {
        // Check users for any existence.
        $users_array = $entity->get_users();
        if(strstr($users_array,$current_user)){
          $form_state->setErrorByName('users', t(" User is already present"));
        }

        // Check roles for any existence.
        if ($entity->get_user_roles() ==  $user_roles && $user_roles == ' ') {
          global $base_url;
          $url = Url::fromUri($base_url.'/admin/structure/user_default_page_config_entity/'.$entity->id().'/edit');
          $internal_link = \Drupal::l(t('edit'), $url);
          $form_state->setErrorByName('user_roles', $this->t("The Role <b>'@user_roles'</b> is already present in @label. You can @edit here", array('@user_roles' => $user_roles,'@label' => $entity->get('label') ,'@edit' => $internal_link)));
        }
      }
//      print '<pre>';print_r($users_array);exit;
    }
    if (!\Drupal::service('path.validator')->isValid($form_state->getValue('logout_redirect'))) {
      $form_state->setErrorByName('redirect_to', $this->t("The Logout redirect path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $form_state->getValue('logout_redirect'))));
    }
    if (!\Drupal::service('path.validator')->isValid($form_state->getValue('login_redirect'))) {
      $form_state->setErrorByName('redirect_to', $this->t("The Login redirect path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $form_state->getValue('login_redirect'))));
    }
    $login_redirect = $values['login_redirect'];
    $login_redirect_message = $values['login_redirect_message'];
    $logout_redirect = $values['logout_redirect'];
    $logout_redirect_message = $values['logout_redirect_message'];
    if (($login_redirect == NULL) && ($logout_redirect == NULL)) {
      $form_state->setErrorByName('login_redirect', $this->t("Fill Login / Logout Redirection path(s)"));
      $form_state->setErrorByName('logout_redirect', $this->t("Fill Login / Logout Redirection path(s)"));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    //Get User input values.
    $input = $form_state->getUserInput();
    $user_default_page_config_entity = $this->entity;
    $user_input = $input['users'];
    if (!empty($user_input)) {
      $uids = explode(',', $user_input);
      $users_array = '';
      foreach ($uids as $uid) {
        $user_uids = preg_match('#\((.*?)\)#', $uid, $match);
        $users_array .= $match[1] . ',';
      }
      $user_default_page_config_entity->setUsers($users_array);
    }
    $status = $user_default_page_config_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(
          $this->t(
            'Created the %label User default page.',
            [
              '%label' => $user_default_page_config_entity->label(),
            ]
          )
        );
        break;

      default:
        drupal_set_message(
          $this->t(
            'Saved the %label User default page.',
            [
              '%label' => $user_default_page_config_entity->label(),
            ]
          )
        );
    }
    $form_state->setRedirectUrl($user_default_page_config_entity->urlInfo('collection'));
  }

}
