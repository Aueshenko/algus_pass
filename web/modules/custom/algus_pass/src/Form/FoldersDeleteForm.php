<?php

namespace Drupal\algus_pass\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FoldersDeleteForm extends FormBase {

  protected $entityTypeManager;
  protected $database;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  public function getFormId() {
    return 'algus_pass_folders_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $folder_id = null) {

    // Отключаем кеширование страницы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Получаем айди папки из URL
    $form['#folder_id'] = $folder_id ?? $this->getIdFromUrl($this->getRequest()->getRequestUri());

    $form['p_confirmation'] = [
      '#markup' => $this->t('Вы точно хотите удалить папку?</br>'),
    ];

    // Добавляем кнопку для отправки формы.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Удалить папку'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $folderId = $form['#folder_id'];

    //Массив айдишников терминов таксономии которые надо удалить(папок)
    $childTerms = $this->findChildTerms($folderId);

    //Массив папок, которые надо удалить из таксономии
    $folders_for_delete = $childTerms['child_terms'];
    //Добавляем текущую папку в массив(потому что она изначально не учитывалась)
    $folders_for_delete[] = $folderId;
    //Массив паролей, которые надо удалить
    $passwords_for_delete = $childTerms['passwords'];

    //Если есть папки на удаление, то заходим в условие
    if($folders_for_delete){

      // Удаляем папки из таксономии.
      foreach ($folders_for_delete as $termId) {
        $term = Term::load($termId);
        if ($term) {
          $term->delete();
        }
      }

      //Удаляем записи из БД
      $this->database
        ->delete('pass_access')
        ->condition('entity_type', 'term')
        ->condition('entity_id', $folders_for_delete, 'IN')
        ->execute();
    }

    //Если есть пароли на удаление, то заходим в условие
    if($passwords_for_delete){

      // Удаляем элементы кастомной сущности password_entity.
      foreach ($passwords_for_delete as $passwordId) {
        $password_entity = $this->entityTypeManager->getStorage('password_entity')->load($passwordId);
        if ($password_entity) {
          $password_entity->delete();
        }
      }

      //Удаляем записи из БД
      $this->database
        ->delete('pass_access')
        ->condition('entity_type', 'node')
        ->condition('entity_id', $passwords_for_delete, 'IN')
        ->execute();
    }

    //Редирект на главную страницу сайта
    $response = new RedirectResponse('/');
    $response->send();
  }

  //Получаем айди папки из url параметра
  public function getIdFromUrl($url){

    // Разбиваем URL на части по слэшу
    $parts = explode('/', $url);

    // Получаем последний элемент из массива
    return end($parts);
  }

  //Функция для нахождения айди всех детей удаляемой папки, детей детей и т.д. до самого нижнего уровня,
  // а также в отдельный массив записываются пароли, которые содержатся в этих папках
  public function findChildTerms($termId) {
    $childTerms = [];
    $passwords = [];

    // Загрузите термин таксономии по его ID.
    $term = Term::load($termId);

    if (!$term) {
      return ['child_terms' => $childTerms, 'passwords' => $passwords];
    }

    // Получите таксономический словарь (vocabulary) для термина.
    $vocabularyId = $term->bundle();
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vocabularyId);

    // Найдите элементы кастомной сущности, связанные с этим термином.
    $passwordEntities = $this->entityTypeManager->getStorage('password_entity')
      ->loadByProperties([
        'field_folder' => $termId,
      ]);

    // Добавьте айдишники элементов кастомной сущности в массив passwords.
    foreach ($passwordEntities as $passwordEntity) {
      $passwords[] = $passwordEntity->id();
    }

    // Получите дочерние термины.
    $childTermIds = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->condition('vid', $vocabularyId)
      ->condition('parent', $term->id())
      ->execute();

    foreach ($childTermIds as $childTermId) {
      $termin = Term::load($childTermId);
      if ($termin) {
        $childTerms[] = $termin->get('tid')->value;
      }

      // Рекурсивно найдите дочерние термины для текущего термина.
      $result = $this->findChildTerms($childTermId);
      $childTerms = array_merge($childTerms, $result['child_terms']);
      $passwords = array_merge($passwords, $result['passwords']);
    }

    // Возвращаем отдельные массивы для терминов и сущностей паролей.
    return ['child_terms' => $childTerms, 'passwords' => $passwords];
  }
}
