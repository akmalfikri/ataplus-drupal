<?php
/**
 * @file
 */

namespace Drupal\simple_access\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\simple_access\Entity\SimpleAccessProfile;

/**
 * Class SimpleAccessProfiles
 *
 * @FormElement("simple_access_profiles")
 */
class SimpleAccessProfiles extends Checkboxes {

  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $profiles = SimpleAccessProfile::loadMultiple();
    uasort($profiles, [SimpleAccessProfile::class, 'sort']);

    $element['#options'] = array_map(function (SimpleAccessProfile $a) {
      return $a->label();
    }, $profiles);
    $element['#access'] = \Drupal::currentUser()->hasPermission('assign profiles to nodes') || \Drupal::currentUser()->hasPermission('administer nodes');

    return parent::processCheckboxes($element, $form_state, $complete_form);
  }

}
