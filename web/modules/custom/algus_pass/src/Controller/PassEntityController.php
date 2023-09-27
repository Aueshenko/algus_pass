<?php

namespace Drupal\algus_pass\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Sunra\PhpSimple\HtmlDomParser;


class PassEntityController extends ControllerBase {

  protected $entityTypeManager;
  protected $requestStack;
  protected $database;
  protected $currentUser;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, Connection $database, AccountInterface $current_user) {
    $this->entityTypeManager = $entityTypeManager;
    $this->requestStack = $requestStack;
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('database'),
      $container->get('current_user')
    );
  }

  public function PassEntityPage() {

    // Отключаем кеширование страницы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $curr_uid = $this->currentUser->id();

    //Получить id пароля из url
    $current_url = $this->requestStack->getCurrentRequest()->getRequestUri();
    $parts = explode('/', $current_url);
    $pass_id = end($parts);

    // Загрузить кастомную сущность Password Entity по ID.
    $password_entity = $this->entityTypeManager->getStorage('password_entity')->load($pass_id);

    //Если пароль получен
    if($password_entity) {
      // Получить основные поля сущности.
      $name = $password_entity->get('name')->value;
      $login = $password_entity->get('field_login')->value;
      $password = $password_entity->get('field_password')->value;
      $url = $password_entity->get('field_url')->value;
      $description = $password_entity->get('field_description')->value;

      //Получаем фавиконку сайта
      $icon_url = !empty($url) ? 'http://www.google.com/s2/favicons?domain=' . urlencode(str_replace("http://", "", $url)) : '';

      //Получаем Доступ юзера к папке, если он не равен 3, значит убираем все кнопки
      $user_access = $this->database
        ->select('pass_access', 'a')
        ->fields('a', ['access'])
        ->condition('a.user_id', $curr_uid)
        ->condition('a.entity_type', 'node')
        ->condition('a.entity_id', $pass_id)
        ->execute()->fetchCol();

      // Создать массив данных для передачи в шаблон Twig.
      $content = [
        'name' => $name,
        'login' => $login,
        'password' => $password,
        'url' => $url,
        'description' => $description,
        'icon' => $icon_url,
      ];

      if($user_access){
        $variables = [
          'user_access' => max($user_access),
          'pass_id' => $pass_id
        ];
      }
      else{
        $variables = [
          'pass_id' => $pass_id
        ];
      }

      // Передать массив данных в шаблон Twig.
      return [
        '#theme' => 'pass_entity',
        '#content' => $content,
        '#variables' => $variables
      ];
    }
  }
}
