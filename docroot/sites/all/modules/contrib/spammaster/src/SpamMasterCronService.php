<?php

namespace Drupal\spammaster;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class service.
 */
class SpamMasterCronService {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * The SpamMasterLicService Service.
   *
   * @var \Drupal\spammaster\SpamMasterLicService
   */
  protected $licService;

  /**
   * The SpamMasterMailService Service.
   *
   * @var \Drupal\spammaster\SpamMasterMailService
   */
  protected $mailService;

  /**
   * The SpamMasterCleanUpService Service.
   *
   * @var \Drupal\spammaster\SpamMasterCleanUpService
   */
  protected $cleanService;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $configFactory, StateInterface $state, SpamMasterLicService $licService, SpamMasterMailService $mailService, SpamMasterCleanUpService $cleanService) {
    $this->connection = $connection;
    $this->configFactory = $configFactory;
    $this->state = $state;
    $this->licService = $licService;
    $this->mailService = $mailService;
    $this->cleanService = $cleanService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('spammaster.lic_service'),
      $container->get('spammaster.mail_service'),
      $container->get('spammaster.clean_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterDailyCron() {
    $spammaster_date = date('Y-m-d H:i:s');
    $spammaster_response_key = $this->state->get('spammaster.license_status');
    $spammaster_alert_3 = $this->state->get('spammaster.license_alert_level');
    $spammaster_settings_protection = $this->configFactory->getEditable('spammaster.settings_protection');
    $spammaster_email_alert_3 = $spammaster_settings_protection->get('spammaster.email_alert_3');
    $spammaster_email_daily_report = $spammaster_settings_protection->get('spammaster.email_daily_report');

    if ($spammaster_response_key == 'VALID' || $spammaster_response_key == 'MALFUNCTION_1' || $spammaster_response_key == 'MALFUNCTION_2') {
      // Call lic service.
      $spammaster_lic_service = $this->licService;
      $spammaster_lic_service->spamMasterLicDaily();

      // Call mail service.
      $spammaster_mail_service = $this->mailService;
      if ($spammaster_email_alert_3 != 0 && $spammaster_alert_3 == 'ALERT_3') {
        $spammaster_mail_service->spamMasterLicAlertLevel3();
      }
      if ($spammaster_email_daily_report != 0) {
        $spammaster_mail_service->spamMasterMailDailyReport();
      }
    }
    else {
      // Log message.
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master: Warning! daily cron did not run, check your license status.',
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterWeeklyCron() {
    $spammaster_date = date('Y-m-d H:i:s');
    $response_key = $this->state->get('spammaster.license_status');
    $spammaster_settings_protection = $this->configFactory->getEditable('spammaster.settings_protection');
    $spammaster_email_weekly_report = $spammaster_settings_protection->get('spammaster.email_weekly_report');
    $spammaster_email_improve = $spammaster_settings_protection->get('spammaster.email_improve');

    if ($response_key == 'VALID' || $response_key == 'MALFUNCTION_1' || $response_key == 'MALFUNCTION_2') {
      // Call mail service.
      $spammaster_mail_service = $this->mailService;
      if ($spammaster_email_weekly_report != 0) {
        $spammaster_mail_service->spamMasterMailWeeklyReport();
      }
      if ($spammaster_email_improve != 0) {
        $spammaster_mail_service->spamMasterMailHelpReport();
      }
      // Call clean-up service.
      $spammaster_cleanup_service = $this->cleanService;
      $spammaster_cleanup_service->spamMasterCleanUpKeys();
      $spammaster_cleanup_service->spamMasterCleanUpBuffer();
    }
    else {
      // Log message.
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master: Warning! weekly cron did not run, check your license status.',
      ])->execute();
    }
  }

}
