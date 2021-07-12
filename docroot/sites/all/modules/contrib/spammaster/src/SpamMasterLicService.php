<?php

namespace Drupal\spammaster;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class service.
 */
class SpamMasterLicService {
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
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

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
   * The SpamMasterMailService Service.
   *
   * @var \Drupal\spammaster\SpamMasterMailService
   */
  protected $mailService;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, MessengerInterface $messenger, RequestStack $requestStack, Client $httpClient, ConfigFactoryInterface $configFactory, StateInterface $state, SpamMasterMailService $mailService) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->requestStack = $requestStack;
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->state = $state;
    $this->mailService = $mailService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('request_stack'),
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('spammaster.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterLicManualCreation() {

    // Get variables.
    $site_settings = $this->configFactory->getEditable('system.site');
    $spammaster_site_name = $site_settings->get('name');
    $spammaster_admin_email = $site_settings->get('mail');
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_version = $this->state->get('spammaster.version');
    $spammaster_type = $this->state->get('spammaster.type');
    $spammaster_platform = 'Drupal';
    $spammaster_platform_version = \Drupal::VERSION;
    $spammaster_platform_type = 'NO';
    $spammaster_n_websites = '0';
    $spammaster_multisite_joined = $spammaster_platform_type . ' - ' . $spammaster_n_websites;
    $spammaster_cron = "MAN";
    $spammaster_site_url = $this->requestStack->getCurrentRequest()->getHost();
    $address_unclean = $spammaster_site_url;
    $address = preg_replace('#^https?://#', '', $address_unclean);
    $spammaster_ip = $_SERVER['SERVER_ADDR'];
    // If empty ip.
    if (empty($spammaster_ip) || $spammaster_ip == '0') {
      $spammaster_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
    }
    $spammaster_hostname = gethostbyaddr($_SERVER['SERVER_ADDR']);
    // If empty host.
    if (empty($spammaster_hostname) || $spammaster_hostname == '0') {
      $spammaster_hostname = 'H ' . gethostbyname($_SERVER['SERVER_NAME']);
    }

    // Encode ssl post link security.
    $spammaster_license_url = 'aHR0cHM6Ly93d3cuc3BhbW1hc3Rlci5vcmcvd3AtY29udGVudC9wbHVnaW5zL3NwYW0tbWFzdGVyLWFkbWluaXN0cmF0b3IvaW5jbHVkZXMvbGljZW5zZS9nZXRfbGljLnBocA==';

    // Call drupal hhtpclient.
    $client = $this->httpClient;
    // Post data.
    $request = $client->post(base64_decode($spammaster_license_url), [
      'form_params' => [
        'spam_license_key' => $spammaster_license,
        'platform' => $spammaster_platform,
        'platform_version' => $spammaster_platform_version,
        'platform_type' => $spammaster_multisite_joined,
        'spam_master_version' => $spammaster_version,
        'spam_master_type' => $spammaster_type,
        'blog_name' => $spammaster_site_name,
        'blog_address' => $address,
        'blog_email' => $spammaster_admin_email,
        'blog_hostname' => $spammaster_hostname,
        'blog_ip' => $spammaster_ip,
        'spam_master_cron' => $spammaster_cron,
      ],
    ]);
    // Decode json data.
    $response = json_decode($request->getBody(), TRUE);
    if (empty($response)) {
      $spammaster_type_set = 'EMPTY';
      $spammaster_status = 'INACTIVE';
      $spammaster_protection_total_number = '0';
      $spammaster_alert_level_received = '';
      $spammaster_alert_level_p_text = '';
    }
    else {
      $spammaster_status = $response['status'];
      if ($spammaster_status == 'MALFUNCTION_3') {
        $spammaster_type_set = 'MALFUNCTION_3';
        $spammaster_protection_total_number = 'MALFUNCTION_3';
        $spammaster_alert_level_received = 'MALFUNCTION_3';
        $spammaster_alert_level_p_text = 'MALFUNCTION_3';
      }
      else {
        $spammaster_type_set = $response['type'];
        $spammaster_protection_total_number = $response['threats'];
        $spammaster_alert_level_received = $response['alert'];
        $spammaster_alert_level_p_text = $response['percent'];
      }
    }
    // Store received data in module settings.
    $this->configFactory->getEditable('spammaster.settings')
      ->set('spammaster.license_key', $spammaster_license)
      ->save();
    // Store state values.
    $manual_values = [
      'spammaster.type' => $spammaster_type_set,
      'spammaster.license_status' => $spammaster_status,
      'spammaster.license_alert_level' => $spammaster_alert_level_received,
      'spammaster.license_protection' => $spammaster_protection_total_number,
      'spammaster.license_probability' => $spammaster_alert_level_p_text,
    ];
    $this->state->setMultiple($manual_values);

    // Display status to user.
    if ($spammaster_status == 'INACTIVE' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2' || $spammaster_status == 'MALFUNCTION_3' || $spammaster_status == 'EXPIRED') {
      $this->messenger->addError($this->t('License key @spammaster_license status is: @spammaster_status. Check Spam Master configuration page and read more about statuses.', ['@spammaster_license' => $spammaster_license, '@spammaster_status' => $spammaster_status]));
      // Spam Master log.
      $spammaster_date = date('Y-m-d H:i:s');
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: license manual status check: ' . $spammaster_status,
      ])->execute();
    }
    else {
      // Log message.
      $spammaster_date = date('Y-m-d H:i:s');
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-license',
        'spamvalue' => 'Spam Master: license manual status check: ' . $spammaster_status,
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterLicDaily() {

    // Get status variable.
    $spammaster_license_status = $this->state->get('spammaster.license_status');
    if ($spammaster_license_status == 'VALID' || $spammaster_license_status == 'MALFUNCTION_1' || $spammaster_license_status == 'MALFUNCTION_2') {
      // Colect data.
      $site_settings = $this->configFactory->getEditable('system.site');
      $spammaster_site_name = $site_settings->get('name');
      $spammaster_admin_email = $site_settings->get('mail');
      $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
      $spammaster_license = $spammaster_settings->get('spammaster.license_key');
      $spammaster_license_alert_level = $this->state->get('spammaster.license_alert_level');
      $spammaster_version = $this->state->get('spammaster.version');
      $spammaster_type = $this->state->get('spammaster.type');
      $spammaster_platform = 'Drupal';
      $spammaster_platform_version = \Drupal::VERSION;
      $spammaster_platform_type = 'NO';
      $spammaster_n_websites = '0';
      $spammaster_multisite_joined = $spammaster_platform_type . ' - ' . $spammaster_n_websites;
      $spammaster_cron = "TRUE";
      $spammaster_site_url = $this->requestStack->getCurrentRequest()->getHost();
      $address_unclean = $spammaster_site_url;
      $address = preg_replace('#^https?://#', '', $address_unclean);
      $spammaster_ip = $_SERVER['SERVER_ADDR'];
      // If empty ip.
      if (empty($spammaster_ip) || $spammaster_ip == '0') {
        $spammaster_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
      }
      $spammaster_hostname = gethostbyaddr($_SERVER['SERVER_ADDR']);
      // If empty host.
      if (empty($spammaster_hostname) || $spammaster_hostname == '0') {
        $spammaster_hostname = 'H ' . gethostbyname($_SERVER['SERVER_NAME']);
      }

      // Encode ssl post link security.
      $spammaster_license_url = 'aHR0cHM6Ly93d3cuc3BhbW1hc3Rlci5vcmcvd3AtY29udGVudC9wbHVnaW5zL3NwYW0tbWFzdGVyLWFkbWluaXN0cmF0b3IvaW5jbHVkZXMvbGljZW5zZS9nZXRfbGljLnBocA==';

      // Call drupal hhtpclient.
      $client = $this->httpClient;
      // Post data.
      $request = $client->post(base64_decode($spammaster_license_url), [
        'form_params' => [
          'spam_license_key' => $spammaster_license,
          'platform' => $spammaster_platform,
          'platform_version' => $spammaster_platform_version,
          'platform_type' => $spammaster_multisite_joined,
          'spam_master_version' => $spammaster_version,
          'spam_master_type' => $spammaster_type,
          'blog_name' => $spammaster_site_name,
          'blog_address' => $address,
          'blog_email' => $spammaster_admin_email,
          'blog_hostname' => $spammaster_hostname,
          'blog_ip' => $spammaster_ip,
          'spam_master_cron' => $spammaster_cron,
        ],
      ]);
      // Decode json data.
      $response = json_decode($request->getBody(), TRUE);
      if (empty($response)) {
        $spammaster_type_set = 'EMPTY';
        $spammaster_status = 'INACTIVE';
        $spammaster_protection_total_number = '0';
        $spammaster_alert_level_received = '';
        $spammaster_alert_level_p_text = '';
      }
      else {
        $spammaster_status = $response['status'];
        if ($spammaster_status == 'MALFUNCTION_3') {
          $spammaster_type_set = 'MALFUNCTION_3';
          $spammaster_protection_total_number = 'MALFUNCTION_3';
          $spammaster_alert_level_received = 'MALFUNCTION_3';
          $spammaster_alert_level_p_text = 'MALFUNCTION_3';
        }
        else {
          $spammaster_type_set = $response['type'];
          $spammaster_protection_total_number = $response['threats'];
          $spammaster_alert_level_received = $response['alert'];
          $spammaster_alert_level_p_text = $response['percent'];
        }
      }
      // Store received data in module settings.
      $this->configFactory->getEditable('spammaster.settings')
        ->set('spammaster.license_key', $spammaster_license)
        ->save();
      // Store state values.
      $daily_values = [
        'spammaster.type' => $spammaster_type_set,
        'spammaster.license_status' => $spammaster_status,
        'spammaster.license_alert_level' => $spammaster_alert_level_received,
        'spammaster.license_protection' => $spammaster_protection_total_number,
        'spammaster.license_probability' => $spammaster_alert_level_p_text,
      ];
      $this->state->setMultiple($daily_values);

      // Call mail service for all requests.
      $spammaster_mail_service = $this->mailService;

      // Display status to user.
      if ($spammaster_status == 'INACTIVE' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2' || $spammaster_status == 'MALFUNCTION_3') {
        // Log message.
        $spammaster_date = date('Y-m-d H:i:s');
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron license warning. Status: ' . $spammaster_status,
        ])->execute();

        // Call mail service function.
        $spammaster_mail_service->spamMasterLicMalfunctions();
      }
      if ($spammaster_status == 'EXPIRED') {
        // Log message.
        $spammaster_date = date('Y-m-d H:i:s');
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron license warning. Status: ' . $spammaster_status,
        ])->execute();

        // Call mail service function.
        $spammaster_mail_service->spamMasterLicExpired();
      }
      if ($spammaster_status == 'VALID') {
        // Log message.
        $spammaster_date = date('Y-m-d H:i:s');
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron license success. Status: ' . $spammaster_status,
        ])->execute();
      }
      if ($spammaster_license_alert_level == 'ALERT_3') {
        // Log message.
        $spammaster_date = date('Y-m-d H:i:s');
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: cron alert level 3 detected.',
        ])->execute();

        // Call mail service function.
        $spammaster_mail_service->spamMasterLicAlertLevel3();
      }
    }
  }

}
