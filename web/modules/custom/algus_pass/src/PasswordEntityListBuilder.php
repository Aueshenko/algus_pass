<?php

namespace Drupal\algus_pass;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Password entities.
 *
 * @ingroup algus_pass
 */
class PasswordEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    //$header['id'] = $this->t('Password ID');
    $header['name'] = $this->t('Name');
    $header['login'] = $this->t('Логин');
    $header['password'] = $this->t('Пароль');
    $header['url'] = $this->t('Ссылка');
    $header['description'] = $this->t('Описание');
    $header['folder'] = $this->t('Папка');
    $header['tags'] = $this->t('Теги');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\algus_pass\Entity\PasswordEntity $entity */
    //$row['id'] = $entity->id();
//    $row['name'] = Link::createFromRoute(
//      $entity->label(),
//      'entity.password_entity.edit_form',
//      ['password_entity' => $entity->id()]
//    );
    $row['name'] = $entity->label();
    $row['login'] = $entity->getLogin();
    $row['password'] = $entity->getPassword();
    $row['url'] = $entity->getUrl();
    $row['description'] = $entity->getDescription();
    $row['folder'] = $entity->getFolder();
    $row['tags'] = $entity->getTags();
    return $row + parent::buildRow($entity);
  }

}
