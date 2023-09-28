<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AccessEditForm extends FormBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  // Метод для получения идентификатора формы.
  public function getFormId() {
    return 'algus_pass_access_edit_form';
  }

  // Метод для построения формы.
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (isset($_GET['uid'], $_GET['entity_type'], $_GET['entity_id'])) {

      $uid = $form['#uid'] = $_GET['uid'];
      $entity_id = $form['#entity_id'] = $_GET['entity_id'];
      $entity_type = $form['#entity_type'] = $_GET['entity_type'];

      //Получаем текущий доступ пользователя из БД
      $current_access = $this->database
        ->select('pass_access','p')
        ->fields('p',['access'])
        ->condition('p.user_id', $uid)
        ->condition('p.entity_type', $entity_type)
        ->condition('p.entity_id', $entity_id)
        ->execute()->fetchAssoc();

      $user = User::load($uid);
      if($user){
        $user_name = $user->getDisplayName();
      }
      $form['user_name'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => "Пользователь: <b>$user_name</b>"
      ];

      $form['select_access'] = [
        '#type' => 'select',
        '#title' => t('Измените доступ'),
        '#id' => 'limiter',
        '#default_value' => $current_access,
        '#options' => [
          1 => 'Чтение',
          2 => 'Редактирование',
          3 => 'Полный доступ'
        ]
      ];

      // Добавляем кнопку "Изменить".
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'Изменить',
      ];

      return $form;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    //Написать запром на изменение записи(доступа) в базе данных
    //Изменить запись в бд, где user_id = $user_id и entity_type = $entity_type и entity_id = $entity_id
    $uid = $form['#uid'];
    $entity_type = $form['#entity_type'];
    $entity_id = $form['#entity_id'];
    $new_access = $form_state->getValue('select_access');

    $this->database
      ->update('pass_access')
      ->fields(['access' => $new_access])
      ->condition('user_id', $uid)
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();

    if($entity_type === 'node'){
      // Указать URL для перенаправления
      $url = Url::fromUri("internal:/access/pass/$entity_id");
      // Выполнить перенаправление
      $form_state->setRedirectUrl($url);
    }
    else if($entity_type === 'term'){
      $url = Url::fromUri("internal:/access/folders/$entity_id");
      // Выполнить перенаправление
      $form_state->setRedirectUrl($url);
    }
  }
}
