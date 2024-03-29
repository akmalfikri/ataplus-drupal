<?php

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'html_element' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "html_element",
 *   label = @Translation("HTML element"),
 *   description = @Translation("This fieldgroup renders the inner content in a HTML element with classes and attributes."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class HtmlElement extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    // Keep using preRender parent for BC.
    parent::preRender($element, $processed_object);

    $element_attributes = new Attribute();

    if ($this->getSetting('attributes')) {

      // This regex split the attributes string so that we can pass that
      // later to drupal_attributes().
      preg_match_all('/([^\s=]+)="([^"]+)"/', $this->getSetting('attributes'), $matches);

      // Put the attribute and the value together.
      foreach ($matches[1] as $key => $attribute) {
        $element_attributes[$attribute] = $matches[2][$key];
      }

    }

    // Add the id to the attributes array.
    if ($this->getSetting('id')) {
      $element_attributes['id'] = Html::getId($this->getSetting('id'));
    }

    // Add the classes to the attributes array.
    $classes = $this->getClasses();
    if (!empty($classes)) {
      if (!isset($element_attributes['class'])) {
        $element_attributes['class'] = [];
      }
      // If user also entered class in the attributes textfield, force it to an array.
      else {
        $element_attributes['class'] = [$element_attributes['class']];
      }
      $element_attributes['class'] = array_merge($classes, $element_attributes['class']->value());
    }

    $element['#effect'] = $this->getSetting('effect');
    $element['#speed'] = $this->getSetting('speed');
    $element['#type'] = 'field_group_html_element';
    $element['#wrapper_element'] = $this->getSetting('element');
    $element['#attributes'] = $element_attributes;
    if ($this->getSetting('show_label')) {
      $element['#title_element'] = $this->getSetting('label_element');
      $element['#title'] = Html::escape($this->t($this->getLabel()));
    }

    if ($this->getSetting('required_fields')) {
      $element['#attributes']['class'][] = 'field-group-html-element';
      $element['#attached']['library'][] = 'field_group/formatter.html_element';
      $element['#attached']['library'][] = 'field_group/core';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    $this->process($element, $rendering_object);

    $form_state = new FormState();
    \Drupal\field_group\Element\HtmlElement::processHtmlElement($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['element'] = [
      '#title' => $this->t('Element'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('element'),
      '#description' => $this->t('E.g. div, section, aside etc.'),
      '#weight' => 1,
    ];

    $form['show_label'] = [
      '#title' => $this->t('Show label'),
      '#type' => 'select',
      '#options' => [0 => $this->t('No'), 1 => $this->t('Yes')],
      '#default_value' => $this->getSetting('show_label'),
      '#weight' => 2,
      '#attributes' => [
        'data-fieldgroup-selector' => 'show_label'
      ],
    ];

    $form['label_element'] = [
      '#title' => $this->t('Label element'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('label_element'),
      '#weight' => 3,
      '#states' => [
        'visible' => [
          ':input[data-fieldgroup-selector="show_label"]' => ['value' => 1],
        ],
      ],
    ];

    if ($this->context == 'form') {
      $form['required_fields'] = [
        '#title' => $this->t('Mark group as required if it contains required fields.'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('required_fields'),
        '#weight' => 4,
      ];
    }

    $form['attributes'] = [
      '#title' => $this->t('Attributes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('attributes'),
      '#description' => $this->t('E.g. name="anchor"'),
      '#weight' => 5,
    ];

    $form['effect'] = [
      '#title' => $this->t('Effect'),
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'collapsible' => $this->t('Collapsible'),
        'blind' => $this->t('Blind')
      ],
      '#default_value' => $this->getSetting('effect'),
      '#weight' => 6,
      '#attributes' => [
        'data-fieldgroup-selector' => 'effect'
      ],
    ];

    $form['speed'] = [
      '#title' => $this->t('Speed'),
      '#type' => 'select',
      '#options' => ['slow' => $this->t('Slow'), 'fast' => $this->t('Fast')],
      '#default_value' => $this->getSetting('speed'),
      '#weight' => 7,
      '#states' => [
        '!visible' => [
          ':input[data-fieldgroup-selector="effect"]' => ['value' => 'none'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();
    $summary[] = $this->t('Element: @element',
      ['@element' => $this->getSetting('element')]
    );

    if ($this->getSetting('show_label')) {
      $summary[] = $this->t('Label element: @element',
        ['@element' => $this->getSetting('label_element')]
      );
    }

    if ($this->getSetting('attributes')) {
      $summary[] = $this->t('Attributes: @attributes',
        ['@attributes' => $this->getSetting('attributes')]
      );
    }

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'element' => 'div',
      'show_label' => 0,
      'label_element' => 'h3',
      'effect' => 'none',
      'speed' => 'fast',
      'attributes' => '',
    ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;

  }

}
