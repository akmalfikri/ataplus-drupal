<?php

namespace Drupal\scn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SCNSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scn_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'scn.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scn.settings');
    $roles = $config->get('scn_roles');

    $form = [];
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Send mail to'),
    ];
    $form['fieldset']['scn_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('admin'),
      '#default_value' => $config->get('scn_admin'),
      '#description' => $this->t('Send mail to user with uid=1'),
    ];
    $form['fieldset']['scn_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($roles) ? $roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
      '#description' => $this->t('Send mail to users with selected roles'),
    ];
    $form['fieldset']['scn_maillist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom mail list'),
      '#default_value' => $config->get('scn_maillist'),
      '#description' => $this->t('Send mail to non-registered users. Delimiter - comma'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('scn.settings')
      ->set('scn_admin', $values['scn_admin'])
      ->set('scn_roles', $values['scn_roles'])
      ->set('scn_maillist', $values['scn_maillist'])
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
