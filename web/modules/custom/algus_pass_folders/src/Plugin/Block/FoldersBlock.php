<?php

namespace Drupal\algus_pass_folders\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with simple text.
 *
 * @Block (
 *   id = "algus_pass_folders_block",
 *   admin_label = @Translation("Папки")
 * )
 *
 *
 */
class FoldersBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $currentUser;
  protected $database;
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user,Connection $database,EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  public function build() {
    if($this->currentUser->isAuthenticated()){

      // Задаем машинное имя таксономии, которую хотим отобразить.
      $taxonomy_name = 'taxonomy_folders';

      // Получаем текущего пользователя и его айди.
      $current_user = User::load($this->currentUser->id());
      $current_user_id = $this->currentUser->id();

      // Фильтруем и получаем термины, которые видимы текущему пользователю.
      $filtered_terms = $this->getFilteredTerms($taxonomy_name, $current_user, $current_user_id);

      // Строим структуру терминов и создаем массив для вывода в твиг.
      $result_massive = $this->buildTermStructure($filtered_terms);

      return [
        '#theme' => 'folders_list',
        '#content' => $result_massive,
        '#cache' => ['max-age' => 0],
      ];
    }
  }

  // Метод для фильтрации терминов.
  protected function getFilteredTerms($taxonomy_name, $current_user, $current_user_id) {

    $filtered_terms = [];
    $company = $current_user->get('field_company')->target_id;
    $entity_type = 'term';

    // Загружаем все термины данной таксономии и преобразуем их в массив.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($taxonomy_name);

    // Получаем айди терминов, к которым есть доступ у текущего пользователя из базы данных.
    $user_terms = $this->database
      ->select('pass_access', 'p')
      ->fields('p', ['entity_id'])
      ->condition('p.user_id', $current_user_id)
      ->condition('p.entity_type', $entity_type)
      ->execute()
      ->fetchCol();

    // Фильтруем термины по компании и доступу пользователя.
    foreach ($terms as $term) {
      $term_entity = Term::load($term->tid);
      if ($term_entity && in_array($term_entity->id(), $user_terms) && $term_entity->get('field_company')->target_id == $company) {
        $filtered_terms[] = $term;
      }
    }

    // Рекурсивно обходим термины вверх и получаем их родителей.
    foreach ($filtered_terms as $filtered_term) {
      $this->findParents($filtered_terms, $filtered_term, $terms);
    }

    return $filtered_terms;
  }

  // Метод для построения структуры терминов.
  protected function buildTermStructure($terms) {

    $index = [];
    foreach ($terms as $term) {
      $index[$term->tid] = $term;
    }

    $result_massive = [];

    foreach ($terms as $term) {
      if ($term->depth == 1) {
        $result_massive[$term->name] = [
          'tid' => $term->tid,
          'children' => $this->buildStructure($term->tid, $index)
        ];
      }
    }

    return $result_massive;
  }

  // Метод для рекурсивного построения структуры терминов.
  function buildStructure($tid, $index)
  {

    $structure = [];

    foreach ($index as $subterm) {
      if ($subterm->parents[0] == $tid) {
        $structure[$subterm->name] = [
          'tid' => $subterm->tid,
          'children' => $this->buildStructure($subterm->tid, $index)
        ];
      }
    }

    return $structure;
  }

  // Метод для рекурсивного поиска родителей терминов вверх.
  function findParents(&$filtered_terms, $term, $all_terms)
  {

    if ($term->parents[0] == 0) {
      return;
    }

    $parent_id = $term->parents[0];
    foreach ($all_terms as $t) {
      if ($t->tid == $parent_id) {
        $filtered_terms[] = $t;
        $this->findParents($filtered_terms, $t, $all_terms);
        break;
      }
    }
  }
}
