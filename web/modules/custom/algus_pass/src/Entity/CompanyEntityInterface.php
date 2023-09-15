<?php

namespace Drupal\algus_pass\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Company entities.
 *
 * @ingroup algus_pass
 */
interface CompanyEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Company name.
   *
   * @return string
   *   Name of the Company.
   */
  public function getName();

  /**
   * Sets the Company name.
   *
   * @param string $name
   *   The Company name.
   *
   * @return \Drupal\algus_pass\Entity\CompanyEntityInterface
   *   The called Company entity.
   */
  public function setName($name);

  /**
   * Gets the Company creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Company.
   */
  public function getCreatedTime();

  /**
   * Sets the Company creation timestamp.
   *
   * @param int $timestamp
   *   The Company creation timestamp.
   *
   * @return \Drupal\algus_pass\Entity\CompanyEntityInterface
   *   The called Company entity.
   */
  public function setCreatedTime($timestamp);

}
