<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class controller.
 */
class SpamMasterLogForm extends ConfigFormBase {

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
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, MessengerInterface $messenger, StateInterface $state) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_log_form';
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterStatisticsPage($form, &$form_state) {
    $spam_get_statistics = $form_state->getValue('statistics_header')['buttons']['addrow']['statistics'];
    if (!empty($spam_get_statistics)) {
      $spammaster_build_statistics_url = 'http://' . $_SERVER['SERVER_NAME'] . '/statistics';
      $spammaster_statistics_url = Url::fromUri($spammaster_build_statistics_url);
      $form_state->setRedirectUrl($spammaster_statistics_url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterStatisticsOnline($form, &$form_state) {
    $spam_get_online = $form_state->getValue('statistics_header')['buttons']['addrow']['online'];
    if (!empty($spam_get_online)) {
      $online_statistics_url = 'https://www.spammaster.org/websites-stats/';
      $redirect_response = new TrustedRedirectResponse($online_statistics_url);
      $form_state->setResponse($redirect_response, 302);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterFirewallPage($form, &$form_state) {
    $spam_get_firewall = $form_state->getValue('statistics_header')['buttons']['addrow']['firewall'];
    if (!empty($spam_get_firewall)) {
      $spammaster_build_firewall_url = 'http://' . $_SERVER['SERVER_NAME'] . '/firewall';
      $spammaster_firewall_url = Url::fromUri($spammaster_build_firewall_url);
      $form_state->setRedirectUrl($spammaster_firewall_url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterDeleteKey($form, &$form_state) {
    $spam_form_key_delete = $form_state->getValue('statistics_header')['table_key'];
    $spammaster_key_date = date("Y-m-d H:i:s");
    foreach ($spam_form_key_delete as $spam_key_delete) {
      if (!empty($spam_key_delete)) {
        $this->connection->delete('spammaster_keys')
          ->condition('spamkey', 'spammaster')
          ->condition('id', $spam_key_delete, '=')
          ->execute();
        $this->messenger->addMessage($this->t('Saved Spam Master Log deletion.'));
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_key_date,
          'spamkey' => 'spammaster-log',
          'spamvalue' => 'Spam Master: log deletion, Id: ' . $spam_key_delete,
        ])->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterDeleteKeysAll() {
    $this->state->set('spammaster.total_block_count', '0');
    $this->connection->truncate('spammaster_keys')->execute();
    $this->messenger->addMessage($this->t('Saved Spam Master Statistics & Logs full deletion.'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $form['statistics_header'] = [
      '#type' => 'details',
      '#title' => $this->t('<h3>Statistics & Logging</h3>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    // Form description.
    $form['statistics_header']['header_description'] = [
      '#markup' => $this->t('<p>Click the button below to check your local statistics. In order to access your online statistics you need to login into Spam Master website with the correct email. Trial licenses login with your Administration Email Address found in Configuration -> Basic site settings. Full licenses login with the email used during license purchase, it costs peanuts per year.'),
    ];

    // Create buttons table.
    $form['statistics_header']['buttons'] = [
      '#type' => 'table',
      '#header' => [],
    ];
    // Insert addrow statistics button.
    $form['statistics_header']['buttons']['addrow']['statistics'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => $this->t('Visit Local Statistics Page'),
      '#submit' => ['::spamMasterStatisticsPage'],
    ];
    // Insert addrow statistics online button.
    $form['statistics_header']['buttons']['addrow']['online'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => $this->t('Visit Online Statistics Page'),
      '#submit' => ['::spamMasterStatisticsOnline'],
    ];
    // Insert addrow firewall button.
    $form['statistics_header']['buttons']['addrow']['firewall'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => $this->t('Visit your Firewall Page'),
      '#submit' => ['::spamMasterFirewallPage'],
    ];

    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    if (empty($spammaster_total_block_count)) {
      $spammaster_total_block_count = '0';
    }
    // Insert statistics table inside tree.
    $form['statistics_header']['total_block_count'] = [
      '#markup' => $this->t('<h2>Total Blocks: <b>@total_blocks</b></h2>', ['@total_blocks' => $spammaster_total_block_count]),
    ];

    $form['statistics_header']['statistics'] = [
      '#type' => 'table',
      '#header' => [
        'logs' => $this->t('All Logs'),
        'system_logs' => $this->t('System Logs'),
        'firewall' => $this->t('Firewall'),
        'registration' => $this->t('Registration'),
        'comment' => $this->t('Comment'),
        'contact' => $this->t('Contact'),
        'recaptha' => $this->t('reCaptcha'),
        'honeypot' => $this->t('Honeypot'),
      ],
    ];
    // Set wide dates.
    $time = date('Y-m-d H:i:s');
    $time_expires_1_day = date('Y-m-d H:i:s', strtotime($time . '-1 days'));
    $time_expires_7_days = date('Y-m-d H:i:s', strtotime($time . '-7 days'));
    $time_expires_31_days = date('Y-m-d H:i:s', strtotime($time . '-31 days'));

    // Generate all logs stats 1 day.
    $spammaster_all_logs_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_all_logs_1->fields('u', ['spamkey']);
    $spammaster_all_logs_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_all_logs_1_result = $spammaster_all_logs_1->countQuery()->execute()->fetchField();
    // Generate all logs stats 7 days.
    $spammaster_all_logs_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_all_logs_7->fields('u', ['spamkey']);
    $spammaster_all_logs_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_all_logs_7_result = $spammaster_all_logs_7->countQuery()->execute()->fetchField();
    // Generate all logs stats 31 days.
    $spammaster_all_logs_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_all_logs_31->fields('u', ['spamkey']);
    $spammaster_all_logs_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_all_logs_31_result = $spammaster_all_logs_31->countQuery()->execute()->fetchField();
    // Generate all logs stats total.
    $spammaster_all_logs = $this->connection->select('spammaster_keys', 'u');
    $spammaster_all_logs->fields('u', ['spamkey']);
    $spammaster_all_logs_result = $spammaster_all_logs->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['logs'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@logs_1</b></p><p>Weekly Entries: <b>@logs_7</b></p><p>Monthly Entries: <b>@logs_31</b></p><p>Total Entries: <b>@logs_total</b></p>', [
        '@logs_1' => $spammaster_all_logs_1_result,
        '@logs_7' => $spammaster_all_logs_7_result,
        '@logs_31' => $spammaster_all_logs_31_result,
        '@logs_total' => $spammaster_all_logs_result,
      ]),
    ];

    // Generate system logs stats 1 day.
    $spammaster_system_logs_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_system_logs_1->fields('u', ['spamkey']);
    $spammaster_system_logs_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_system_logs_1->where('(spamkey != :firewall AND spamkey != :registration AND spamkey != :comment AND spamkey != :contact AND spamkey != :honeypot AND spamkey != :recaptcha)', [
      ':firewall' => 'spammaster-firewall',
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      ':contact' => 'spammaster-contact',
      ':honeypot' => 'spammaster-honeypot',
      ':recaptcha' => 'spammaster-recaptcha',
    ]);
    $spammaster_system_logs_1_result = $spammaster_system_logs_1->countQuery()->execute()->fetchField();
    // Generate system logs stats 7 days.
    $spammaster_system_logs_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_system_logs_7->fields('u', ['spamkey']);
    $spammaster_system_logs_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_system_logs_7->where('(spamkey != :firewall AND spamkey != :registration AND spamkey != :comment AND spamkey != :contact AND spamkey != :honeypot AND spamkey != :recaptcha)', [
      ':firewall' => 'spammaster-firewall',
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      ':contact' => 'spammaster-contact',
      ':honeypot' => 'spammaster-honeypot',
      ':recaptcha' => 'spammaster-recaptcha',
    ]);
    $spammaster_system_logs_7_result = $spammaster_system_logs_7->countQuery()->execute()->fetchField();
    // Generate system logs stats 31 days.
    $spammaster_system_logs_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_system_logs_31->fields('u', ['spamkey']);
    $spammaster_system_logs_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_system_logs_31->where('(spamkey != :firewall AND spamkey != :registration AND spamkey != :comment AND spamkey != :contact AND spamkey != :honeypot AND spamkey != :recaptcha)', [
      ':firewall' => 'spammaster-firewall',
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      ':contact' => 'spammaster-contact',
      ':honeypot' => 'spammaster-honeypot',
      ':recaptcha' => 'spammaster-recaptcha',
    ]);
    $spammaster_system_logs_31_result = $spammaster_system_logs_31->countQuery()->execute()->fetchField();
    // Generate system logs stats total.
    $spammaster_system_logs = $this->connection->select('spammaster_keys', 'u');
    $spammaster_system_logs->fields('u', ['spamkey']);
    $spammaster_system_logs->where('(spamkey != :firewall AND spamkey != :registration AND spamkey != :comment AND spamkey != :contact AND spamkey != :honeypot AND spamkey != :recaptcha)', [
      ':firewall' => 'spammaster-firewall',
      ':registration' => 'spammaster-registration',
      ':comment' => 'spammaster-comment',
      ':contact' => 'spammaster-contact',
      ':honeypot' => 'spammaster-honeypot',
      ':recaptcha' => 'spammaster-recaptcha',
    ]);
    $spammaster_system_logs_result = $spammaster_system_logs->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['system_logs'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@logs_system_1</b></p><p>Weekly Entries: <b>@logs_system_7</b></p><p>Monthly Entries: <b>@logs_system_31</b></p><p>Total Entries: <b>@logs_system_total</b></p>', [
        '@logs_system_1' => $spammaster_system_logs_1_result,
        '@logs_system_7' => $spammaster_system_logs_7_result,
        '@logs_system_31' => $spammaster_system_logs_31_result,
        '@logs_system_total' => $spammaster_system_logs_result,
      ]),
    ];

    // Generate firewall stats 1 day.
    $spammaster_firewall_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_firewall_1->fields('u', ['spamkey']);
    $spammaster_firewall_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_firewall_1->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_1_result = $spammaster_firewall_1->countQuery()->execute()->fetchField();
    // Generate firewall stats 7 days.
    $spammaster_firewall_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_firewall_7->fields('u', ['spamkey']);
    $spammaster_firewall_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_firewall_7->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_7_result = $spammaster_firewall_7->countQuery()->execute()->fetchField();
    // Generate firewall stats 31 days.
    $spammaster_firewall_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_firewall_31->fields('u', ['spamkey']);
    $spammaster_firewall_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_firewall_31->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_31_result = $spammaster_firewall_31->countQuery()->execute()->fetchField();
    // Generate firewall stats total.
    $spammaster_firewall = $this->connection->select('spammaster_keys', 'u');
    $spammaster_firewall->fields('u', ['spamkey']);
    $spammaster_firewall->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_result = $spammaster_firewall->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['firewall'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@firewall_1</b></p><p>Weekly Entries: <b>@firewall_7</b></p><p>Monthly Entries: <b>@firewall_31</b></p><p>Total Entries: <b>@firewall_total</b></p>', [
        '@firewall_1' => $spammaster_firewall_1_result,
        '@firewall_7' => $spammaster_firewall_7_result,
        '@firewall_31' => $spammaster_firewall_31_result,
        '@firewall_total' => $spammaster_firewall_result,
      ]),
    ];

    // Generate registration stats 1 day.
    $spammaster_registration_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_registration_1->fields('u', ['spamkey']);
    $spammaster_registration_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_registration_1->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_1_result = $spammaster_registration_1->countQuery()->execute()->fetchField();
    // Generate registration stats 7 days.
    $spammaster_registration_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_registration_7->fields('u', ['spamkey']);
    $spammaster_registration_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_registration_7->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_7_result = $spammaster_registration_7->countQuery()->execute()->fetchField();
    // Generate registration stats 31 days.
    $spammaster_registration_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_registration_31->fields('u', ['spamkey']);
    $spammaster_registration_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_registration_31->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_31_result = $spammaster_registration_31->countQuery()->execute()->fetchField();
    // Generate registration stats total.
    $spammaster_registration = $this->connection->select('spammaster_keys', 'u');
    $spammaster_registration->fields('u', ['spamkey']);
    $spammaster_registration->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $spammaster_registration_result = $spammaster_registration->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['registration'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@registration_1</b></p><p>Weekly Entries: <b>@registration_7</b></p><p>Monthly Entries: <b>@registration_31</b></p><p>Total Entries: <b>@registration_total</b></p>', [
        '@registration_1' => $spammaster_registration_1_result,
        '@registration_7' => $spammaster_registration_7_result,
        '@registration_31' => $spammaster_registration_31_result,
        '@registration_total' => $spammaster_registration_result,
      ]),
    ];

    // Generate comment stats 1 day.
    $spammaster_comment_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_comment_1->fields('u', ['spamkey']);
    $spammaster_comment_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_comment_1->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_1_result = $spammaster_comment_1->countQuery()->execute()->fetchField();
    // Generate comment stats 7 days.
    $spammaster_comment_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_comment_7->fields('u', ['spamkey']);
    $spammaster_comment_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_comment_7->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_7_result = $spammaster_comment_7->countQuery()->execute()->fetchField();
    // Generate comment stats 31 days.
    $spammaster_comment_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_comment_31->fields('u', ['spamkey']);
    $spammaster_comment_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_comment_31->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_31_result = $spammaster_comment_31->countQuery()->execute()->fetchField();
    // Generate comment stats total.
    $spammaster_comment = $this->connection->select('spammaster_keys', 'u');
    $spammaster_comment->fields('u', ['spamkey']);
    $spammaster_comment->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $spammaster_comment_result = $spammaster_comment->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['comment'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@comment_1</b></p><p>Weekly Entries: <b>@comment_7</b></p><p>Monthly Entries: <b>@comment_31</b></p><p>Total Entries: <b>@comment_total</b></p>', [
        '@comment_1' => $spammaster_comment_1_result,
        '@comment_7' => $spammaster_comment_7_result,
        '@comment_31' => $spammaster_comment_31_result,
        '@comment_total' => $spammaster_comment_result,
      ]),
    ];

    // Generate contact stats 1 day.
    $spammaster_contact_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_contact_1->fields('u', ['spamkey']);
    $spammaster_contact_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_contact_1->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_1_result = $spammaster_contact_1->countQuery()->execute()->fetchField();
    // Generate contact stats 7 days.
    $spammaster_contact_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_contact_7->fields('u', ['spamkey']);
    $spammaster_contact_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_contact_7->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_7_result = $spammaster_contact_7->countQuery()->execute()->fetchField();
    // Generate contact stats 31 days.
    $spammaster_contact_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_contact_31->fields('u', ['spamkey']);
    $spammaster_contact_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_contact_31->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_31_result = $spammaster_contact_31->countQuery()->execute()->fetchField();
    // Generate contact stats total.
    $spammaster_contact = $this->connection->select('spammaster_keys', 'u');
    $spammaster_contact->fields('u', ['spamkey']);
    $spammaster_contact->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $spammaster_contact_result = $spammaster_contact->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['contact'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@contact_1</b></p><p>Weekly Entries: <b>@contact_7</b></p><p>Monthly Entries: <b>@contact_31</b></p><p>Total Entries: <b>@contact_total</b></p>', [
        '@contact_1' => $spammaster_contact_1_result,
        '@contact_7' => $spammaster_contact_7_result,
        '@contact_31' => $spammaster_contact_31_result,
        '@contact_total' => $spammaster_contact_result,
      ]),
    ];

    // Generate recaptcha stats 1 day.
    $spammaster_recaptcha_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_recaptcha_1->fields('u', ['spamkey']);
    $spammaster_recaptcha_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_recaptcha_1->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_1_result = $spammaster_recaptcha_1->countQuery()->execute()->fetchField();
    // Generate recaptcha stats 7 days.
    $spammaster_recaptcha_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_recaptcha_7->fields('u', ['spamkey']);
    $spammaster_recaptcha_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_recaptcha_7->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_7_result = $spammaster_recaptcha_7->countQuery()->execute()->fetchField();
    // Generate recaptcha stats 31 days.
    $spammaster_recaptcha_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_recaptcha_31->fields('u', ['spamkey']);
    $spammaster_recaptcha_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_recaptcha_31->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_31_result = $spammaster_recaptcha_31->countQuery()->execute()->fetchField();
    // Generate recaptcha stats total.
    $spammaster_recaptcha = $this->connection->select('spammaster_keys', 'u');
    $spammaster_recaptcha->fields('u', ['spamkey']);
    $spammaster_recaptcha->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_result = $spammaster_recaptcha->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['recaptcha'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@recaptcha_1</b></p><p>Weekly Entries: <b>@recaptcha_7</b></p><p>Monthly Entries: <b>@recaptcha_31</b></p><p>Total Entries: <b>@recaptcha_total</b></p>', [
        '@recaptcha_1' => $spammaster_recaptcha_1_result,
        '@recaptcha_7' => $spammaster_recaptcha_7_result,
        '@recaptcha_31' => $spammaster_recaptcha_31_result,
        '@recaptcha_total' => $spammaster_recaptcha_result,
      ]),
    ];

    // Generate honeypot stats 1 day.
    $spammaster_honeypot_1 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_honeypot_1->fields('u', ['spamkey']);
    $spammaster_honeypot_1->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_1_day, ':time' => $time]);
    $spammaster_honeypot_1->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_1_result = $spammaster_honeypot_1->countQuery()->execute()->fetchField();
    // Generate honeypot stats 7 days.
    $spammaster_honeypot_7 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_honeypot_7->fields('u', ['spamkey']);
    $spammaster_honeypot_7->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_7_days, ':time' => $time]);
    $spammaster_honeypot_7->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_7_result = $spammaster_honeypot_7->countQuery()->execute()->fetchField();
    // Generate honeypot stats 31 days.
    $spammaster_honeypot_31 = $this->connection->select('spammaster_keys', 'u');
    $spammaster_honeypot_31->fields('u', ['spamkey']);
    $spammaster_honeypot_31->where('(date BETWEEN :time_expires AND :time)', [':time_expires' => $time_expires_31_days, ':time' => $time]);
    $spammaster_honeypot_31->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_31_result = $spammaster_honeypot_31->countQuery()->execute()->fetchField();
    // Generate honeypot stats total.
    $spammaster_honeypot = $this->connection->select('spammaster_keys', 'u');
    $spammaster_honeypot->fields('u', ['spamkey']);
    $spammaster_honeypot->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_result = $spammaster_honeypot->countQuery()->execute()->fetchField();
    $form['statistics_header']['statistics']['addrow']['honeypot'] = [
      '#markup' => $this->t('<p>Daily Entries: <b>@honeypot_1</b></p><p>Weekly Entries: <b>@honeypot_7</b></p><p>Monthly Entries: <b>@honeypot_31</b></p><p>Total Entries: <b>@honeypot_total</b></p>', [
        '@honeypot_1' => $spammaster_honeypot_1_result,
        '@honeypot_7' => $spammaster_honeypot_7_result,
        '@honeypot_31' => $spammaster_honeypot_31_result,
        '@honeypot_total' => $spammaster_honeypot_result,
      ]),
    ];

    // Construct header.
    $header_key = [
      'id' => [
        'data' => $this->t('ID'),
        'field'  => 'id',
        'specifier' => 'id',
        'sort' => 'desc',
      ],
      'date' => [
        'data' => $this->t('Date'),
        'field'  => 'date',
        'specifier' => 'date',
        'sort' => 'desc',
      ],
      'spamkey' => [
        'data' => $this->t('Type'),
        'field'  => 'spamkey',
        'specifier' => 'spamkey',
        'sort' => 'desc',
      ],
      'spamvalue' => [
        'data' => $this->t('Description'),
        'field'  => 'spamvalue',
        'specifier' => 'spamvalue',
        'sort' => 'desc',
      ],
    ];
    // Get table spammaster_keys data.
    $spammaster_spam_key = $this->connection->select('spammaster_keys', 'u')
      ->fields('u', ['id', 'date', 'spamkey', 'spamvalue'])
      ->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header_key)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20)
      ->execute()->fetchAll();

    $output_key = [];
    foreach ($spammaster_spam_key as $results_key) {
      if (!empty($results_key)) {
        $output_key[$results_key->id] = [
          'id' => $results_key->id,
          'date' => $results_key->date,
          'spamkey' => $results_key->spamkey,
          'spamvalue' => $results_key->spamvalue,
        ];
      }
    }
    // Display table.
    $form['statistics_header']['table_key'] = [
      '#type' => 'tableselect',
      '#header' => $header_key,
      '#options' => $output_key,
      '#empty' => $this->t('No log found'),
    ];
    // Spam log description.
    $form['statistics_header']['description'] = [
      '#markup' => $this->t('<p>Before deleting! Spam Master keeps logs for 3 months. Older logs are automatically deleted via weekly cron to keep your website clean and fast.</p>'),
    ];
    // Delete button at end of table, calls spammasterdeletekey function.
    $form['statistics_header']['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => $this->t('Delete Log Entry'),
      '#submit' => ['::spamMasterDeleteKey'],
    ];
    // Delete button at end of table, calls spammasterdeletekeysall function.
    $form['statistics_header']['submit_all'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => $this->t('Delete all Statistics & Logs -> Caution, no way back'),
      '#submit' => ['::spamMasterDeleteKeysAll'],
    ];
    // Form pager if ore than 25 entries.
    $form['statistics_header']['pager'] = [
      '#type' => 'pager',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings_log',
    ];
  }

}
