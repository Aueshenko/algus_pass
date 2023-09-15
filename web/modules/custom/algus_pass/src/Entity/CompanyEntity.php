<?php

namespace Drupal\algus_pass\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Company entity.
 *
 * @ingroup algus_pass
 *
 * @ContentEntityType(
 *   id = "company_entity",
 *   label = @Translation("Company"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\algus_pass\CompanyEntityListBuilder",
 *     "views_data" = "Drupal\algus_pass\Entity\CompanyEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\algus_pass\Form\CompanyEntityForm",
 *       "add" = "Drupal\algus_pass\Form\CompanyEntityForm",
 *       "edit" = "Drupal\algus_pass\Form\CompanyEntityForm",
 *       "delete" = "Drupal\algus_pass\Form\CompanyEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\algus_pass\CompanyEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\algus_pass\CompanyEntityAccessControlHandler",
 *   },
 *   base_table = "company_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer company entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/company_entity/{company_entity}",
 *     "add-form" = "/admin/structure/company_entity/add",
 *     "edit-form" = "/admin/structure/company_entity/{company_entity}/edit",
 *     "delete-form" = "/admin/structure/company_entity/{company_entity}/delete",
 *     "collection" = "/admin/structure/company_entity",
 *   },
 *   field_ui_base_route = "company_entity.settings"
 * )
 */
class CompanyEntity extends ContentEntityBase implements CompanyEntityInterface {

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Company entity.'))
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

    $fields['status']->setDescription(t('A boolean indicating whether the Company is published.'))
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
