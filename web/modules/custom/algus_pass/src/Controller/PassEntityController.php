<?php

namespace Drupal\algus_pass\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Sunra\PhpSimple\HtmlDomParser;


class PassEntityController extends ControllerBase {

  public function PassEntityPage() {

    // Отключаем кеширование страницы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    //Получить id пароля из url
    $current_url = \Drupal::request()->getRequestUri();
    $parts = explode('/', $current_url);
    $pass_id = end($parts);

    // Получить сервис Entity Type Manager.
    $entity_type_manager = \Drupal::entityTypeManager();

    // Загрузить кастомную сущность Password Entity по ID.
    $password_entity = $entity_type_manager->getStorage('password_entity')->load($pass_id);

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

      // Создать массив данных для передачи в шаблон Twig.
      $content = [
        'id' => $pass_id,
        'name' => $name,
        'login' => $login,
        'password' => $password,
        'url' => $url,
        'description' => $description,
        'icon' => $icon_url,
      ];

      // Передать массив данных в шаблон Twig.
      return [
        '#theme' => 'pass_entity',
        '#content' => $content,
      ];
    }
  }
}
