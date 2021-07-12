<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class controller.
 */
class SpamMasterFirewallController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function spamMasterFirewall() {

    // Set firewall image path.
    $firewall_image = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/modules/spammaster/images/spam-master.gif';

    return [
      '#theme' => 'firewall',
      '#type' => 'page',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spam_master_firewall_image' => $firewall_image,
    ];
  }

}
