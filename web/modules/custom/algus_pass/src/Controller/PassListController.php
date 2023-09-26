<?php

namespace Drupal\algus_pass\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PassListController extends ControllerBase {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function PassListPage() {

    // Отключаем кеширование страницы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $curr_uid = \Drupal::currentUser()->id();

    //Получить id папки из url
    $current_url = \Drupal::request()->getRequestUri();
    $parts = explode('/', $current_url);
    $folder_id = end($parts);

    //Достаём айди паролей принадлежащих этой папке
    $query = $this->entityTypeManager->getStorage('password_entity')->getQuery();
    $query->condition('field_folder', $folder_id);
    $result = $query->execute();

    //Получаем айди паролей, к которым есть доступ у текущего пользователя
    $access_pass_ids = \Drupal::database()
      ->select('pass_access', 'p')
      ->fields('p', ['entity_id'])
      ->condition('p.entity_type', 'node')
      ->condition('p.user_id', $curr_uid)
      ->execute()->fetchCol();

    //Новый массив, которые содержит элементы, которые есть и в первом и во втором массивах
    $passwords_id = array_intersect($access_pass_ids, $result);

    //Получаем объекты паролей, которые находятся в этой папке
    $passwords = $this->entityTypeManager->getStorage('password_entity')->loadMultiple($passwords_id);

    //Массив на вывод в твиге
    $pass_for_show = [];
    // Теперь $passwords содержит список элементов вашей кастомной сущности password_entity.
    foreach ($passwords as $password) {
      $pass_for_show[$password->id()] = $password->label();
    }
    return [
      '#theme' => 'pass_list',
      '#content' => $pass_for_show,
      '#variables' => $folder_id
    ];
  }

}
