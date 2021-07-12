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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class service.
 */
class SpamMasterRecaptchaService {
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
   * The SpamMasterRecaptchaService service.
   *
   * @var \Drupal\spammmaster\SpamMasterRecaptchaService
   */
  protected $spammasterpage;

  /**
   * The SpamMasterRecaptchaService service.
   *
   * @var \Drupal\spammmaster\SpamMasterRecaptchaService
   */
  protected $spammasterip;

  /**
   * The SpamMasterRecaptchaService service.
   *
   * @var \Drupal\spammmaster\SpamMasterRecaptchaService
   */
  protected $spammasteragent;

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
  public function spamMasterRecaptchaCheck($form, $formstate, $spammasterpage, $spammasterip, $spammasteragent) {

    $this->form = $form;
    $this->formstate = $formstate;
    $this->spammasterpage = $spammasterpage;
    $this->spammasterip = $spammasterip;
    $this->spammasteragent = $spammasteragent;
    $spammaster_date = date('Y-m-d H:i:s');
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $this->state->get('spammaster.license_status');
    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    $spammaster_settings_protection = $this->configFactory->getEditable('spammaster.settings_protection');
    $spammaster_block_message = $spammaster_settings_protection->get('spammaster.block_message');
    $blog_threat_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    if ($spammaster_status == 'VALID' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2') {
      // Exempt whitelist from ip only.
      $spammaster_white_query = $this->connection->select('spammaster_white', 'u');
      $spammaster_white_query->fields('u', ['white']);
      $spammaster_white_query->where('(white = :ip)', [':ip' => $spammasterip]);
      $spammaster_white_result = $spammaster_white_query->execute()->fetchObject();
      // White positive, log insert.
      if (!empty($spammaster_white_result)) {
        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-recaptcha',
          'spamvalue' => 'Spam Master: ' . $spammasterpage . ' recaptcha found in whitelist, Ip: ' . $spammasterip . ', Agent: ' . $spammasteragent,
        ])->execute();
      }
      else {
        // Buffer db check.
        $spammaster_spam_buffer_query = $this->connection->select('spammaster_threats', 'u');
        $spammaster_spam_buffer_query->fields('u', ['threat']);
        $spammaster_spam_buffer_query->where('(threat = :ip)', [':ip' => $spammasterip]);
        $spammaster_spam_buffer_result = $spammaster_spam_buffer_query->execute()->fetchObject();
        // Buffer db positive, throw error, log insert.
        if (!empty($spammaster_spam_buffer_result)) {
          $formstate->setErrorByName('spammaster_recaptcha_field', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
          $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
          $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);

          $this->connection->insert('spammaster_keys')->fields([
            'date' => $spammaster_date,
            'spamkey' => 'spammaster-recaptcha',
            'spamvalue' => 'Spam Master: ' . $spammasterpage . ' recaptcha buffer block, Ip: ' . $spammasterip . ', Agent: ' . $spammasteragent,
          ])->execute();
        }
        // Web api check.
        else {
          // Create data to be posted.
          $blog_threat_type = 'captcha';
          $blog_threat_content = 'Page: ' . $spammasterpage . ' Ip : ' . $spammasterip;
          $spammasteremail = 'drup@' . date('Ymdhis') . '.drup';
          $blog_web_address = $this->requestStack->getCurrentRequest()->getHost();
          $address_unclean = $blog_web_address;
          $address = preg_replace('#^https?://#', '', $address_unclean);
          @$blog_server_ip = $_SERVER['SERVER_ADDR'];
          // If empty ip.
          if (empty($blog_server_ip) || $blog_server_ip == '0') {
            @$blog_server_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
          }
          $spam_master_leaning_url = 'aHR0cHM6Ly93d3cuc3BhbW1hc3Rlci5vcmcvd3AtY29udGVudC9wbHVnaW5zL3NwYW0tbWFzdGVyLWFkbWluaXN0cmF0b3IvaW5jbHVkZXMvbGVhcm5pbmcvZ2V0X2xlYXJuX2NhcHRjaGEucGhw';
          // Call drupal hhtpclient.
          $client = $this->httpClient;
          // Post data.
          $request = $client->post(base64_decode($spam_master_leaning_url), [
            'form_params' => [
              'blog_license_key' => $spammaster_license,
              'blog_threat_ip' => $blog_threat_ip,
              'blog_threat_type' => $blog_threat_type,
              'blog_threat_email' => $spammasteremail,
              'blog_threat_content' => $blog_threat_content,
              'blog_threat_agent' => $spammasteragent,
              'blog_web_adress' => $address,
              'blog_server_ip' => $blog_server_ip,
            ],
          ]);
          // Decode json data.
          $response = json_decode($request->getBody(), TRUE);
          if (empty($response)) {
            $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
            $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
            // Log clean rbl check.
            $this->connection->insert('spammaster_keys')->fields([
              'date' => $spammaster_date,
              'spamkey' => 'spammaster-recaptcha',
              'spamvalue' => 'Spam Master: ' . $spammasterpage . ' recaptcha Ok, Ip: ' . $spammasterip . ', Agent: ' . $spammasteragent,
            ])->execute();
          }
          else {
            // Insert ip into buffer db.
            $this->connection->insert('spammaster_threats')->fields([
              'date' => $spammaster_date,
              'threat' => $spammasterip,
            ])->execute();
            // Web positive, throw error.
            $formstate->setErrorByName('spammaster_recaptcha_field', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));
            $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
            $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);
            $this->connection->insert('spammaster_keys')->fields([
              'date' => $spammaster_date,
              'spamkey' => 'spammaster-recaptcha',
              'spamvalue' => 'Spam Master: ' . $spammasterpage . ' recaptcha rbl block, Ip: ' . $spammasterip . ', Agent: ' . $spammasteragent,
            ])->execute();
          }
        }
      }
    }
  }

}
