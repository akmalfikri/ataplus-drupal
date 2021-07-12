<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class controller.
 */
class SpamMasterBufferForm extends FormBase {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_buffer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['buffer_header'] = [
      '#type' => 'details',
      '#title' => $this->t('<h3>Spam Buffer</h3>'),
      '#tree' => TRUE,
      '#open' => TRUE,
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
      'threat' => [
        'data' => $this->t('Threat'),
        'field'  => 'threat',
        'specifier' => 'threat',
        'sort' => 'desc',
      ],
      'search' => [
        'data' => $this->t('Search'),
      ],
    ];
    // Get table spammaster_threats data.
    $spammaster_spam_buffer = $this->connection->select('spammaster_threats', 'u')
      ->fields('u', ['id', 'date', 'threat'])
      ->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20)
      ->execute()->fetchAll();

    $output = [];
    foreach ($spammaster_spam_buffer as $results) {
      if (!empty($results)) {
        if (filter_var($results->threat, FILTER_VALIDATE_IP)) {
          $search = Url::fromUri('https://www.spammaster.org/search-threat/', ['attributes' => ['target' => '_blank']]);
          $search_display = Link::fromTextAndUrl('+ Spam Master online database', $search);
        }
        else {
          $search_display = 'discard email';
          $search = '';
        }
        $output[$results->id] = [
          'id' => $results->id,
          'date' => $results->date,
          'threat' => $results->threat,
          'search' => $search_display,
        ];
      }
    }
    // Get buffer size.
    $spammaster_buffer_size = $this->connection->select('spammaster_threats', 'u');
    $spammaster_buffer_size->fields('u', ['threat']);
    $spammaster_buffer_size_result = $spammaster_buffer_size->countQuery()->execute()->fetchField();
    $form['buffer_header']['total_buffer'] = [
      '#markup' => $this->t('<h2>Buffer Size: <b>@buffer_size</b></h2>', ['@buffer_size' => $spammaster_buffer_size_result]),
    ];

    // Spam buffer description.
    $form['buffer_header']['header_description'] = [
      '#markup' => $this->t('<p>Spam Master Buffer greatly reduces server resources like cpu, memory and bandwidth by doing fast local machine checks. Also prevents major attacks like flooding, DoS , etc. via Spam Master Firewall.</p>'),
    ];

    // Display table.
    $form['buffer_header']['table_buffer'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => $this->t('No threats found'),
    ];

    // Disable checkboxes.
    foreach ($output as $key => $value) {
      $form['buffer_header']['table_buffer'][$key]['#disabled'] = TRUE;
    }

    // Form pager if ore than 25 entries.
    $form['buffer_header']['pager'] = [
      '#type' => 'pager',
    ];

    // Spam Buffer Description.
    $form['buffer_header']['footer_description'] = [
      '#markup' => $this->t('<p>You can use whitelisting to delete individual buffer entries. Spam Master Buffers for 3 months, older buffer entries are automatically deleted via weekly cron to keep your website clean and fast.</p>'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
