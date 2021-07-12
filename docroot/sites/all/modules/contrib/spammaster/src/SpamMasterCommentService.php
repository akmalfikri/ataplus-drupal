<?php

namespace Drupal\spammaster;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class service.
 */
class SpamMasterCommentService {
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
   * The String Translation..
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The form class.
   *
   * @var \Drupal\Core\Form\FormInterface
   */
  protected $form;

  /**
   * The form class.
   *
   * @var \Drupal\Core\Form\FormInterface
   */
  protected $formstate;

  /**
   * The SpamMasterCommentService service.
   *
   * @var \Drupal\spammmaster\SpamMasterCommentService
   */
  protected $spammasterip;

  /**
   * The SpamMasterCommentService service.
   *
   * @var \Drupal\spammmaster\SpamMasterCommentService
   */
  protected $spammasteragent;

  /**
   * The SpamMasterCommentService service.
   *
   * @var \Drupal\spammmaster\SpamMasterCommentService
   */
  protected $spammasteremail;

  /**
   * The SpamMasterCommentService service.
   *
   * @var \Drupal\spammmaster\SpamMasterCommentService
   */
  protected $spammastercomment;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, MessengerInterface $messenger, RequestStack $requestStack, Client $httpClient, ConfigFactoryInterface $configFactory, StateInterface $state, TranslationInterface $stringTranslation) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->requestStack = $requestStack;
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->state = $state;
    $this->stringTranslation = $stringTranslation;
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
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterCommentCheck($form, $formstate, $spammasterip, $spammasteragent, $spammasteremail, $spammastercomment) {

    $this->form = $form;
    $this->formstate = $formstate;
    $this->spammasterip = $spammasterip;
    $this->spammasteragent = $spammasteragent;
    $this->spammasteremail = $spammasteremail;
    $this->spammastercomment = $spammastercomment;
    $spammaster_date = date("Y-m-d H:i:s");
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $this->state->get('spammaster.license_status');
    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    $spammaster_settings_protection = $this->configFactory->getEditable('spammaster.settings_protection');
    $spammaster_block_message = $spammaster_settings_protection->get('spammaster.block_message');
    $spammaster_comment_cyrillic = $spammaster_settings_protection->get('spammaster.extra_comment_cyrillic');
    $spammaster_comment_asian = $spammaster_settings_protection->get('spammaster.extra_comment_asian');
    $spammaster_comment_arabic = $spammaster_settings_protection->get('spammaster.extra_comment_arabic');
    $spammaster_comment_spam = $spammaster_settings_protection->get('spammaster.extra_comment_spam');
    $blog_threat_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    if ($spammaster_status == 'VALID' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2') {
      // Comment text needs to be called first.
      $result_comment_content_trim = substr($spammastercomment, 0, 360);
      $result_comment_content_clean = strip_tags($result_comment_content_trim);
      if (empty($result_comment_content_clean)) {
        $result_comment_content_clean = 'empty-comment';
      }
      // Comment email needs to be called second.
      if (empty($spammasteremail) || is_array($spammasteremail)) {
        $spammasteremail = 'drup@' . date('Ymdhis') . '.drup';
      }
      // Exempt whitelist.
      $spammaster_white_query = $this->connection->select('spammaster_white', 'u');
      $spammaster_white_query->fields('u', ['white']);
      $spammaster_white_query->where('(white = :ip OR white = :email)', [':ip' => $spammasterip, ':email' => $spammasteremail]);
      $spammaster_white_result = $spammaster_white_query->execute()->fetchObject();
      // White positive, log insert.
      if (!empty($spammaster_white_result)) {
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-comment',
          'spamvalue' => 'Spam Master: comment found in whitelist, Ip: ' . $spammasterip . ', Email: ' . $spammasteremail . ', Agent: ' . $spammasteragent,
        ])->execute();
      }
      else {
        // Buffer db check.
        $spammaster_spam_buffer_query = $this->connection->select('spammaster_threats', 'u');
        $spammaster_spam_buffer_query->fields('u', ['threat']);
        $spammaster_spam_buffer_query->where('(threat = :ip OR threat = :email)', [':ip' => $spammasterip, ':email' => $spammasteremail]);
        $spammaster_spam_buffer_result = $spammaster_spam_buffer_query->execute()->fetchObject();
        // Buffer db positive, throw error, log insert.
        if (!empty($spammaster_spam_buffer_result)) {
          $formstate->setErrorByName('mail', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
          $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
          $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
          $this->connection->insert('spammaster_keys')->fields([
            'date' => $spammaster_date,
            'spamkey' => 'spammaster-comment',
            'spamvalue' => 'Spam Master: comment buffer block, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
          ])->execute();
          throw new AccessDeniedHttpException();
        }
        else {
          // Check for spam chars if active.
          if ($spammaster_comment_cyrillic != 0 || $spammaster_comment_asian != 0 || $spammaster_comment_arabic != 0 || $spammaster_comment_spam != 0) {
            // Create data to be posted.
            $blog_threat_type = 'comment';
            $blog_web_address = $this->requestStack->getCurrentRequest()->getHost();
            $address_unclean = $blog_web_address;
            $address = preg_replace('#^https?://#', '', $address_unclean);
            $blog_server_ip = $_SERVER['SERVER_ADDR'];
            // If empty ip.
            if (empty($blog_server_ip) || $blog_server_ip == '0') {
              @$blog_server_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
            }
            $spam_master_leaning_url = 'aHR0cHM6Ly93d3cuc3BhbW1hc3Rlci5vcmcvd3AtY29udGVudC9wbHVnaW5zL3NwYW0tbWFzdGVyLWFkbWluaXN0cmF0b3IvaW5jbHVkZXMvbGVhcm5pbmcvZ2V0X2xlYXJuX2NvbS5waHA=';
            // Call drupal hhtpclient.
            $client = $this->httpClient;
            // Check cyrillic.
            if ($spammaster_comment_cyrillic != 0) {
              $blacklist_cyrillic_char_string = $this->state->get('spammaster.extra_cyrillic_char');
              $blacklist_cyrillic_char_array = explode(',', $blacklist_cyrillic_char_string);
              $blacklist_cyrillic_char_size = count($blacklist_cyrillic_char_array);
              // Analyze list.
              for ($i = 0; $i < $blacklist_cyrillic_char_size; $i++) {
                $blacklist_cyrillic_char_current = trim($blacklist_cyrillic_char_array[$i]);
                // Check list.
                if (stripos($result_comment_content_clean, $blacklist_cyrillic_char_current) !== FALSE) {
                  // Insert ip into buffer db.
                  $this->connection->insert('spammaster_threats')->fields([
                    'date' => $spammaster_date,
                    'threat' => $spammasterip,
                  ])->execute();
                  // Post data.
                  $client->post(base64_decode($spam_master_leaning_url), [
                    'form_params' => [
                      'blog_license_key' => $spammaster_license,
                      'blog_threat_ip' => $blog_threat_ip,
                      'blog_threat_type' => $blog_threat_type,
                      'blog_threat_email' => $spammasteremail,
                      'blog_threat_content' => 'Cyrillic Char: ' . $blacklist_cyrillic_char_current . 'Text: ' . $result_comment_content_clean,
                      'blog_threat_agent' => $spammasteragent,
                      'blog_web_adress' => $address,
                      'blog_server_ip' => $blog_server_ip,
                    ],
                  ]);
                  // Log char block.
                  $this->connection->insert('spammaster_keys')->fields([
                    'date' => $spammaster_date,
                    'spamkey' => 'spammaster-comment',
                    'spamvalue' => 'Spam Master: comment message char cyrillic block, Char: ' . $blacklist_cyrillic_char_current . ', Ip: ' . $spammasterip . ', Email: ' . $spammasteremail . ', Message: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
                  ])->execute();
                  $formstate->setErrorByName('mail', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
                  $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
                  $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
                  throw new AccessDeniedHttpException();
                }
              }
            }
            // Check asian.
            if ($spammaster_comment_asian != 0) {
              $blacklist_asian_char_string = $this->state->get('spammaster.extra_asian_char');
              $blacklist_asian_char_array = explode(',', $blacklist_asian_char_string);
              $blacklist_asian_char_size = count($blacklist_asian_char_array);
              // Analyze list.
              for ($i = 0; $i < $blacklist_asian_char_size; $i++) {
                $blacklist_asian_char_current = trim($blacklist_asian_char_array[$i]);
                // Check list.
                if (stripos($result_comment_content_clean, $blacklist_asian_char_current) !== FALSE) {
                  // Insert ip into buffer db.
                  $this->connection->insert('spammaster_threats')->fields([
                    'date' => $spammaster_date,
                    'threat' => $spammasterip,
                  ])->execute();
                  // Post data.
                  $client->post(base64_decode($spam_master_leaning_url), [
                    'form_params' => [
                      'blog_license_key' => $spammaster_license,
                      'blog_threat_ip' => $blog_threat_ip,
                      'blog_threat_type' => $blog_threat_type,
                      'blog_threat_email' => $spammasteremail,
                      'blog_threat_content' => 'Asian Char: ' . $blacklist_asian_char_current . 'Text: ' . $result_comment_content_clean,
                      'blog_threat_agent' => $spammasteragent,
                      'blog_web_adress' => $address,
                      'blog_server_ip' => $blog_server_ip,
                    ],
                  ]);
                  // Log char block.
                  $this->connection->insert('spammaster_keys')->fields([
                    'date' => $spammaster_date,
                    'spamkey' => 'spammaster-comment',
                    'spamvalue' => 'Spam Master: comment message char asian block, Char: ' . $blacklist_asian_char_current . ', Ip: ' . $spammasterip . ', Email: ' . $spammasteremail . ', Message: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
                  ])->execute();
                  $formstate->setErrorByName('mail', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
                  $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
                  $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
                  throw new AccessDeniedHttpException();
                }
              }
            }
            // Check arabic.
            if ($spammaster_comment_arabic != 0) {
              $blacklist_arabic_char_string = $this->state->get('spammaster.extra_arabic_char');
              $blacklist_arabic_char_array = explode(',', $blacklist_arabic_char_string);
              $blacklist_arabic_char_size = count($blacklist_arabic_char_array);
              // Analyze list.
              for ($i = 0; $i < $blacklist_arabic_char_size; $i++) {
                $blacklist_arabic_char_current = trim($blacklist_arabic_char_array[$i]);
                // Check list.
                if (stripos($result_comment_content_clean, $blacklist_arabic_char_current) !== FALSE) {
                  // Insert ip into buffer db.
                  $this->connection->insert('spammaster_threats')->fields([
                    'date' => $spammaster_date,
                    'threat' => $spammasterip,
                  ])->execute();
                  // Post data.
                  $client->post(base64_decode($spam_master_leaning_url), [
                    'form_params' => [
                      'blog_license_key' => $spammaster_license,
                      'blog_threat_ip' => $blog_threat_ip,
                      'blog_threat_type' => $blog_threat_type,
                      'blog_threat_email' => $spammasteremail,
                      'blog_threat_content' => 'Arabic Char: ' . $blacklist_arabic_char_current . 'Text: ' . $result_comment_content_clean,
                      'blog_threat_agent' => $spammasteragent,
                      'blog_web_adress' => $address,
                      'blog_server_ip' => $blog_server_ip,
                    ],
                  ]);
                  // Log char block.
                  $this->connection->insert('spammaster_keys')->fields([
                    'date' => $spammaster_date,
                    'spamkey' => 'spammaster-comment',
                    'spamvalue' => 'Spam Master: comment message char arabic block, Char: ' . $blacklist_arabic_char_current . ', Ip: ' . $spammasterip . ', Email: ' . $spammasteremail . ', Message: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
                  ])->execute();
                  $formstate->setErrorByName('mail', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
                  $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
                  $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
                  throw new AccessDeniedHttpException();
                }
              }
            }
            // Check spam.
            if ($spammaster_comment_spam != 0) {
              $blacklist_spam_char_string = $this->state->get('spammaster.extra_spam_char');
              $blacklist_spam_char_array = explode(',', $blacklist_spam_char_string);
              $blacklist_spam_char_size = count($blacklist_spam_char_array);
              // Analyze list.
              for ($i = 0; $i < $blacklist_spam_char_size; $i++) {
                $blacklist_spam_char_current = trim($blacklist_spam_char_array[$i]);
                // Check list.
                if (stripos($result_comment_content_clean, $blacklist_spam_char_current) !== FALSE) {
                  // Insert ip into buffer db.
                  $this->connection->insert('spammaster_threats')->fields([
                    'date' => $spammaster_date,
                    'threat' => $spammasterip,
                  ])->execute();
                  // Post data.
                  $client->post(base64_decode($spam_master_leaning_url), [
                    'form_params' => [
                      'blog_license_key' => $spammaster_license,
                      'blog_threat_ip' => $blog_threat_ip,
                      'blog_threat_type' => $blog_threat_type,
                      'blog_threat_email' => $spammasteremail,
                      'blog_threat_content' => 'Spam Char: ' . $blacklist_spam_char_current . 'Text: ' . $result_comment_content_clean,
                      'blog_threat_agent' => $spammasteragent,
                      'blog_web_adress' => $address,
                      'blog_server_ip' => $blog_server_ip,
                    ],
                  ]);
                  // Log char block.
                  $this->connection->insert('spammaster_keys')->fields([
                    'date' => $spammaster_date,
                    'spamkey' => 'spammaster-comment',
                    'spamvalue' => 'Spam Master: comment message char spam block, Char: ' . $blacklist_spam_char_current . ', Ip: ' . $spammasterip . ', Email: ' . $spammasteremail . ', Message: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
                  ])->execute();
                  $formstate->setErrorByName('mail', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
                  $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
                  $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
                  throw new AccessDeniedHttpException();
                }
              }
            }
          }
          // Web api check, create data to be posted.
          $blog_threat_type = 'comment';
          $blog_web_address = $this->requestStack->getCurrentRequest()->getHost();
          $address_unclean = $blog_web_address;
          $address = preg_replace('#^https?://#', '', $address_unclean);
          $blog_server_ip = $_SERVER['SERVER_ADDR'];
          // If empty ip.
          if (empty($blog_server_ip) || $blog_server_ip == '0') {
            @$blog_server_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
          }
          $spam_master_leaning_url = 'aHR0cHM6Ly93d3cuc3BhbW1hc3Rlci5vcmcvd3AtY29udGVudC9wbHVnaW5zL3NwYW0tbWFzdGVyLWFkbWluaXN0cmF0b3IvaW5jbHVkZXMvbGVhcm5pbmcvZ2V0X2xlYXJuX2NvbS5waHA=';
          // Call drupal hhtpclient.
          $client = $this->httpClient;
          // Post data.
          $request = $client->post(base64_decode($spam_master_leaning_url), [
            'form_params' => [
              'blog_license_key' => $spammaster_license,
              'blog_threat_ip' => $blog_threat_ip,
              'blog_threat_type' => $blog_threat_type,
              'blog_threat_email' => $spammasteremail,
              'blog_threat_content' => $result_comment_content_clean,
              'blog_threat_agent' => $spammasteragent,
              'blog_web_adress' => $address,
              'blog_server_ip' => $blog_server_ip,
            ],
          ]);
          // Decode json data.
          $response = json_decode($request->getBody(), TRUE);
          if (empty($response)) {
            // Log clean rbl check.
            $this->connection->insert('spammaster_keys')->fields([
              'date' => $spammaster_date,
              'spamkey' => 'spammaster-comment',
              'spamvalue' => 'Spam Master: comment delivered, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
            ])->execute();
          }
          else {
            // Insert ip into buffer db.
            $this->connection->insert('spammaster_threats')->fields([
              'date' => $spammaster_date,
              'threat' => $spammasterip,
            ])->execute();
            // Check if email is auto created.
            $result_email = substr($spammasteremail, 0, 5);
            if ($result_email != 'drup@') {
              $spammaster_db_email = $this->connection->select('spammaster_threats', 'u');
              $spammaster_db_email->fields('u', ['threat']);
              $spammaster_db_email->where('(threat = :email)', [':email' => $spammasteremail]);
              $spammaster_db_email_result = $spammaster_db_email->execute()->fetchObject();
              if (empty($spammaster_db_email_result)) {
                $this->connection->insert('spammaster_threats')->fields([
                  'date' => $spammaster_date,
                  'threat' => $spammasteremail,
                ])->execute();
              }
            }
            // Web positive, throw error.
            $formstate->setErrorByName('mail', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
            $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
            $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
            $this->connection->insert('spammaster_keys')->fields([
              'date' => $spammaster_date,
              'spamkey' => 'spammaster-comment',
              'spamvalue' => 'Spam Master: comment rbl block, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_clean . ', Agent: ' . $spammasteragent,
            ])->execute();
            throw new AccessDeniedHttpException();
          }
        }
      }
    }
  }

}
