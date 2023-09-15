<?php

/**
 * @file
 * Contains password_entity.page.inc.
 *
 * Page callback for Password entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Password templates.
 *
 * Default template: password_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_password_entity(array &$variables) {
  // Fetch PasswordEntity Entity Object.
  $password_entity = $variables['elements']['#password_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
