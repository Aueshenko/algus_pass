<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PassEditForm extends FormBase {

  // Метод для получения идентификатора формы.
  public function getFormId() {
    return 'algus_pass_pass_edit_form';
  }

  // Метод для построения формы.
  public function buildForm(array $form, FormStateInterface $form_state, $pass_id = null) {

    // Отключаем кеширование формы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Получаем айди пароля из URL параметра, если он передан.
    if (!$pass_id) {
      $pass_id = $this->getIdFromUrl(\Drupal::request()->getRequestUri());
    }
    $form['#pass_id'] = $pass_id;

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
    }

    // Поле для ввода названия термина.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Название пароля'),
      '#required' => TRUE,
      '#default_value' => isset($name) ? $name : '',
    ];

    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Логин'),
      '#required' => TRUE,
      '#default_value' => isset($login) ? $login : '',
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Пароль'),
      '#required' => TRUE,
      '#default_value' => isset($password) ? $password : '',
    ];

    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ссылка'),
      '#default_value' => isset($url) ? $url : '',
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Описание'),
      '#default_value' => isset($description) ? $description : '',
    ];

    // Добавляем кнопку для отправки формы.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Изменить пароль'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Получаем идентификатор пароля из переменной формы.
    $pass_id = $form['#pass_id'];

    // Загружаем сущность Password Entity по ID.
    $entity_type_manager = \Drupal::entityTypeManager();
    $password_entity = $entity_type_manager->getStorage('password_entity')->load($pass_id);

    // Если пароль получен
    if ($password_entity) {
      // Обновляем значения полей на основе введенных данных в форме.
      $password_entity->set('name', $form_state->getValue('name'));
      $password_entity->set('field_login', $form_state->getValue('login'));
      $password_entity->set('field_password', $form_state->getValue('password'));
      $password_entity->set('field_url', $form_state->getValue('link'));
      $password_entity->set('field_description', $form_state->getValue('description'));

      // Сохраняем обновленную сущность.
      $password_entity->save();

      // Указать URL для перенаправления
      $url = Url::fromUri("internal:/password_entity/$pass_id");
      // Выполнить перенаправление
      $form_state->setRedirectUrl($url);
    }
  }

  //Получаем айди пароля из url параметра
  public function getIdFromUrl($url){

    // Разбиваем URL на части по слэшу
    $parts = explode('/', $url);

    // Получаем последний элемент из массива
    return end($parts);
  }
}
