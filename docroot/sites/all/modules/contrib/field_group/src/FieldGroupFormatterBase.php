<?php

namespace Drupal\field_group;

use Drupal\Core\Field\PluginSettingsBase;

/**
 * Base class for 'Fieldgroup formatter' plugin implementations.
 *
 * @ingroup field_group_formatter
 */
abstract class FieldGroupFormatterBase extends PluginSettingsBase implements FieldGroupFormatterInterface {

  /**
   * The group this formatter needs to render.
   * @var stdClass
   */
  protected $group;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The label display setting.
   *
   * @var string
   */
  protected $label;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * The context mode.
   *
   * @var string
   */
  protected $context;

  /**
   * Constructs a FieldGroupFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param $group
   *   The group object.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   */
  public function __construct($plugin_id, $plugin_definition, $group, array $settings, $label) {
    parent::__construct([], $plugin_id, $plugin_definition);

    $this->group = $group;
    $this->settings = $settings;
    $this->label = $label;
    $this->context = $group->context;
  }

  /**
   * Get the current label.
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = [];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Field group label'),
      '#default_value' => $this->label,
      '#weight' => -5,
    ];

    $form['id'] = [
      '#title' => t('ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('id'),
      '#weight' => 10,
      '#element_validate' => ['field_group_validate_id'],
    ];

    $form['classes'] = [
      '#title' => t('Extra CSS classes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('classes'),
      '#weight' => 11,
      '#element_validate' => ['field_group_validate_css_class'],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    if ($this->getSetting('formatter')) {
      $summary[] = $this->pluginDefinition['label'] . ': ' . $this->getSetting('formatter');
    }

    if ($this->getSetting('id')) {
      $summary[] = $this->t('Id: @id', ['@id' => $this->getSetting('id')]);
    }

    if ($this->getSetting('classes')) {
      $summary[] = \Drupal::translation()->translate('Extra CSS classes: @classes', ['@classes' => $this->getSetting('classes')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::defaultContextSettings('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return [
      'classes' => '',
      'id' => '',
    ];
  }

  /**
   * Get the classes to add to the group.
   */
  protected function getClasses() {

    $classes = [];
    // Add a required-fields class to trigger the js.
    if ($this->getSetting('required_fields')) {
      $classes[] = 'required-fields';
      $classes[] = 'field-group-' . str_replace('_', '-', $this->getBaseId());
    }

    if ($this->getSetting('classes')) {
      $classes = array_merge($classes, explode(' ', trim($this->getSetting('classes'))));
    }

    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    $element['#group_name'] = $this->group->group_name;
    $element['#entity_type'] = $this->group->entity_type;
    $element['#bundle'] = $this->group->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    $element['#group_name'] = $this->group->group_name;
    $element['#entity_type'] = $this->group->entity_type;
    $element['#bundle'] = $this->group->bundle;

    // BC: Call the pre render layer to not break contrib plugins.
    return $this->preRender($element, $processed_object);
  }

}
