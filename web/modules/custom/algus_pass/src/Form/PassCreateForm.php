<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;


class PassCreateForm extends FormBase {

  // Метод для получения идентификатора формы.
  public function getFormId() {
    return 'algus_pass_pass_create_form';
  }

  // Метод для построения формы.
  public function buildForm(array $form, FormStateInterface $form_state, $folder_id = null) {

    // Отключаем кеширование формы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Поле для ввода названия термина.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Название пароля'),
      '#required' => TRUE,
    ];
    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Логин'),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Пароль'),
      '#required' => TRUE,
    ];

    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ссылка'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Описание'),
    ];
    // Добавляем кнопку для отправки формы.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Создать пароль'),
    ];
    // Получаем айди пароля из URL параметра, если он передан.
    if (!$folder_id) {
      $folder_id = $this->getIdFromUrl(\Drupal::request()->getRequestUri());
    }
    $form['#folder_id'] = $folder_id;

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // Получаем идентификатор пароля из переменной формы.
    $folder_id = $form['#folder_id'];

    // Создаем новую сущность password_entity.
    $entity = \Drupal::entityTypeManager()->getStorage('password_entity')->create([
      'name' => $values['name'],
      'field_login' => $values['login'],
      'field_password' => $values['password'],
      'field_url' => $values['link'],
      'field_description' => $values['description'],
      'field_folder' => $folder_id
    ]);

    // Сохраняем сущность в базе данных.
    $entity->save();

    // Получаем ID только что созданной сущности.
    $pass_id = $entity->id();
    $user_id = \Drupal::currentUser()->id();
    \Drupal::messenger()->addMessage($this->t('Пароль успешно создан.'));

    // Вставляем новую запись в таблицу 'pass_access' в базе данных.
    $access = \Drupal::database()
      ->insert('pass_access')
      ->fields([
        'entity_type' => 'node',
        'entity_id' => $pass_id,
        'user_id' => $user_id,
        'access' => 3
      ])
      ->execute();

    // Указать URL для перенаправления
    $url = Url::fromUri("internal:/passwords/$folder_id");
    // Выполнить перенаправление
    $form_state->setRedirectUrl($url);
  }

  //Получаем айди пароля из url параметра
  public function getIdFromUrl($url){

    // Разбиваем URL на части по слэшу
    $parts = explode('/', $url);

    // Получаем последний элемент из массива
    return end($parts);
  }
}
