<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PassDeleteForm extends FormBase {

  // Метод для получения идентификатора формы.
  public function getFormId() {
    return 'algus_pass_pass_delete_form';
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

    // Вы точно хотите удалить доступ пользователя ?
    $form['p_confirmation'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => "Вы точно хотите удалить пароль?"
    ];

    // Добавляем кнопку для отправки формы.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Удалить пароль'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Получаем идентификатор пароля из переменной формы.
    $pass_id = $form['#pass_id'];

    //Удаляем все записи о доступах из бд к этому паролю
    \Drupal::database()
      ->delete('pass_access')
      ->condition('entity_type', 'node')
      ->condition('entity_id', $pass_id)
      ->execute();

    //Получить айди папки к которой принадлежит пароль
    // Загружаем сущность Password Entity по ID.
    $entity_type_manager = \Drupal::entityTypeManager();
    $password_entity = $entity_type_manager->getStorage('password_entity')->load($pass_id);

    if ($password_entity) {

      // $password_entity не пустая, выполните необходимые действия

      //Получаем айди папки где лежит пароль
      $folder_id = $password_entity->get('field_folder')->target_id;

      //Удаляем сущность
      $password_entity->delete();
    }

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
