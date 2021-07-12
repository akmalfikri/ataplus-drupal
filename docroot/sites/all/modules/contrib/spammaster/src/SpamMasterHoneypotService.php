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
class SpamMasterHoneypotService {
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
   * The SpamMasterHoneypotService service.
   *
   * @var \Drupal\spammmaster\SpamMasterHoneypotService
   */
  protected $spammasterip;

  /**
   * The SpamMasterHoneypotService service.
   *
   * @var \Drupal\spammmaster\SpamMasterHoneypotService
   */
  protected $spammasteragent;

  /**
   * The SpamMasterHoneypotService service.
   *
   * @var \Drupal\spammmaster\SpamMasterHoneypotService
   */
  protected $spammasterpage;

  /**
   * The SpamMasterHoneypotService service.
   *
   * @var \Drupal\spammmaster\SpamMasterHoneypotService
   */
  protected $spammasterextrafield1;

  /**
   * The SpamMasterHoneypotService service.
   *
   * @var \Drupal\spammmaster\SpamMasterHoneypotService
   */
  protected $spammasterextrafield2;

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
  public function spamMasterHoneypotCheck($form, $formstate, $spammasterip, $spammasteragent, $spammasterpage, $spammasterextrafield1, $spammasterextrafield2) {

    $this->form = $form;
    $this->formstate = $formstate;
    $this->spammasterip = $spammasterip;
    $this->spammasteragent = $spammasteragent;
    $this->spammasterpage = $spammasterpage;
    $this->spammasterextrafield1 = $spammasterextrafield1;
    $this->spammasterextrafield2 = $spammasterextrafield2;
    $spammaster_date = date('Y-m-d H:i:s');
    $spammaster_settings = $this->configFactory->getEditable('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $this->state->get('spammaster.license_status');
    $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
    $spammaster_settings_protection = $this->configFactory->getEditable('spammaster.settings_protection');
    $spammaster_block_message = $spammaster_settings_protection->get('spammaster.block_message');
    $blog_threat_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    if ($spammaster_status == 'VALID' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2') {
      // No whitelisting needed. Check honeypot fields. Buffer db check.
      $spammaster_spam_buffer_query = $this->connection->select('spammaster_threats', 'u');
      $spammaster_spam_buffer_query->fields('u', ['threat']);
      $spammaster_spam_buffer_query->where('(threat = :ip)', [':ip' => $spammasterip]);
      $spammaster_spam_buffer_result = $spammaster_spam_buffer_query->execute()->fetchObject();
      // Buffer db positive.
      if (empty($spammaster_spam_buffer_result)) {
        $this->connection->insert('spammaster_threats')->fields([
          'date' => $spammaster_date,
          'threat' => $spammasterip,
        ])->execute();

        // Web api check. Create data to be posted to verify rbl listings.
        $blog_threat_type = 'honeypot';
        $blog_threat_content = 'Page: ' . $spammasterpage . ' Field 1: ' . $spammasterextrafield1 . ' Field 2: ' . $spammasterextrafield2;
        $spammasteremail = 'drup@' . date('Ymdhis') . '.drup';
        $blog_web_address = $this->requestStack->getCurrentRequest()->getHost();
        $address_unclean = $blog_web_address;
        $address = preg_replace('#^https?://#', '', $address_unclean);
        @$blog_server_ip = $_SERVER['SERVER_ADDR'];
        // If empty ip.
        if (empty($blog_server_ip) || $blog_server_ip == '0') {
          @$blog_server_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
        }
        $spam_master_leaning_url = 'aHR0cHM6Ly93d3cuc3BhbW1hc3Rlci5vcmcvd3AtY29udGVudC9wbHVnaW5zL3NwYW0tbWFzdGVyLWFkbWluaXN0cmF0b3IvaW5jbHVkZXMvbGVhcm5pbmcvZ2V0X2xlYXJuX2hvbmV5LnBocA==';
        // Call drupal hhtpclient.
        $client = $this->httpClient;
        // Post data.
        $client->post(base64_decode($spam_master_leaning_url), [
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
        // No response needed, this is positive spam.
        $formstate->setErrorByName('spammaster_extra_field_1', $this->t('SPAM MASTER: @block_message', ['@block_message' => $spammaster_block_message]));

        $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
        $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);

        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-honeypot',
          'spamvalue' => 'Spam Master: ' . $spammasterpage . ' honeypot block IP: ' . $spammasterip . ' Field 1: ' . $spammasterextrafield1 . ' Field 2: ' . $spammasterextrafield2 . ', Agent: ' . $spammasteragent,
        ])->execute();
        throw new AccessDeniedHttpException();
      }
    }
  }

}
