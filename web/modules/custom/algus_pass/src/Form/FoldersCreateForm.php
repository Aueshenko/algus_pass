<?php
namespace Drupal\algus_pass\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FoldersCreateForm extends FormBase {

  protected $database;
  protected $currentUser;
  protected $entityTypeManager;

  public function __construct(Connection $database, AccountInterface $current_user, EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  // Метод для получения идентификатора формы.
  public function getFormId() {
    return 'algus_pass_folders_create_form';
  }

  // Метод для построения формы.
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Отключаем кеширование формы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Поле для ввода названия термина.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Название папки'),
      '#required' => TRUE,
    ];

    // Поле для выбора родительского термина.
    $form['parent'] = [
      '#type' => 'select',
      '#title' => $this->t('Родительская папка (опционально)'),
      '#empty_option' => $this->t('- Нет -'),
      '#options' => $this->getTaxonomyTermOptions(),
    ];

    // Добавляем кнопку для отправки формы.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Создать папку'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Получаем значение поля для названия термина.
    $name = $form_state->getValue('name');

    // Получаем значение поля для выбора родительского термина.
    $parent_tid = $form_state->getValue('parent');

    // Создаем новый термин таксономии.
    $term = Term::create([
      'vid' => 'taxonomy_folders', // Идентификатор таксономии
      'name' => $name,
    ]);

    // Если выбран родительский термин, устанавливаем отношение.
    if (!empty($parent_tid)) {
      $parent_term = Term::load($parent_tid);
      if ($parent_term) {
        $term->set('parent', $parent_term->id());
      }
    }

    // Сохраняем термин.
    $term->save();

    \Drupal::messenger()->addMessage($this->t('Папка "@name" успешно создана.', ['@name' => $name]));

    // Выполняем SQL-запрос для добавления записи в таблицу pass_access.
    $curr_user_id = $this->currentUser->id();
    $access = $this->database
      ->insert('pass_access')
      ->fields([
        'entity_type' => 'term',
        'entity_id' => $term->id(),
        'user_id' => $curr_user_id,
        'access' => 3,
      ])
      ->execute();
  }

  // Получение опций для списка выбора родительского термина.
  private function getTaxonomyTermOptions() {

    $taxonomy_vid = 'taxonomy_folders';
    //Получаем айди компании текущего юзера
    $current_user = User::load($this->currentUser->id());
    if($current_user){
      $company = $current_user->get('field_company')->target_id;
    }

    //Получаем айдишники терминов, которые соответствуют нашей компании(чтобы отфильтровать выпадающий список)
    $ids = $this->database
      ->select('taxonomy_term__field_company', 'f')
      ->fields('f',['entity_id'])
      ->condition('f.field_company_target_id', $company)
      ->execute()->fetchCol();

    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree($taxonomy_vid);

    $options = [];

    //Выводим только те папки, которые принадлежат Компании
    foreach ($terms as $term) {
      if(in_array($term->tid, $ids)){
        $options[$term->tid] = str_repeat('-', $term->depth) . $term->name . ' (' . $term->tid . ')';
      }
    }
    return $options;
  }
}
