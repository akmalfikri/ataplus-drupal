<?php

/**
 * @file
 * Contains investment.page.inc.
 *
 * Page callback for Investment entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Investment templates.
 *
 * Default template: investment.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_investment(array &$variables) {
  // Fetch Investment Entity Object.
  $investment = $variables['elements']['#investment'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
