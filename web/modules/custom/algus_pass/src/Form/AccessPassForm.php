<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class AccessPassForm extends FormBase {

  public function getFormId() {
    return 'algus_pass_access_pass_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Отключаем кеширование формы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    //Соотношение доступов(айди) с названиями
    $name_of_access = [
      '1' => 'Только чтение',
      '2' => 'Редактирование',
      '3' => 'Полный доступ'
    ];

    $entity_type = 'node';

    //Получаем айди пароля из url параметра
    $pass_id = $this->getIdFromUrl($_SERVER['REQUEST_URI']);
    $form['h3_current_access'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Текущие доступы'
    ];

    $users = \Drupal::database()
      ->select('pass_access', 'p')
      ->fields('p', ['user_id','access'])
      ->condition('p.entity_type',$entity_type)
      ->condition('p.entity_id', $pass_id)
      ->execute()->fetchAll();

    // Создаем заголовок таблицы.
    $header = [
      'name' => 'Имя',
      'access' => 'Доступ',
    ];
    // Создаем строки таблицы с данными из базы данных.
    $rows = [];
    //Массив для того чтобы исключить этих юзеров(они уже добавлены) из списка для выдачи доступа
    $already_with_access = [];
    foreach ($users as $user) {
      // Получаем имя пользователя по его ID.
      $user_id = $user->user_id;
      $already_with_access[] = $user_id;
      $user_object = User::load($user_id);
      $name = $user_object ? $user_object->getDisplayName() : 'Пользователь не найден';

      $rows[] = [
        'name' => $name,
        'access' => $name_of_access[$user->access],
      ];
    }
    // Добавляем таблицу в массив $build.
    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $form['h3_add_users'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Добавить пользователя'
    ];

    //Выпадающий список пользователей( у которых компания равна компании текущего юзера)
    $curr_user = User::load(\Drupal::currentUser()->id());
    if($curr_user){
      $company_id = $curr_user->get('field_company')->target_id;
    }
    $form['users_list'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Выберите пользователя'),
      '#target_type' => 'user',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'users_from_company', // Имя вашего представления
          'display_name' => 'entity_reference_1', // Имя отображения вашего представления
          'arguments' => [$company_id], // Передайте ваш динамический аргумент здесь
        ],
      ],
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['select_access'] = [
      '#type' => 'select',
      '#title' => 'Выберите доступ',
      '#options' => [
        '1' => 'Чтение',
        '2' => 'Редактирование',
        '3' => 'Полный доступ',
      ],
      '#required' => TRUE, // Если поле обязательное.
    ];

    //Получаем компанию текущего пользователя, чтобы вывести таких же юзеров
    $curr_user = User::load(\Drupal::currentUser()->id());
    if($curr_user){
      $company_id = $curr_user->get('field_company')->target_id;
    }
    // Создаем объект EntityQuery для пользователей.
    $query = \Drupal::entityQuery('user')
      ->condition('status', 1); // Опциональное условие, чтобы выбрать только активных пользователей.
    // Добавляем условие для поля field_company.
    $query->condition('field_company', $company_id);
    // Получаем массив UID пользователей, удовлетворяющих условиям запроса.
    $uids = $query->execute();
    // Загружаем полные объекты пользователей на основе полученных UID.
    $users = User::loadMultiple($uids);
    $names_of_users = [];
    foreach($users as $user){
      if(!in_array($user->id(),$already_with_access)){
        $names_of_users[] = $user->getDisplayName();
      }
    }

    $form['add_users'] = [
      '#theme' => 'item_list',
      '#items' => $names_of_users,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Отправить',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Айди доступа
    $access_id = $form_state->getValue('select_access');
    //Айди пользователя кому выдать доступ
    $user_id = $form_state->getValue('users_list');
    $pass_id = $this->getIdFromUrl($_SERVER['REQUEST_URI']);

    $access = \Drupal::database()
      ->insert('pass_access')
      ->fields([
        'entity_type' => 'node',
        'entity_id' => $pass_id,
        'user_id' => $user_id,
        'access' => $access_id
      ])
      ->execute();
  }
  //Получаем айди пароля из url параметра
  public function getIdFromUrl($url){
    // Разбиваем URL на части по слэшу
    $parts = explode('/', $url);

    // Получаем последний элемент из массива (в данном случае "5")
    $pass_id = end($parts);

    return $pass_id;
  }
}
