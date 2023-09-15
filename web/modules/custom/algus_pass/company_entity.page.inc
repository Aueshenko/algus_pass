<?php

/**
 * @file
 * Contains company_entity.page.inc.
 *
 * Page callback for Company entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Company templates.
 *
 * Default template: company_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_company_entity(array &$variables) {
  // Fetch CompanyEntity Entity Object.
  $company_entity = $variables['elements']['#company_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
