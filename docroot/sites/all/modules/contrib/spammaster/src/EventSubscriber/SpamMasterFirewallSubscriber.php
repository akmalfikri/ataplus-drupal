<?php

namespace Drupal\spammaster\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class firewall subscriber.
 */
class SpamMasterFirewallSubscriber implements EventSubscriberInterface {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

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
  public function __construct(Connection $connection, RequestStack $requestStack, AccountProxyInterface $currentUser, MessengerInterface $messenger, StateInterface $state) {
    $this->connection = $connection;
    $this->requestStack = $requestStack;
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkForRedirection(GetResponseEvent $event) {

    $spammaster_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $spammaster_date = date("Y-m-d H:i:s");
    // Exempt whitelist.
    $spammaster_white_query = $this->connection->select('spammaster_white', 'u');
    $spammaster_white_query->fields('u', ['white']);
    $spammaster_white_query->where('(white = :ip)', [':ip' => $spammaster_ip]);
    $spammaster_white_result = $spammaster_white_query->execute()->fetchObject();
    if (!empty($spammaster_white_result)) {
    }
    else {
      // White negative, proceed to buffer.
      $spammaster_db_ip = $this->connection->select('spammaster_threats', 'u');
      $spammaster_db_ip->fields('u', ['threat']);
      $spammaster_db_ip->where('(threat = :ip)', [':ip' => $spammaster_ip]);
      $spammaster_db_ip_result = $spammaster_db_ip->execute()->fetchObject();
      $spammaster_anonymous = $this->currentUser->isAnonymous();
      if (!empty($spammaster_db_ip_result) && $spammaster_anonymous) {

        $this->connection->insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-firewall',
          'spamvalue' => 'Spam Master: firewall block, Ip: ' . $spammaster_ip,
        ])->execute();

        $spammaster_total_block_count = $this->state->get('spammaster.total_block_count');
        $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
        $this->state->set('spammaster.total_block_count', $spammaster_total_block_count_1);

        $page = '/firewall';
        $response = new RedirectResponse($page);
        $response->send();

        return;

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];

    return $events;
  }

}
