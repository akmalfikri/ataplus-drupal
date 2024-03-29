<?php

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\VerticalTabs;
use Drupal\field_group\Element\HorizontalTabs;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'horizontal_tabs' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "tabs",
 *   label = @Translation("Tabs"),
 *   description = @Translation("This fieldgroup renders child groups in its own tabs wrapper."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Tabs extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    // Keep using preRender parent for BC.
    parent::preRender($element, $processed_object);

    $element += [
      '#prefix' => '<div class=" ' . implode(' ' , $this->getClasses()) . '">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#parents' => [$this->group->group_name],
      '#default_tab' => '',
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    // By default tabs don't have titles but you can override it in the theme.
    if ($this->getLabel()) {
      $element['#title'] = Html::escape($this->getLabel());
    }

    $element += [
      '#type' => $this->getSetting('direction') . '_tabs',
      '#theme_wrappers' => [$this->getSetting('direction') . '_tabs'],
    ];

    // Search for a tab that was marked as open. First one wins.
    foreach (Element::children($element) as $tab_name) {
      if (!empty($element[$tab_name]['#open'])) {
        $element[$this->group->group_name . '__active_tab']['#default_value'] = $tab_name;
        break;
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $this->process($element, $rendering_object);

    if ($this->getSetting('direction') == 'vertical') {
        $form_state = new FormState();
        $complete_form = [];
        $element = VerticalTabs::processVerticalTabs($element, $form_state, $complete_form);
    }
    else {
        $form_state = new FormState();
        $complete_form = [];
        $element = HorizontalTabs::processHorizontalTabs($element, $form_state, $complete_form);
    }

    // Make sure the group has 1 child. This is needed to succeed at form_pre_render_vertical_tabs().
    // Skipping this would force us to move all child groups to this array, making it an un-nestable.
    $element['group']['#groups'][$this->group->group_name] = [0 => []];
    $element['group']['#groups'][$this->group->group_name]['#group_exists'] = TRUE;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['direction'] = [
      '#title' => $this->t('Direction'),
      '#type' => 'select',
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#default_value' => $this->getSetting('direction'),
      '#weight' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();
    $summary[] = $this->t('Direction: @direction',
      ['@direction' => $this->getSetting('direction')]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return [
      'direction' => 'vertical',
    ] + parent::defaultContextSettings($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {

    $classes = parent::getClasses();
    $classes[] = 'field-group-' . $this->group->format_type . '-wrapper';

    return $classes;
  }

}
