<?php

namespace Drupal\spammaster\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides 'Total Threats Count' Block.
 *
 * @Block(
 *   id = "total_threats_count_block",
 *   admin_label = @Translation("Spam Master Total Threats Count"),
 * )
 */
class SpamMasterTotalCountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get Total Threats Count from module settings.
    $spammaster_total_threats_count = number_format($this->state->get('spammaster.license_protection'));

    return [
      '#theme' => 'total_count',
      '#type' => 'block',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spammaster_total_threats_count' => $spammaster_total_threats_count,
      '#spammaster_total_threats_footer' => $this->t('by <a href="@spammaster_url">Spam Master</a>.', ['@spammaster_url' => 'https://www.spammaster.org/']),
    ];

  }

}
