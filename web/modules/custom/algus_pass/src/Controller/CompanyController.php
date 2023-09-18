<?php

namespace Drupal\algus_pass\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;

class CompanyController extends ControllerBase
{
  public function taxonomy_folders()
  {
    // Отключаем кеширование страницы.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Задаем машинное имя таксономии, которую хотим отобразить.
    $taxonomy_name = 'taxonomy_folders';
    // Получаем текущего пользователя и его айди.
    $current_user = User::load(\Drupal::currentUser()->id());
    $current_user_id = \Drupal::currentUser()->id();

    // Фильтруем и получаем термины, которые видимы текущему пользователю.
    $filtered_terms = $this->getFilteredTerms($taxonomy_name, $current_user, $current_user_id);

    // Строим структуру терминов и создаем массив для вывода в твиг.
    $result_massive = $this->buildTermStructure($filtered_terms);

    return [
      '#theme' => 'pass_list',
      '#content' => $result_massive,
    ];
  }

  // Метод для фильтрации терминов.
  protected function getFilteredTerms($taxonomy_name, $current_user, $current_user_id) {
    $filtered_terms = [];
    $company = $current_user->get('field_company')->target_id;

    // Загружаем все термины данной таксономии и преобразуем их в массив.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($taxonomy_name);

    // Получаем айди терминов, к которым есть доступ у текущего пользователя из базы данных.
    $user_terms = \Drupal::database()
      ->select('pass_access', 'p')
      ->fields('p', ['entity_id'])
      ->condition('p.user_id', $current_user_id)
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
        $children = $this->buildStructure($term, $index);
        $result_massive[$term->name] = empty($children) ? [] : $children;
      }
    }

    return $result_massive;
  }
  // Метод для рекурсивного построения структуры терминов.
  function buildStructure($term, $index)
  {
    $structure = [];
    foreach ($index as $subterm) {
      if ($subterm->parents[0] == $term->tid) {
        $children = $this->buildStructure($subterm, $index);
        if (empty($children)) {
          $structure[$subterm->name] = [];
        } else {
          $structure[$subterm->name] = $children;
        }
      }
    }
    return $structure;
  }

  // Метод для рекурсивного поиска родителей терминов вверх.
  function findParents(&$filtered_terms, $term, $all_terms) {
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
