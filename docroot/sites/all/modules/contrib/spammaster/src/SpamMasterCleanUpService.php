<?php

namespace Drupal\spammaster;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class service.
 */
class SpamMasterCleanUpService {

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
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $configFactory, StateInterface $state) {
    $this->connection = $connection;
    $this->configFactory = $configFactory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterCleanUpKeys() {

    // Get variables.
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license_status = $this->state->get('spammaster.license_status');
    // Prepare now date.
    $spammaster_date = date('Y-m-d H:i:s');
    $time = date('Y-m-d H:i:s');
    if ($spammaster_license_status == 'VALID' || $spammaster_license_status == 'MALFUNCTION_1' || $spammaster_license_status == 'MALFUNCTION_2') {
      // Process system log.
      $spammaster_cleanup_system = $spammaster_settings->get('spammaster.cleanup_system');
      if ($spammaster_cleanup_system == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: System weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_sys_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_system.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster')
          ->condition('date', $minus_sys_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: System weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_system . ' months.',
        ])->execute();
      }
      // Process cron log.
      $spammaster_cleanup_cron = $spammaster_settings->get('spammaster.cleanup_cron');
      if ($spammaster_cleanup_cron == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Cron weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_cron_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_cron.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-cron')
          ->condition('date', $minus_cron_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Cron weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_cron . ' months.',
        ])->execute();
      }

      // Process mail log.
      $spammaster_cleanup_mail = $spammaster_settings->get('spammaster.cleanup_mail');
      if ($spammaster_cleanup_mail == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Mail weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_mail_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_mail.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-mail')
          ->condition('date', $minus_mail_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Mail weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_mail . ' months.',
        ])->execute();
      }

      // Process whitelist log.
      $spammaster_cleanup_whitelist = $spammaster_settings->get('spammaster.cleanup_whitelist');
      if ($spammaster_cleanup_whitelist == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Whitelist weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_white_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_whitelist.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-whitelist')
          ->condition('date', $minus_white_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Whitelist weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_whitelist . ' months.',
        ])->execute();
      }

      // Process firewall log.
      $spammaster_cleanup_firewall = $spammaster_settings->get('spammaster.cleanup_firewall');
      if ($spammaster_cleanup_firewall == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Firewall weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_fire_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_firewall.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-firewall')
          ->condition('date', $minus_fire_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Firewall weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_firewall . ' months.',
        ])->execute();
      }

      // Process registration log.
      $spammaster_cleanup_registration = $spammaster_settings->get('spammaster.cleanup_registration');
      if ($spammaster_cleanup_registration == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Registration weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_reg_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_registration.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-registration')
          ->condition('date', $minus_reg_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Registration weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_registration . ' months.',
        ])->execute();
      }

      // Process comment log.
      $spammaster_cleanup_comment = $spammaster_settings->get('spammaster.cleanup_comment');
      if ($spammaster_cleanup_comment == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Comment weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_com_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_comment.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-comment')
          ->condition('date', $minus_com_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Comment weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_comment . ' months.',
        ])->execute();
      }

      // Process contact log.
      $spammaster_cleanup_contact = $spammaster_settings->get('spammaster.cleanup_contact');
      if ($spammaster_cleanup_contact == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Contact weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_con_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_contact.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-contact')
          ->condition('date', $minus_con_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Contact weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_contact . ' months.',
        ])->execute();
      }

      // Process honeypot log.
      $spammaster_cleanup_honeypot = $spammaster_settings->get('spammaster.cleanup_honeypot');
      if ($spammaster_cleanup_honeypot == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Honeypot weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_hon_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_honeypot.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-honeypot')
          ->condition('date', $minus_hon_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: Honeypot weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_honeypot . ' months.',
        ])->execute();
      }

      // Process recaptcha log.
      $spammaster_cleanup_recaptcha = $spammaster_settings->get('spammaster.cleanup_recaptcha');
      if ($spammaster_cleanup_recaptcha == '0') {
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: reCaptcha weekly clean-up cron did not run. Is set to infinite.',
        ])->execute();
      }
      else {
        $minus_rec_months_time = date('Y-m-d h:i:s', strtotime("-'.$spammaster_cleanup_recaptcha.' months", strtotime($time)));
        // Delete data older than 3 months.
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster-recaptcha')
          ->condition('date', $minus_rec_months_time, '<=')
          ->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-cron',
          'spamvalue' => 'Spam Master: reCaptcha weekly clean-up cron successfully run. Deleted older than ' . $spammaster_cleanup_recaptcha . ' months.',
        ])->execute();
      }
    }
    else {
      // Log message.
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master: Weekly logs clean-up cron did not run, check your license status.',
      ])->execute();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterCleanUpBuffer() {

    // Get variables.
    $spammaster_license_status = $this->state->get('spammaster.license_status');
    // Prepare date.
    $spammaster_date = date('Y-m-d H:i:s');
    if ($spammaster_license_status == 'VALID' || $spammaster_license_status == 'MALFUNCTION_1' || $spammaster_license_status == 'MALFUNCTION_2') {
      // Set 365 days time.
      $time = date('Y-m-d H:i:s');
      $time_expires = date('Y-m-d H:i:s', strtotime($time . '-365 days'));

      // Delete data older than 365 days.
      $this->connection->delete('spammaster_threats')
        ->condition('date', $time_expires, '<=')
        ->execute();

      // Log message.
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master:  Buffer weekly clean-up cron successful run.',
      ])->execute();
    }
    else {
      // Log message.
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_date,
        'spamkey' => 'spammaster-cron',
        'spamvalue' => 'Spam Master:  Buffer weekly clean-up cron did not run, check your license status.',
      ])->execute();
    }

  }

}
