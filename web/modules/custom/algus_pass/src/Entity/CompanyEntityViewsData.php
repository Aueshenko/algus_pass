<?php

namespace Drupal\algus_pass\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Company entities.
 */
class CompanyEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
