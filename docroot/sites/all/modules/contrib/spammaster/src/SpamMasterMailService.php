<?php

namespace Drupal\spammaster;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class service.
 */
class SpamMasterMailService {
  use StringTranslationTrait;

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
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The MailManager object.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, MessengerInterface $messenger, AccountProxyInterface $current_user, ConfigFactoryInterface $configFactory, StateInterface $state, MailManagerInterface $mailManager) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->configFactory = $configFactory;
    $this->state = $state;
    $this->mailManager = $mailManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterLicTrialCreation() {

    // Email key.
    $key = 'license_trial_create';
    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $this->state->get('spammaster.license_status');
    $spammaster_license_protection = $this->state->get('spammaster.license_protection');
    $to = $this->currentUser->getEmail();
    // Set date.
    $spammaster_date = date('Y-m-d H:i:s');
    if ($spammaster_status == 'VALID') {
      // Email Content.
      $spam_master_table_content = 'Congratulations, ' . $spammaster_site_name . ' is now protected by Spam Master against millions of threats.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Your License is: ' . $spammaster_license . '.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Protected Against: ' . number_format($spammaster_license_protection) . ' million threats.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Your free trial license expires in 7 days.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Enjoy,';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'TechGasp Team';
      $spam_master_table_content .= "\r\n";
      $module = 'spammaster';
      $params['message'] = $spam_master_table_content;
      $langcode = $this->currentUser->getPreferredLangcode();
      $send = TRUE;
      $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      $this->messenger->addMessage($this->t('Remember to visit Spam Master configuration page.'));

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: congratulations! trial license created.',
      ])->execute();

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-mail',
        'spamvalue' => 'Spam Master: mail trial license created sent to:' . $to,
      ])->execute();
    }
    else {
      $this->messenger->addError($this->t('Spam Master Trial license could not be created. License status is: @spammaster_status. Check Spam Master configuration page and read more about statuses.', ['@spammaster_status' => $spammaster_status]));

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: trial license not created, contains malfunction: ' . $spammaster_status,
      ])->execute();

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-mail',
        'spamvalue' => 'Spam Master: mail not sent, license contains malfunction:' . $spammaster_status,
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterLicExpired() {

    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $to = $site_settings->get('mail');
    $spammaster_type = $this->state->get('spammaster.type');
    // Set date.
    $spammaster_date = date('Y-m-d H:i:s');
    if ($spammaster_type == 'TRIAL') {
      // Email key.
      $key = 'license_trial_end';
      // Email Content.
      $spam_master_table_content = $spammaster_site_name . ' is no longer protected by Spam Master against millions of threats.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'If you enjoyed the protection you can quickly get a full license, it costs peanuts per year.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Go to Spam Master settings page and click get full license.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Thanks.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'TechGasp Team';
      $spam_master_table_content .= "\r\n";
      $module = 'spammaster';
      $params['message'] = $spam_master_table_content;
      $langcode = $this->currentUser->getPreferredLangcode();
      $send = TRUE;
      $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: trial license expired.',
      ])->execute();

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-mail',
        'spamvalue' => 'Spam Master: mail trial license expired sent To: ' . $to,
      ])->execute();
    }
    if ($spammaster_type == 'FULL') {
      // Email key.
      $key = 'license_full_end';
      // Email Content.
      $spam_master_table_content = $spammaster_site_name . ' is no longer protected by Spam Master against millions of threats.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Hope you have enjoyed 1 year of bombastic protection. You can quickly get another license and get protected again, it costs peanuts per year.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Go to Spam Master settings page and click get full license.';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'Thanks,';
      $spam_master_table_content .= "\r\n";
      $spam_master_table_content .= 'TechGasp Team';
      $spam_master_table_content .= "\r\n";
      $module = 'spammaster';
      $params['message'] = $spam_master_table_content;
      $langcode = $this->currentUser->getPreferredLangcode();
      $send = TRUE;
      $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: full license expired.',
      ])->execute();

      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-mail',
        'spamvalue' => 'Spam Master: mail full license expired sent To: ' . $to,
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterLicMalfunctions() {

    // Email key.
    $key = 'license_malfunction';
    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $to = $site_settings->get('mail');
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $this->state->get('spammaster.license_status');

    // Email Content.
    $spam_master_table_content = 'Warning, your ' . $spammaster_site_name . ' might not be 100% protected.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Your License: ' . $spammaster_license . ' status is: ' . $spammaster_status . '.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Some license status are easy to fix, example Malfunction 1 just means you need to update the module to the latest version and the status will automatically fix itself.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'All statuses are explained in our website documentation section and, in case of trouble get in touch with our support.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'TechGasp Team';
    $spam_master_table_content .= "\r\n";
    $module = 'spammaster';
    $params['message'] = $spam_master_table_content;
    $langcode = $this->currentUser->getPreferredLangcode();
    $send = TRUE;
    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    $spammaster_date = date('Y-m-d H:i:s');

    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-license',
      'spamvalue' => 'Spam Master: license malfunction detected.',
    ])->execute();

    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-mail',
      'spamvalue' => 'Spam Master: mail license malfunction sent To: ' . $to,
    ])->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterLicAlertLevel3() {

    // Email key.
    $key = 'lic_alert_level_3';
    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $to = $site_settings->get('mail');
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $this->state->get('spammaster.license_status');
    $spammaster_license_protection = $this->state->get('spammaster.license_protection');

    // Email Content.
    $spam_master_table_content = 'Warning!!! Spam Master Alert 3 detected for ' . $spammaster_site_name . '.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Your License: ' . $spammaster_license . ' status is: ' . $spammaster_status . ' and you are protected against: ' . number_format($spammaster_license_protection) . ' threats.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'The daily Alert 3 email will automatically stop when your website alert level drops to safer levels.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'All alert levels are explained in our website documentation section and, in case of trouble get in touch with our support.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'TechGasp Team';
    $spam_master_table_content .= "\r\n";
    $module = 'spammaster';
    $params['message'] = $spam_master_table_content;
    $langcode = $this->currentUser->getPreferredLangcode();
    $send = TRUE;
    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    $spammaster_date = date('Y-m-d H:i:s');

    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-license',
      'spamvalue' => 'Spam Master: alert level 3 detected.',
    ])->execute();

    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-mail',
      'spamvalue' => 'Spam Master: mail alert level 3 sent To: ' . $to,
    ])->execute();
  }

  /**
   * The Mail function for Daily Report.
   */
  public function spamMasterMailDailyReport() {

    // Email key.
    $key = 'mail_daily_report';
    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $to = $site_settings->get('mail');
    $response_key = $this->state->get('spammaster.license_status');
    $spam_master_warning = FALSE;
    $spam_master_warning_signature = FALSE;
    $spam_master_alert_level_deconstructed = FALSE;
    $spam_master_total_block_count_result = FALSE;
    if (!isset($response_key) || empty($response_key) || $response_key == 'INACTIVE') {
      $spam_master_warning = 'Warning: Spam Master is Inactive, you are not protected.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'VALID') {
      $spam_master_warning = 'Your license status is Valid & Online.';
      $spam_master_warning_signature = 'All is good.';
    }
    if ($response_key == 'MALFUNCTION_1') {
      $spam_master_warning = 'Warnings: Malfunction 1, please update Spam Master to the latest version.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'MALFUNCTION_2') {
      $spam_master_warning = 'Warnings: Malfunction 2, urgently update Spam Master, your installed version is extremely old.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'MALFUNCTION_3') {
      $spam_master_warning = 'Warning: Malfunction 3, get in touch with TechGasp support..';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'EXPIRED') {
      $spam_master_warning = 'Warning: your license is EXPIRED and you are not protected.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    $spammaster_license_protection = $this->state->get('spammaster.license_protection');
    $spammaster_license_probability = $this->state->get('spammaster.license_probability');
    $spammaster_license_alert_level = $this->state->get('spammaster.license_alert_level');
    if (!isset($spammaster_license_alert_level) || empty($spammaster_license_alert_level)) {
      $spam_master_alert_level_deconstructed = 'empty';
    }
    if ($spammaster_license_alert_level == 'ALERT_0') {
      $spam_master_alert_level_deconstructed = '0';
    }
    if ($spammaster_license_alert_level == 'ALERT_1') {
      $spam_master_alert_level_deconstructed = '1';
    }
    if ($spammaster_license_alert_level == 'ALERT_2') {
      $spam_master_alert_level_deconstructed = '2';
    }
    if ($spammaster_license_alert_level == 'ALERT_3') {
      $spam_master_alert_level_deconstructed = '3';
    }
    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    if (!isset($spammaster_total_block_count) || empty($spammaster_total_block_count)) {
      $spam_master_total_block_count_result = 'empty';
    }
    if ($spammaster_total_block_count <= '10') {
      $spam_master_total_block_count_result = 'Firewall Total Triggers: good, less than 10';
    }
    if ($spammaster_total_block_count >= '11') {
      $spam_master_total_block_count_result = 'Firewall Total Triggers: ' . number_format($spammaster_total_block_count);
    }
    // Get count last 7 days of blocks from spammaster_keys.
    $time = date('Y-m-d H:i:s');
    $time_expires = date('Y-m-d H:i:s', strtotime($time . '-1 days'));
    $spammaster_spam_watch_query = $this->connection->select('spammaster_keys', 'u');
    $spammaster_spam_watch_query->fields('u', ['spamkey']);
    $spammaster_spam_watch_query->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_spam_watch_query->where('(spamkey = :registration OR spamkey = :comment OR spamkey = :contact OR spamkey = :firewall)', [
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      'contact' => 'spammaster-contact',
      ':firewall' => 'spammaster-firewall',
    ]);
    $spammaster_spam_watch_result = $spammaster_spam_watch_query->countQuery()->execute()->fetchField();
    if (empty($spammaster_spam_watch_result)) {
      $spam_master_block_count_result = 'Firewall Weekly Triggers: good, nothing to report';
    }
    else {
      $spam_master_block_count_result = 'Firewall Weekly Triggers: ' . number_format($spammaster_spam_watch_result);
    }
    // Email Content.
    $spam_master_table_content = 'Spam Master Daily Report for ' . $spammaster_site_name . '.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_warning;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Alert Level: ' . $spam_master_alert_level_deconstructed;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Probability: ' . $spammaster_license_probability . '%';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Protected Against: ' . number_format($spammaster_license_protection) . ' million threats';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_total_block_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_block_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_warning_signature;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'The daily report email can be turned off in Spam Master module settings page, Emails & Reporting section.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'TechGasp Team';
    $module = 'spammaster';
    $params['message'] = $spam_master_table_content;
    $langcode = $this->currentUser->getPreferredLangcode();
    $send = TRUE;
    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    // Log message.
    $spammaster_date = date('Y-m-d H:i:s');
    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-mail',
      'spamvalue' => 'Spam Master: mail daily sent To: ' . $to,
    ])->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterMailWeeklyReport() {

    // Email key.
    $key = 'mail_weekly_report';
    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $to = $site_settings->get('mail');
    $response_key = $this->state->get('spammaster.license_status');
    $spam_master_warning = FALSE;
    $spam_master_warning_signature = FALSE;
    $spam_master_alert_level_deconstructed = FALSE;
    $spam_master_total_block_count_result = FALSE;
    if (!isset($response_key) || empty($response_key) || $response_key == 'INACTIVE') {
      $spam_master_warning = 'Warning: Spam Master is Inactive, you are not protected.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'VALID') {
      $spam_master_warning = 'Your license status is Valid & Online.';
      $spam_master_warning_signature = 'All is good.';
    }
    if ($response_key == 'MALFUNCTION_1') {
      $spam_master_warning = 'Warnings: Malfunction 1, please update Spam Master to the latest version.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'MALFUNCTION_2') {
      $spam_master_warning = 'Warnings: Malfunction 2, urgently update Spam Master, your installed version is extremely old.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'MALFUNCTION_3') {
      $spam_master_warning = 'Warning: Malfunction 3, get in touch with TechGasp support..';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    if ($response_key == 'EXPIRED') {
      $spam_master_warning = 'Warning: your license is EXPIRED and you are not protected.';
      $spam_master_warning_signature = 'Please correct the warnings.';
    }
    $spammaster_license_protection = $this->state->get('spammaster.license_protection');
    $spammaster_license_probability = $this->state->get('spammaster.license_probability');
    $spammaster_license_alert_level = $this->state->get('spammaster.license_alert_level');
    if (!isset($spammaster_license_alert_level) || empty($spammaster_license_alert_level)) {
      $spam_master_alert_level_deconstructed = 'empty';
    }
    if ($spammaster_license_alert_level == 'ALERT_0') {
      $spam_master_alert_level_deconstructed = '0';
    }
    if ($spammaster_license_alert_level == 'ALERT_1') {
      $spam_master_alert_level_deconstructed = '1';
    }
    if ($spammaster_license_alert_level == 'ALERT_2') {
      $spam_master_alert_level_deconstructed = '2';
    }
    if ($spammaster_license_alert_level == 'ALERT_3') {
      $spam_master_alert_level_deconstructed = '3';
    }
    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    if (!isset($spammaster_total_block_count) || empty($spammaster_total_block_count)) {
      $spam_master_total_block_count_result = 'empty';
    }
    if ($spammaster_total_block_count <= '10') {
      $spam_master_total_block_count_result = 'Total Blocks: good less than 10 since beginning of time';
    }
    if ($spammaster_total_block_count >= '11') {
      $spam_master_total_block_count_result = 'Total Blocks: ' . number_format($spammaster_total_block_count) . 'since beginning of time';
    }
    $spammaster_license_alert_level = $this->state->get('spammaster.license_alert_level');
    // Set 7 days time.
    $time = date('Y-m-d H:i:s');
    $time_expires = date('Y-m-d H:i:s', strtotime($time . '-7 days'));
    // Get count last 7 days of blocks from spammaster_keys.
    $spammaster_spam_watch_query = $this->connection->select('spammaster_keys', 'u');
    $spammaster_spam_watch_query->fields('u', ['spamkey']);
    $spammaster_spam_watch_query->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_spam_watch_query->where('(spamkey = :registration OR spamkey = :comment OR spamkey = :contact OR spamkey = :firewall OR spamkey = :honeypot OR spamkey = :recaptcha)', [
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      'contact' => 'spammaster-contact',
      ':firewall' => 'spammaster-firewall',
      ':honeypot' => 'spammaster-honeypot',
      ':recaptcha' => 'spammaster-recaptcha',
    ]);
    $spammaster_spam_watch_result = $spammaster_spam_watch_query->countQuery()->execute()->fetchField();
    if (empty($spammaster_spam_watch_result)) {
      $spam_master_block_count_result = 'Combined Weekly Triggers: good, nothing to report';
    }
    else {
      $spam_master_block_count_result = 'Combined Weekly Triggers: ' . number_format($spammaster_spam_watch_result);
    }
    $spammaster_buffer_size = $this->connection->select('spammaster_threats', 'u');
    $spammaster_buffer_size->fields('u', ['threat']);
    $spammaster_buffer_size_result = $spammaster_buffer_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_buffer_size_result)) {
      $spammaster_buffer_size_result_count = '1';
    }
    else {
      $spammaster_buffer_size_result_count = $spammaster_buffer_size_result;
    }
    $spammaster_white_size = $this->connection->select('spammaster_white', 'u');
    $spammaster_white_size->fields('u', ['white_']);
    $spammaster_white_size_result = $spammaster_white_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_white_size_result)) {
      $spammaster_white_size_result_count = '0';
    }
    else {
      $spammaster_white_size_result_count = $spammaster_white_size_result;
    }
    // Get count last 7 days of firewall from spammaster_keys.
    $spammaster_firewall_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_firewall_size->fields('u', ['spamkey']);
    $spammaster_firewall_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_firewall_size->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_size_result = $spammaster_firewall_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_firewall_size_result)) {
      $spam_master_firewall_count_result = 'Firewall Weekly Triggers: 0';
    }
    else {
      $spam_master_firewall_count_result = 'Firewall Weekly Triggers: ' . number_format($spammaster_firewall_size_result);
    }
    // Get count last 7 days of registrations from spammaster_keys.
    $spammaster_registration_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_registration_size->fields('u', ['spamkey']);
    $spammaster_registration_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_registration_size->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_size_result = $spammaster_registration_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_registration_size_result)) {
      $spam_master_registration_count_result = 'Registrations Weekly Triggers: 0';
    }
    else {
      $spam_master_registration_count_result = 'Registrations Weekly Triggers: ' . number_format($spammaster_registration_size_result);
    }
    // Get count last 7 days of comment from spammaster_keys.
    $spammaster_comment_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_comment_size->fields('u', ['spamkey']);
    $spammaster_comment_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_comment_size->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_size_result = $spammaster_comment_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_comment_size_result)) {
      $spam_master_comment_count_result = 'Comments Weekly Triggers: 0';
    }
    else {
      $spam_master_comment_count_result = 'Comments Weekly Triggers: ' . number_format($spammaster_comment_size_result);
    }
    // Get count last 7 days of contact from spammaster_keys.
    $spammaster_contact_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_contact_size->fields('u', ['spamkey']);
    $spammaster_contact_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_contact_size->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_size_result = $spammaster_contact_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_contact_size_result)) {
      $spam_master_contact_count_result = 'Contacts Contacts Triggers: 0';
    }
    else {
      $spam_master_contact_count_result = 'Contacts Contacts Triggers: ' . number_format($spammaster_contact_size_result);
    }
    // Get count last 7 days of honeypot from spammaster_keys.
    $spammaster_honeypot_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_honeypot_size->fields('u', ['spamkey']);
    $spammaster_honeypot_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_honeypot_size->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_size_result = $spammaster_honeypot_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_honeypot_size_result)) {
      $spam_master_honeypot_count_result = 'Honeypot Weekly Trigers: 0';
    }
    else {
      $spam_master_honeypot_count_result = 'Honeypot Weekly Trigers: ' . number_format($spammaster_honeypot_size_result);
    }
    // Get count last 7 days of recaptcha from spammaster_keys.
    $spammaster_recaptcha_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_recaptcha_size->fields('u', ['spamkey']);
    $spammaster_recaptcha_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_recaptcha_size->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_size_result = $spammaster_recaptcha_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_recaptcha_size_result)) {
      $spam_master_recaptcha_count_result = 'reCaptcha reCaptcha Triggers: 0';
    }
    else {
      $spam_master_recaptcha_count_result = 'reCaptcha reCaptcha Triggers: ' . number_format($spammaster_recaptcha_size_result);
    }
    // Email Content.
    $spam_master_table_content = 'Spam Master weekly report for ' . $spammaster_site_name . '.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_warning;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Alert Level: ' . $spam_master_alert_level_deconstructed;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Probability: ' . $spammaster_license_probability . '%';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Protected Against: ' . number_format($spammaster_license_protection) . ' million threats';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Buffer Size: ' . number_format($spammaster_buffer_size_result_count);
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Whitelist Size: ' . number_format($spammaster_white_size_result_count);
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_total_block_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_block_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_firewall_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_registration_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_comment_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_contact_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_honeypot_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_recaptcha_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_warning_signature;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'The weekly report email can be turned off in Spam Master module settings page, Emails & Reporting section.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'See you next week!';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'TechGasp Team';
    $spam_master_table_content .= "\r\n";
    $module = 'spammaster';
    $params['message'] = $spam_master_table_content;
    $langcode = $this->currentUser->getPreferredLangcode();
    $send = TRUE;
    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    // Log message.
    $spammaster_date = date('Y-m-d H:i:s');
    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-mail',
      'spamvalue' => 'Spam Master: mail weekly sent To: ' . $to,
    ])->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterMailHelpReport() {

    // Email key.
    $key = 'mail_help_report';
    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $to = 'c3RhdHNAdGVjaGdhc3AuY29t';
    $spam_master_alert_level_deconstructed = FALSE;
    $spam_master_total_block_count_result = FALSE;
    $spammaster_version = $this->state->get('spammaster.version');
    $spammaster_platform_version = \Drupal::VERSION;
    $spammaster_license_protection = $this->state->get('spammaster.license_protection');
    $spammaster_license_probability = $this->state->get('spammaster.license_probability');
    $spammaster_license_alert_level = $this->state->get('spammaster.license_alert_level');
    if (!isset($spammaster_license_alert_level) || empty($spammaster_license_alert_level)) {
      $spam_master_alert_level_deconstructed = 'empty';
    }
    if ($spammaster_license_alert_level == 'ALERT_0') {
      $spam_master_alert_level_deconstructed = '0';
    }
    if ($spammaster_license_alert_level == 'ALERT_1') {
      $spam_master_alert_level_deconstructed = '1';
    }
    if ($spammaster_license_alert_level == 'ALERT_2') {
      $spam_master_alert_level_deconstructed = '2';
    }
    if ($spammaster_license_alert_level == 'ALERT_3') {
      $spam_master_alert_level_deconstructed = '3';
    }
    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    if (!isset($spammaster_total_block_count) || empty($spammaster_total_block_count)) {
      $spam_master_total_block_count_result = 'empty';
    }
    if ($spammaster_total_block_count <= '10') {
      $spam_master_total_block_count_result = 'Total Blocks: good less than 10 since beginning of time';
    }
    if ($spammaster_total_block_count >= '11') {
      $spam_master_total_block_count_result = 'Total Blocks: ' . number_format($spammaster_total_block_count) . ' since beginning of time';
    }
    $spammaster_license_alert_level = $this->state->get('spammaster.license_alert_level');
    // Set 7 days time.
    $time = date('Y-m-d H:i:s');
    $time_expires = date('Y-m-d H:i:s', strtotime($time . '-7 days'));
    // Get count last 7 days of blocks from spammaster_keys.
    $spammaster_spam_watch_query = $this->connection->select('spammaster_keys', 'u');
    $spammaster_spam_watch_query->fields('u', ['spamkey']);
    $spammaster_spam_watch_query->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_spam_watch_query->where('(spamkey = :registration OR spamkey = :comment OR spamkey = :contact OR spamkey = :firewall OR spamkey = :honeypot OR spamkey = :recaptcha)', [
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      'contact' => 'spammaster-contact',
      ':firewall' => 'spammaster-firewall',
      ':honeypot' => 'spammaster-honeypot',
      ':recaptcha' => 'spammaster-recaptcha',
    ]);
    $spammaster_spam_watch_result = $spammaster_spam_watch_query->countQuery()->execute()->fetchField();
    if (empty($spammaster_spam_watch_result)) {
      $spam_master_block_count_result = 'Combined Weekly Triggers: good, nothing to report';
    }
    else {
      $spam_master_block_count_result = 'Combined Weekly Triggers: ' . number_format($spammaster_spam_watch_result);
    }
    $spammaster_buffer_size = $this->connection->select('spammaster_threats', 'u');
    $spammaster_buffer_size->fields('u', ['threat']);
    $spammaster_buffer_size_result = $spammaster_buffer_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_buffer_size_result)) {
      $spammaster_buffer_size_result_count = '1';
    }
    else {
      $spammaster_buffer_size_result_count = $spammaster_buffer_size_result;
    }
    $spammaster_white_size = $this->connection->select('spammaster_white', 'u');
    $spammaster_white_size->fields('u', ['white_']);
    $spammaster_white_size_result = $spammaster_white_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_white_size_result)) {
      $spammaster_white_size_result_count = '0';
    }
    else {
      $spammaster_white_size_result_count = $spammaster_white_size_result;
    }
    // Get count last 7 days of firewall from spammaster_keys.
    $spammaster_firewall_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_firewall_size->fields('u', ['spamkey']);
    $spammaster_firewall_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_firewall_size->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_size_result = $spammaster_firewall_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_firewall_size_result)) {
      $spam_master_firewall_count_result = 'Firewall Weekly Triggers: 0';
    }
    else {
      $spam_master_firewall_count_result = 'Firewall Weekly Triggers: ' . number_format($spammaster_firewall_size_result);
    }
    // Get count last 7 days of registrations from spammaster_keys.
    $spammaster_registration_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_registration_size->fields('u', ['spamkey']);
    $spammaster_registration_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_registration_size->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_size_result = $spammaster_registration_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_registration_size_result)) {
      $spam_master_registration_count_result = 'Registrations Weekly Triggers: 0';
    }
    else {
      $spam_master_registration_count_result = 'Registrations Weekly Triggers: ' . number_format($spammaster_registration_size_result);
    }
    // Get count last 7 days of comment from spammaster_keys.
    $spammaster_comment_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_comment_size->fields('u', ['spamkey']);
    $spammaster_comment_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_comment_size->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_size_result = $spammaster_comment_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_comment_size_result)) {
      $spam_master_comment_count_result = 'Comments Weekly Triggers: 0';
    }
    else {
      $spam_master_comment_count_result = 'Comments Weekly Triggers: ' . number_format($spammaster_comment_size_result);
    }
    // Get count last 7 days of contact from spammaster_keys.
    $spammaster_contact_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_contact_size->fields('u', ['spamkey']);
    $spammaster_contact_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_contact_size->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_size_result = $spammaster_contact_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_contact_size_result)) {
      $spam_master_contact_count_result = 'Contacts Weekly Triggers: 0';
    }
    else {
      $spam_master_contact_count_result = 'Contacts Weekly Triggers: ' . number_format($spammaster_contact_size_result);
    }
    // Get count last 7 days of honeypot from spammaster_keys.
    $spammaster_honeypot_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_honeypot_size->fields('u', ['spamkey']);
    $spammaster_honeypot_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_honeypot_size->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_size_result = $spammaster_honeypot_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_honeypot_size_result)) {
      $spam_master_honeypot_count_result = 'Honeypot Weekly Trigers: 0';
    }
    else {
      $spam_master_honeypot_count_result = 'Honeypot Weekly Trigers: ' . number_format($spammaster_honeypot_size_result);
    }
    // Get count last 7 days of recaptcha from spammaster_keys.
    $spammaster_recaptcha_size = $this->connection->select('spammaster_keys', 'u');
    $spammaster_recaptcha_size->fields('u', ['spamkey']);
    $spammaster_recaptcha_size->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires, ':time' => $time]);
    $spammaster_recaptcha_size->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_size_result = $spammaster_recaptcha_size->countQuery()->execute()->fetchField();
    if (empty($spammaster_recaptcha_size_result)) {
      $spam_master_recaptcha_count_result = 'reCaptcha Weekly Triggers: 0';
    }
    else {
      $spam_master_recaptcha_count_result = 'reCaptcha Weekly Triggers: ' . number_format($spammaster_recaptcha_size_result);
    }
    // Email Content.
    $spam_master_table_content = 'Spam Master weekly report for ' . $spammaster_site_name . '.';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Drupal Version: ' . $spammaster_platform_version;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Master Version: ' . $spammaster_version;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Alert Level: ' . $spam_master_alert_level_deconstructed;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Probability: ' . $spammaster_license_probability . '%';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Protected Against: ' . number_format($spammaster_license_protection) . ' million threats';
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Buffer Size: ' . number_format($spammaster_buffer_size_result_count);
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Whitelist Size: ' . number_format($spammaster_white_size_result_count);
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_total_block_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_block_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_firewall_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_registration_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_comment_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_contact_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_honeypot_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= $spam_master_recaptcha_count_result;
    $spam_master_table_content .= "\r\n";
    $spam_master_table_content .= 'Spam Master Statistics powered by TechGasp Drupal.';
    $spam_master_table_content .= "\r\n";
    $module = 'spammaster';
    $params['message'] = $spam_master_table_content;
    $langcode = $this->currentUser->getPreferredLangcode();
    $send = TRUE;
    $this->mailManager->mail($module, $key, base64_decode($to), $langcode, $params, NULL, $send);

    // Log message.
    $spammaster_date = date('Y-m-d H:i:s');
    $this->connection->insert('spammaster_keys')->fields([
      'date' => $spammaster_date,
      'spamkey' => 'spammaster-mail',
      'spamvalue' => 'Spam Master: mail help us improve was successfully sent',
    ])->execute();
  }

}
