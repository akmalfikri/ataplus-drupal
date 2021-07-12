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
class SpamMasterWhiteForm extends ConfigFormBase {

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
    return 'spammaster_settings_white_form';
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterDeleteWhite($form, &$form_state) {
    $white_form_delete = $form_state->getValue('white_header')['table_white'];
    $spammaster_white_date = date("Y-m-d H:i:s");
    foreach ($white_form_delete as $white_row_delete) {
      if (!empty($white_row_delete)) {
        $this->connection->delete('spammaster_white')
          ->condition('id', $white_row_delete, '=')
          ->execute();
        $this->messenger->addMessage($this->t('Saved Whitelist deletion.'));
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_white_date,
          'spamkey' => 'spammaster-whitelist',
          'spamvalue' => 'Spam Master: whitelist deletion, Id: ' . $white_row_delete,
        ])->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('spammaster.white');

    $form['white_header'] = [
      '#type' => 'details',
      '#title' => $this->t('<h3>Whitelist</h3>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    // Insert Whitelist field.
    $form['white_header']['white_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Insert Ip or email:'),
      '#default_value' => $config->get('spammaster.white_key'),
      '#description' => $this->t('Insert frequent users ips or emails to exempt them from spam checks and if they exist, automatically delete them from spam buffer.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive-49',
        ],
      ],
    ];

    // Construct header.
    $header = [
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
      'white' => [
        'data' => $this->t('Whitelisted'),
        'field'  => 'white',
        'specifier' => 'white',
        'sort' => 'desc',
      ],
    ];
    // Get table spammaster_white data.
    $spammaster_spam_white = $this->connection->select('spammaster_white', 'u')
      ->fields('u', ['id', 'date', 'white'])
      ->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20)
      ->execute()->fetchAll();

    $output = [];
    foreach ($spammaster_spam_white as $results) {
      if (!empty($results)) {
        $output[$results->id] = [
          'id' => $results->id,
          'date' => $results->date,
          'white' => $results->white,
        ];
      }
    }
    // Get White Size.
    $spammaster_white_size = $this->connection->select('spammaster_white', 'u');
    $spammaster_white_size->fields('u', ['white']);
    $spammaster_white_size_result = $spammaster_white_size->countQuery()->execute()->fetchField();
    $form['white_header']['total_white'] = [
      '#markup' => $this->t('<h2>Whitelist Size: <b>@white_size</b></h2>', ['@white_size' => $spammaster_white_size_result]),
    ];

    // Whitelist Description.
    $form['white_header']['header_description'] = [
      '#markup' => $this->t('<p>Spam Buffer Whitelisting excludes spam checks from safe Emails or Ips.</p>'),
    ];

    // Display table.
    $form['white_header']['table_white'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => $this->t('No Entries found'),
    ];
    // Delete button at end of table, calls spammasterdeletewhite function.
    $form['white_header']['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => $this->t('Delete Whitelist Entry'),
      '#submit' => ['::spamMasterDeleteWhite'],
    ];

    // Form pager if ore than 25 entries.
    $form['white_header']['pager'] = [
      '#type' => 'pager',
    ];

    // Whitelist Description.
    $form['white_header']['footer_description'] = [
      '#markup' => $this->t('<p>Add frequent user emails or ips to exclude from spam checks.</p>'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('white_header')['white_key']) && (!filter_var($form_state->getValue('white_header')['white_key'], FILTER_VALIDATE_EMAIL)) && (!filter_var($form_state->getValue('white_header')['white_key'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) && (!filter_var($form_state->getValue('white_header')['white_key'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))) {
      $form_state->setErrorByName('white_header', $this->t('Validation of Ip or Email Failed. Please insert a valid Ip or Email.'));
      // Log message.
      $spammaster_white_date = date("Y-m-d H:i:s");
      $this->connection->insert('spammaster_keys')->fields([
        'date' => $spammaster_white_date,
        'spamkey' => 'spammaster-whitelist',
        'spamvalue' => 'Spam Master: Whitelist Ip or Email Validation Failed for: ' . $form_state->getValue('white_header')['white_key'],
      ])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $white_to_insert = $form_state->getValue('white_header')['white_key'];
    if (!empty($white_to_insert)) {
      // Insert whitelist db.
      $spammaster_db_ip = $this->connection->select('spammaster_white', 'u');
      $spammaster_db_ip->fields('u', ['white']);
      $spammaster_db_ip->where('(white = :ipemail)', [':ipemail' => $white_to_insert]);
      $spammaster_db_ip_result = $spammaster_db_ip->execute()->fetchObject();
      if (empty($spammaster_db_ip_result)) {
        $spammaster_date = date("Y-m-d H:i:s");
        $this->connection->insert('spammaster_white')->fields([
          'date' => $spammaster_date,
          'white' => $white_to_insert,
        ])->execute();
        // Log message.
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-whitelist',
          'spamvalue' => 'Spam Master: Whitelist insertion successful for: ' . $white_to_insert,
        ])->execute();
        // Delete from buffer if exists.
        $spammaster_check_buffer = $this->connection->select('spammaster_threats', 'u');
        $spammaster_check_buffer->fields('u', ['threat']);
        $spammaster_check_buffer->where('(threat = :white)', [':white' => $white_to_insert]);
        $spammaster_check_buffer_result = $spammaster_check_buffer->execute()->fetchObject();
        if (!empty($spammaster_check_buffer_result)) {
          $this->connection->delete('spammaster_threats')
            ->condition('threat', $white_to_insert, '=')
            ->execute();
          // Log message.
          $this->connection->insert('spammaster_keys')->fields([
            'date' => $spammaster_date,
            'spamkey' => 'spammaster-whitelist',
            'spamvalue' => 'Spam Master: Whitelist buffer deletion successful for: ' . $white_to_insert,
          ])->execute();
        }
      }
    }
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings_white',
    ];
  }

}
