<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class AccessFoldersForm extends FormBase {

  // Метод для получения идентификатора формы.
  public function getFormId() {
    return 'algus_pass_access_folders_form';
  }

  // Метод для построения формы.
  public function buildForm(array $form, FormStateInterface $form_state, $pass_id = null) {

    // Отключаем кеширование формы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    //Соотношение доступов(айди) с названиями
    $name_of_access = [
      '1' => 'Чтение',
      '2' => 'Редактирование',
      '3' => 'Полный доступ'
    ];

    //Тип ПАПКА в бд
    $entity_type = 'term';

    // Получаем айди пароля из URL параметра, если он передан.
    if (!$pass_id) {
      $pass_id = $this->getIdFromUrl(\Drupal::request()->getRequestUri());
    }

    // Добавляем заголовок "Текущие доступы".
    $form['h3_current_access'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Текущие доступы'
    ];

    // Запрашиваем пользователей у которых есть доступ к пароля из БД.
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

    // Добавляем таблицу в массив $form.
    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    // Добавляем заголовок "Добавить пользователя".
    $form['h3_add_users'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Добавить пользователя'
    ];

    //Поисковое поле с выпадающим списком пользователей( у которых компания равна компании текущего юзера)
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

    // Выпадающий список доступов для выбора какой доступ выдать.
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

    // Фильтруем пользователей, исключая тех, у кого уже есть доступ.
    foreach($users as $user){
      if(!in_array($user->id(),$already_with_access)){
        $names_of_users[] = $user->getDisplayName();
      }
    }

    // Добавляем список пользователей, которых можно добавить.
    $form['add_users'] = [
      '#theme' => 'item_list',
      '#items' => $names_of_users,
    ];

    // Добавляем кнопку "Отправить".
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Отправить',
    ];

    // Сохраняем идентификатор пароля в форме для использования в submitForm().
    $form['#pass_id'] = $pass_id;

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Получаем значение выбранного доступа из формы.
    $access_id = $form_state->getValue('select_access');

    // Получаем значение выбранного пользователя из формы.
    $user_id = $form_state->getValue('users_list');

    // Получаем идентификатор пароля из переменной формы.
    $pass_id = $form['#pass_id'];

    // Вставляем новую запись в таблицу 'pass_access' в базе данных.
    $access = \Drupal::database()
      ->insert('pass_access')
      ->fields([
        'entity_type' => 'term',
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

    // Получаем последний элемент из массива
    return end($parts);
  }
}
