<?php

namespace Drupal\algus_pass\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Password entities.
 *
 * @ingroup algus_pass
 */
interface PasswordEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Password name.
   *
   * @return string
   *   Name of the Password.
   */
  public function getName();

  /**
   * Sets the Password name.
   *
   * @param string $name
   *   The Password name.
   *
   * @return \Drupal\algus_pass\Entity\PasswordEntityInterface
   *   The called Password entity.
   */
  public function setName($name);

  /**
   * Gets the Password creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Password.
   */
  public function getCreatedTime();

  /**
   * Sets the Password creation timestamp.
   *
   * @param int $timestamp
   *   The Password creation timestamp.
   *
   * @return \Drupal\algus_pass\Entity\PasswordEntityInterface
   *   The called Password entity.
   */
  public function setCreatedTime($timestamp);
  public function getLogin();
  public function setLogin($login);
  public function getPassword();
  public function setPassword($password);
  public function getUrl();
  public function setUrl($url);
  public function getDescription();
  public function setDescription($description);
  public function getFolder();
  public function setFolder($folder);
  public function getTags();
  public function setTags($tags);
}
