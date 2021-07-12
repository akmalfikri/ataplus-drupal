<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class controller.
 */
class SpamMasterProtectionForm extends ConfigFormBase {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_protection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Default settings.
    $config = $this->config('spammaster.settings_protection');

    $form['protection_header'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('<h3>Protection Tools</h3>'),
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
    ];

    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Block Message'),
      '#group' => 'protection_header',
    ];

    // Insert license key field.
    $form['message']['block_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Change block message:'),
      '#default_value' => $config->get('spammaster.block_message'),
      '#description' => $this->t('Message to display to blocked spam users who are not allowed to register, contact, or comment in your Drupal site. Keep it short.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];

    // Insert basic tools table inside tree.
    $form['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Tools'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];

    $form['basic']['table_1'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => $this->t('Activate individual Basic Tools to implement Spam Master across your site.'), 'colspan' => 4],
      ],
    ];
    $form['basic']['table_1']['addrow']['basic_firewall'] = [
      '#type' => 'select',
      '#title' => $this->t('Firewall Scan'),
      '#options' => [
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_firewall'),
      '#description' => $this->t('Always <em>Yes</em>. Firewall scan implemented across your site. Greatly reduces server resources like CPU and Memory.'),
    ];
    $form['basic']['table_1']['addrow']['basic_registration'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration Scan'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_registration'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like the Registraion Scan for new registration attempts. Applies to registration form.'),
    ];
    $form['basic']['table_1']['addrow']['basic_comment'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment Scan'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_comment'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like the Comment Scan for new comment attempts. Applies to comment form.'),
    ];
    $form['basic']['table_1']['addrow']['basic_contact'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Scan'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_contact'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like the Contact Scan to be display on the contact form.'),
    ];

    // Insert extra tools table inside tree.
    $form['extra'] = [
      '#type' => 'details',
      '#title' => $this->t('Extra Tools'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];
    // Insert extra tools re-captcha table.
    $form['extra']['table_2'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => $this->t('Google re-Captcha V2')],
      ],
    ];
    $form['extra']['table_2']['addrow']['extra_recaptcha'] = [
      '#type' => 'select',
      '#title' => $this->t('Google re-Captcha V2'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like Google re-Captcha V2 implemented across your site forms.'),
    ];
    $form['extra']['table_3'] = [
      '#type' => 'table',
      '#header' => [],
    ];
    // Insert addrow re-captcha api key.
    $form['extra']['table_3']['addrow']['extra_recaptcha_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google re-Captcha API Site Key:'),
      '#default_value' => $config->get('spammaster.extra_recaptcha_api_key'),
      '#description' => $this->t('Insert your Google re-Captcha api key.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];
    // Insert addrow re-captcha secrete key.
    $form['extra']['table_3']['addrow']['extra_recaptcha_api_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google re-Captcha API Secret Key:'),
      '#default_value' => $config->get('spammaster.extra_recaptcha_api_secret_key'),
      '#description' => $this->t('Insert your Google re-Captcha api secret key.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];
    $form['extra']['table_4'] = [
      '#type' => 'table',
      '#header' => [],
    ];
    $form['extra']['table_4']['addrow']['extra_recaptcha_login'] = [
      '#type' => 'select',
      '#title' => $this->t('re-Captcha on Login Form'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_login'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Login Form.'),
    ];
    $form['extra']['table_4']['addrow']['extra_recaptcha_registration'] = [
      '#type' => 'select',
      '#title' => $this->t('re-Captcha on Registration Form'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_registration'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Registration Form.'),
    ];
    $form['extra']['table_4']['addrow']['extra_recaptcha_comment'] = [
      '#type' => 'select',
      '#title' => $this->t('re-Captcha on Comment Form'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_comment'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Comment Form.'),
    ];
    $form['extra']['table_4']['addrow']['extra_recaptcha_contact'] = [
      '#type' => 'select',
      '#title' => $this->t('re-Captcha on Contact Form'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_contact'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Contact Form.'),
    ];
    // Insert extra tools honeypot table.
    $form['extra']['table_5'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => $this->t('Honeypot')],
      ],
    ];
    $form['extra']['table_5']['addrow']['extra_honeypot'] = [
      '#type' => 'select',
      '#title' => $this->t('Honeypot'),
      '#options' => [
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot'),
      '#description' => $this->t('Implementes two Honeypot fields across your site forms. Always <em>Yes</em> since it does not affect human interaction or server resources.'),
    ];
    $form['extra']['table_6'] = [
      '#type' => 'table',
      '#header' => [],
    ];
    $form['extra']['table_6']['addrow']['extra_honeypot_login'] = [
      '#type' => 'select',
      '#title' => $this->t('Honeypot on Login Form'),
      '#options' => [
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_login'),
      '#description' => $this->t('Honeypot on the Login Form.'),
    ];
    $form['extra']['table_6']['addrow']['extra_honeypot_registration'] = [
      '#type' => 'select',
      '#title' => $this->t('Honeypot on Registration Form'),
      '#options' => [
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_registration'),
      '#description' => $this->t('Honeypot on the Registration Form.'),
    ];
    $form['extra']['table_6']['addrow']['extra_honeypot_comment'] = [
      '#type' => 'select',
      '#title' => $this->t('Honeypot on Comment Form'),
      '#options' => [
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_comment'),
      '#description' => $this->t('Honeypot on the Comment Form.'),
    ];
    $form['extra']['table_6']['addrow']['extra_honeypot_contact'] = [
      '#type' => 'select',
      '#title' => $this->t('Honeypot on Contact Form'),
      '#options' => [
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_contact'),
      '#description' => $this->t('Honeypot on the Contact Form.'),
    ];
    // Insert extra tools scan characters table.
    $form['extra']['table_7'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => $this->t('Scan Characters'), 'colspan' => 4],
      ],
    ];
    $form['extra']['table_7']['addrow']['extra_contact_cyrillic'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Form Scan Cyrillic Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_contact_cyrillic'),
      '#description' => $this->t('Basic tools contact scan needs to be set to <em>Yes</em>.'),
    ];
    $form['extra']['table_7']['addrow']['extra_contact_asian'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Form Scan Asian Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_contact_asian'),
      '#description' => $this->t('Basic tools contact scan needs to be set to <em>Yes</em>.'),
    ];
    $form['extra']['table_7']['addrow']['extra_contact_arabic'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Form Scan Arabic Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_contact_arabic'),
      '#description' => $this->t('Basic tools contact scan needs to be set to <em>Yes</em>.'),
    ];
    $form['extra']['table_7']['addrow']['extra_contact_spam'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Form Scan Spam Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_contact_spam'),
      '#description' => $this->t('Basic tools contact scan needs to be set to <em>Yes</em>.'),
    ];

    $form['extra']['table_7']['addrow1']['extra_comment_cyrillic'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment Form Scan Cyrillic Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_comment_cyrillic'),
      '#description' => $this->t('Basic tools comment scan needs to be set to <em>Yes</em>.'),
    ];
    $form['extra']['table_7']['addrow1']['extra_comment_asian'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment Form Scan Asian Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_comment_asian'),
      '#description' => $this->t('Basic tools comment scan needs to be set to <em>Yes</em>.'),
    ];
    $form['extra']['table_7']['addrow1']['extra_comment_arabic'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment Form Scan Arabic Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_comment_arabic'),
      '#description' => $this->t('Basic tools comment scan needs to be set to <em>Yes</em>.'),
    ];
    $form['extra']['table_7']['addrow1']['extra_comment_spam'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment Form Scan Spam Chars'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_comment_spam'),
      '#description' => $this->t('Basic tools comment scan needs to be set to <em>Yes</em>.'),
    ];

    // Insert signatures tools table inside tree.
    $form['signature'] = [
      '#type' => 'details',
      '#title' => $this->t('Signatures'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];
    $form['signature']['table_8'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => $this->t('Signatures are a huge deterrent against all forms of human spam.'), 'colspan' => 4],
      ],
    ];
    $form['signature']['table_8']['addrow']['signature_registration'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration Signature'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_registration'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the registration form.'),
    ];
    $form['signature']['table_8']['addrow']['signature_login'] = [
      '#type' => 'select',
      '#title' => $this->t('Login Signature'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_login'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the login form.'),
    ];
    $form['signature']['table_8']['addrow']['signature_comment'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment Signature'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_comment'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the comment form.'),
    ];
    $form['signature']['table_8']['addrow']['signature_contact'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Signature'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_contact'),
      '#description' => $this->t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the contact form.'),
    ];

    // Insert email tools table inside tree.
    $form['email'] = [
      '#type' => 'details',
      '#title' => $this->t('Emails & Reports'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];
    $form['email']['table_9'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('An extra watchful eye over your drupal website security. Emails and reports are sent to the email address found in your drupal Configuration, Basic Site Settings.'), 'colspan' => 4],
      ],
    ];
    $form['email']['table_9']['addrow']['email_alert_3'] = [
      '#type' => 'select',
      '#title' => $this->t('Alert 3 Warning Email'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_alert_3'),
      '#description' => $this->t('Set this to <em>Yes</em> to receive the alert 3 email. Only sent if your website has reached or is at a dangerous level.'),
    ];
    $form['email']['table_9']['addrow']['email_daily_report'] = [
      '#type' => 'select',
      '#title' => $this->t('Daily Report Email'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_daily_report'),
      '#description' => $this->t('Set this to <em>Yes</em> to receive the daily report for normal alert levels and spam probability percentage.'),
    ];
    $form['email']['table_9']['addrow']['email_weekly_report'] = [
      '#type' => 'select',
      '#title' => $this->t('Weekly Report Email'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_weekly_report'),
      '#description' => $this->t('Set this to <em>Yes</em> to receive the Weekly detailed email report.'),
    ];
    $form['email']['table_9']['addrow']['email_improve'] = [
      '#type' => 'select',
      '#title' => $this->t('Help us improve Spam Master'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_improve'),
      '#description' => $this->t('Set this to <em>Yes</em> to help us improve Spam Master with weekly statistical data, same as your weekly report.'),
    ];

    // Insert clean-up tools table inside tree.
    $form['cleanup'] = [
      '#type' => 'details',
      '#title' => $this->t('Clean-Up'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];
    $form['cleanup']['table_10'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Clean-up allows to automatically delete logs via weekly cron but impacts statistics. Insert a month number, default is 3. Use 0 to never delete specific log types.'), 'colspan' => 5],
      ],
    ];
    $form['cleanup']['table_10']['addrow']['cleanup_system'] = [
      '#type' => 'number',
      '#title' => $this->t('System Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_system'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow']['cleanup_cron'] = [
      '#type' => 'number',
      '#title' => $this->t('Cron Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_cron'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow']['cleanup_mail'] = [
      '#type' => 'number',
      '#title' => $this->t('Mail Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_mail'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow']['cleanup_whitelist'] = [
      '#type' => 'number',
      '#title' => $this->t('Whitelist Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_whitelist'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow']['cleanup_firewall'] = [
      '#type' => 'number',
      '#title' => $this->t('Firewall Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_firewall'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow1']['cleanup_registration'] = [
      '#type' => 'number',
      '#title' => $this->t('Registration Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_registration'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow1']['cleanup_comment'] = [
      '#type' => 'number',
      '#title' => $this->t('Comment Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_comment'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow1']['cleanup_contact'] = [
      '#type' => 'number',
      '#title' => $this->t('Contact Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_contact'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow1']['cleanup_honeypot'] = [
      '#type' => 'number',
      '#title' => $this->t('Honeypot Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_honeypot'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['cleanup']['table_10']['addrow1']['cleanup_recaptcha'] = [
      '#type' => 'number',
      '#title' => $this->t('reCaptacha Logs:'),
      '#default_value' => $config->get('spammaster.cleanup_recaptcha'),
      '#description' => $this->t('Insert retention time (minimum 1, infinite 0).'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('block_message'))) {
      $form_state->setErrorByName('protection_header', $this->t('Field can not be empty. Please insert your block message.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for block message. Reason: Empty.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow']['cleanup_system']) or $form_state->getValue('table_10')['addrow']['cleanup_system'] <= '0') && $form_state->getValue('table_10')['addrow']['cleanup_system'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('System Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for System Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow']['cleanup_cron']) or $form_state->getValue('table_10')['addrow']['cleanup_cron'] <= '0') && $form_state->getValue('table_10')['addrow']['cleanup_cron'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Cron Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Cron Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow']['cleanup_mail']) or $form_state->getValue('table_10')['addrow']['cleanup_mail'] <= '0') && $form_state->getValue('table_10')['addrow']['cleanup_mail'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Mail Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Mail Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow']['cleanup_whitelist']) or $form_state->getValue('table_10')['addrow']['cleanup_whitelist'] <= '0') && $form_state->getValue('table_10')['addrow']['cleanup_whitelist'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Whitelist Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Whitelist Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow']['cleanup_firewall']) or $form_state->getValue('table_10')['addrow']['cleanup_firewall'] <= '0') && $form_state->getValue('table_10')['addrow']['cleanup_firewall'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Firewall Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Firewall Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow1']['cleanup_registration']) or $form_state->getValue('table_10')['addrow1']['cleanup_registration'] <= '0') && $form_state->getValue('table_10')['addrow1']['cleanup_registration'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Registration Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Registration Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow1']['cleanup_comment']) or $form_state->getValue('table_10')['addrow1']['cleanup_comment'] <= '0') && $form_state->getValue('table_10')['addrow1']['cleanup_comment'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Comment Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Comment Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow1']['cleanup_contact']) or $form_state->getValue('table_10')['addrow1']['cleanup_contact'] <= '0') && $form_state->getValue('table_10')['addrow1']['cleanup_contact'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Contact Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Contact Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow1']['cleanup_honeypot']) or $form_state->getValue('table_10')['addrow1']['cleanup_honeypot'] <= '0') && $form_state->getValue('table_10')['addrow1']['cleanup_honeypot'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('Honeypot Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for Honeypot Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
    if ((empty($form_state->getValue('table_10')['addrow1']['cleanup_recaptcha']) or $form_state->getValue('table_10')['addrow1']['cleanup_recaptcha'] <= '0') && $form_state->getValue('table_10')['addrow1']['cleanup_recaptcha'] != '0') {
      $form_state->setErrorByName('protection_header', $this->t('reCaptcha Logs field can not be empty and minimum is 1. For infinite you can insert 0.'));
      // Log message.
      $spammaster_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster',
        'spamvalue' => 'Spam Master: Protection Tools Validation Failed for reCaptcha Logs. Reason: Either empty field or inferior to 1.',
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spammaster.settings_protection');
    $config->set('spammaster.block_message', $form_state->getValue('block_message'));
    $config->set('spammaster.basic_registration', $form_state->getValue('table_1')['addrow']['basic_registration']);
    $config->set('spammaster.basic_comment', $form_state->getValue('table_1')['addrow']['basic_comment']);
    $config->set('spammaster.basic_contact', $form_state->getValue('table_1')['addrow']['basic_contact']);
    $config->set('spammaster.extra_recaptcha', $form_state->getValue('table_2')['addrow']['extra_recaptcha']);
    $config->set('spammaster.extra_recaptcha_api_key', $form_state->getValue('table_3')['addrow']['extra_recaptcha_api_key']);
    $config->set('spammaster.extra_recaptcha_api_secret_key', $form_state->getValue('table_3')['addrow']['extra_recaptcha_api_secret_key']);
    $config->set('spammaster.extra_recaptcha_login', $form_state->getValue('table_4')['addrow']['extra_recaptcha_login']);
    $config->set('spammaster.extra_recaptcha_registration', $form_state->getValue('table_4')['addrow']['extra_recaptcha_registration']);
    $config->set('spammaster.extra_recaptcha_comment', $form_state->getValue('table_4')['addrow']['extra_recaptcha_comment']);
    $config->set('spammaster.extra_recaptcha_contact', $form_state->getValue('table_4')['addrow']['extra_recaptcha_contact']);
    $config->set('spammaster.extra_honeypot', $form_state->getValue('table_5')['addrow']['extra_honeypot']);
    $config->set('spammaster.extra_honeypot_login', $form_state->getValue('table_6')['addrow']['extra_honeypot_login']);
    $config->set('spammaster.extra_honeypot_registration', $form_state->getValue('table_6')['addrow']['extra_honeypot_registration']);
    $config->set('spammaster.extra_honeypot_comment', $form_state->getValue('table_6')['addrow']['extra_honeypot_comment']);
    $config->set('spammaster.extra_honeypot_contact', $form_state->getValue('table_6')['addrow']['extra_honeypot_contact']);
    $config->set('spammaster.extra_contact_cyrillic', $form_state->getValue('table_7')['addrow']['extra_contact_cyrillic']);
    $config->set('spammaster.extra_contact_asian', $form_state->getValue('table_7')['addrow']['extra_contact_asian']);
    $config->set('spammaster.extra_contact_arabic', $form_state->getValue('table_7')['addrow']['extra_contact_arabic']);
    $config->set('spammaster.extra_contact_spam', $form_state->getValue('table_7')['addrow']['extra_contact_spam']);
    $config->set('spammaster.extra_comment_cyrillic', $form_state->getValue('table_7')['addrow1']['extra_comment_cyrillic']);
    $config->set('spammaster.extra_comment_asian', $form_state->getValue('table_7')['addrow1']['extra_comment_asian']);
    $config->set('spammaster.extra_comment_arabic', $form_state->getValue('table_7')['addrow1']['extra_comment_arabic']);
    $config->set('spammaster.extra_comment_spam', $form_state->getValue('table_7')['addrow1']['extra_comment_spam']);
    $config->set('spammaster.signature_registration', $form_state->getValue('table_8')['addrow']['signature_registration']);
    $config->set('spammaster.signature_login', $form_state->getValue('table_8')['addrow']['signature_login']);
    $config->set('spammaster.signature_comment', $form_state->getValue('table_8')['addrow']['signature_comment']);
    $config->set('spammaster.signature_contact', $form_state->getValue('table_8')['addrow']['signature_contact']);
    $config->set('spammaster.email_alert_3', $form_state->getValue('table_9')['addrow']['email_alert_3']);
    $config->set('spammaster.email_daily_report', $form_state->getValue('table_9')['addrow']['email_daily_report']);
    $config->set('spammaster.email_weekly_report', $form_state->getValue('table_9')['addrow']['email_weekly_report']);
    $config->set('spammaster.email_improve', $form_state->getValue('table_9')['addrow']['email_improve']);
    $config->set('spammaster.cleanup_system', $form_state->getValue('table_10')['addrow']['cleanup_system']);
    $config->set('spammaster.cleanup_cron', $form_state->getValue('table_10')['addrow']['cleanup_cron']);
    $config->set('spammaster.cleanup_mail', $form_state->getValue('table_10')['addrow']['cleanup_mail']);
    $config->set('spammaster.cleanup_whitelist', $form_state->getValue('table_10')['addrow']['cleanup_whitelist']);
    $config->set('spammaster.cleanup_firewall', $form_state->getValue('table_10')['addrow']['cleanup_firewall']);
    $config->set('spammaster.cleanup_registration', $form_state->getValue('table_10')['addrow1']['cleanup_registration']);
    $config->set('spammaster.cleanup_comment', $form_state->getValue('table_10')['addrow1']['cleanup_comment']);
    $config->set('spammaster.cleanup_contact', $form_state->getValue('table_10')['addrow1']['cleanup_contact']);
    $config->set('spammaster.cleanup_honeypot', $form_state->getValue('table_10')['addrow1']['cleanup_honeypot']);
    $config->set('spammaster.cleanup_recaptcha', $form_state->getValue('table_10')['addrow1']['cleanup_recaptcha']);
    $config->save();
    // Log message.
    $spammaster_date = date("Y-m-d H:i:s");
    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster',
      'spamvalue' => 'Spam Master: Protection Tools page successful save.',
    ])->execute();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings_protection',
    ];
  }

}
