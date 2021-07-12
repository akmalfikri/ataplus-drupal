<?php

namespace Drupal\spammaster\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides 'Firewall Status' Block.
 *
 * @Block(
 *   id = "heads_up",
 *   admin_label = @Translation("Spam Master Heads Up"),
 * )
 */
class SpamMasterHeadsUpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $requestStack, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Images url.
    $image_path = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/modules/spammaster/images/';
    $image_check = $image_path . 'check-safe.png';
    $image_pass = $image_path . 'check-pass.png';
    $image_lock = 'check-lock.png';
    $image_inactive = 'check-inactive.png';
    // Get module settings.
    $protection_engine_version = $this->state->get('spammaster.version');
    $protection_license_protection = number_format($this->state->get('spammaster.license_protection'));
    // Check for SSL.
    if (isset($_SERVER["HTTPS"])) {
      $spam_ssl_text = 'Secure Encrypted Website';
      $spam_ssl_image = $image_path . $image_lock;
    }
    else {
      $spam_ssl_text = 'SSL No Encryption';
      $spam_ssl_image = $image_path . $image_inactive;
    }

    return [
      '#theme' => 'heads_up',
      '#type' => 'block',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spammaster_table_head' => $this->t('Safe Website'),
      '#image_check' => $image_check,
      '#image_pass' => $image_pass,
      '#protection_engine_version_text' => $this->t('Protection Engine:'),
      '#protection_engine_version' => $protection_engine_version,
      '#protection_license_protection_text' => $this->t('Protected:'),
      '#protection_license_protection' => $protection_license_protection,
      '#protection_license_protection_end' => $this->t('Threats'),
      '#protection_scan_text' => $this->t('Active Scan'),
      '#protection_firewall_text' => $this->t('Active Firewall'),
      '#spam_ssl_image' => $spam_ssl_image,
      '#spam_ssl_text' => $spam_ssl_text,
      '#spammaster_table_footer' => $this->t('<a href="@spammaster_url">Protected by Spam Master</a>', ['@spammaster_url' => 'https://www.spammaster.org/']),
    ];

  }

}
