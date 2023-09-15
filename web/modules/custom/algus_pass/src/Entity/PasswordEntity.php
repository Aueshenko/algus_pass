<?php

namespace Drupal\algus_pass\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Password entity.
 *
 * @ingroup algus_pass
 *
 * @ContentEntityType(
 *   id = "password_entity",
 *   label = @Translation("Password"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\algus_pass\PasswordEntityListBuilder",
 *     "views_data" = "Drupal\algus_pass\Entity\PasswordEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\algus_pass\Form\PasswordEntityForm",
 *       "add" = "Drupal\algus_pass\Form\PasswordEntityForm",
 *       "edit" = "Drupal\algus_pass\Form\PasswordEntityForm",
 *       "delete" = "Drupal\algus_pass\Form\PasswordEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\algus_pass\PasswordEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\algus_pass\PasswordEntityAccessControlHandler",
 *   },
 *   base_table = "password_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer password entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/password_entity/{password_entity}",
 *     "add-form" = "/admin/structure/password_entity/add",
 *     "edit-form" = "/admin/structure/password_entity/{password_entity}/edit",
 *     "delete-form" = "/admin/structure/password_entity/{password_entity}/delete",
 *     "collection" = "/admin/structure/password_entity",
 *   },
 *   field_ui_base_route = "password_entity.settings"
 * )
 */
class PasswordEntity extends ContentEntityBase implements PasswordEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }
  public function getLogin()
  {
    return $this->get('field_login')->value;
  }

  public function setLogin($login)
  {
    $this->set('field_login', $login);
    return $this;
  }

  public function getPassword()
  {
    return $this->get('field_password')->value;
  }

  public function setPassword($password)
  {
    $this->set('field_password', $password);
    return $this;
  }

  public function getUrl()
  {
    return $this->get('field_url')->value;
  }

  public function setUrl($url)
  {
    $this->set('field_url', $url);
    return $this;
  }

  public function getDescription()
  {
    return $this->get('field_description')->value;
  }

  public function setDescription($description)
  {
    $this->set('field_description', $description);
    return $this;
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Password entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Password is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}
