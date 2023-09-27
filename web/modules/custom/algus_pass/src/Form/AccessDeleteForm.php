<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AccessDeleteForm extends FormBase {

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
    return 'algus_pass_access_delete_form';
  }

  // Метод для построения формы.
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (isset($_GET['uid'], $_GET['entity_type'], $_GET['entity_id'])) {

      $uid = $form['#uid'] = $_GET['uid'];
      $form['#entity_id'] = $_GET['entity_id'];
      $form['#entity_type'] = $_GET['entity_type'];

      $user = User::load($uid);
      $username = $user ? $user->getDisplayName() : '';

      // Вы точно хотите удалить доступ пользователя ?
      $form['p_confirmation'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => "Вы точно хотите удалить доступ для пользователя <b>$username</b> ?"
      ];

      // Добавляем кнопку "Удалить".
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'Удалить',
      ];

      return $form;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $uid = $form['#uid'];
    $entity_type = $form['#entity_type'];
    $entity_id = $form['#entity_id'];

    $this->database
      ->delete('pass_access')
      ->condition('user_id', $uid)
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();

    //Перенаправление на страницу с доступами к ПАРОЛЮ/ПАПКЕ
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
