<?php

namespace Drupal\algus_pass\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PassListController extends ControllerBase {

  protected $entityTypeManager;
  protected $database;
  protected $currentUser;
  protected $requestStack;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database, AccountInterface $current_user, RequestStack $requestStack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->requestStack = $requestStack;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  public function PassListPage() {

    // Отключаем кеширование страницы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $curr_uid = $this->currentUser->id();

    //Получить id папки из url
    $current_url = $this->requestStack->getCurrentRequest()->getRequestUri();
    $parts = explode('/', $current_url);
    $folder_id = end($parts);

    //Достаём айди паролей принадлежащих этой папке
    $query = $this->entityTypeManager->getStorage('password_entity')->getQuery();
    $query->condition('field_folder', $folder_id);
    $result = $query->execute();

    //Получаем айди паролей, к которым есть доступ у текущего пользователя
    $access_pass_ids = $this->database
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

    //Получаем Доступ юзера к папке, если он не равен 3, значит убираем все кнопки
    $user_access = $this->database
      ->select('pass_access', 'a')
      ->fields('a', ['access'])
      ->condition('a.user_id', $curr_uid)
      ->condition('a.entity_type', 'term')
      ->condition('a.entity_id', $folder_id)
      ->execute()->fetchCol();

    //Отправляем айди папки и максимальный доступ к этой папке в твиг
    $variables = [
      'folder_id' => $folder_id,
      'user_access' => max($user_access)
    ];

    return [
      '#theme' => 'pass_list',
      '#content' => $pass_for_show,
      '#variables' => $variables
    ];
  }

}
