<?php

namespace Drupal\comment_notify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Unsubscribe form for Comment Notify.
 */
class CommentNotifyUnsubscribe extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_notify_unsubscribe';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['comment_notify_unsubscribe'] = [];

    $form['comment_notify_unsubscribe']['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email to unsubscribe'),
      '#description' => $this->t('All comment notification requests associated with this email will be revoked.'),
      '#required' => TRUE,
    ];
    $form['comment_notify_unsubscribe']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Unsubscribe this e-mail'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'comment_notify', 'comment_notify');
    $email = trim($form_state->getValue(['email']));
    $comments = comment_notify_unsubscribe_by_email($email);
    // Update the admin about the state of the subscription.
    if ($comments == 0) {
      drupal_set_message($this->t("There were no active comment notifications for that email."));
    }
    else {
      drupal_set_message($this->formatPlural($comments, "Email unsubscribed from 1 comment notification.", "Email unsubscribed from @count comment notifications."));
    }
  }

}
