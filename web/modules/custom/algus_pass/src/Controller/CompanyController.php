<?php

namespace Drupal\algus_pass\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;

class CompanyController extends ControllerBase
{
  public function taxonomy_folders()
  {
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Здесь указываем машинное имя таксономии, которую хотим отобразить.
    $taxonomy_name = 'taxonomy_folders';
    $curr_user = User::load(\Drupal::currentUser()->id());
    $curr_user_id = \Drupal::currentUser()->id();
    if ($curr_user) {
      $company = $curr_user->get('field_company')->target_id;
    }
    // Загружаем все термины данной таксономии и преобразуем их в массивы.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($taxonomy_name);
    //Получить айди терминов, к которым есть доступ у текущего юзера (из БД)
    $user_terms = \Drupal::database()
      ->select('pass_access', 'p')
      ->fields('p', ['entity_id'])
      ->condition('p.user_id', $curr_user_id)
      ->execute()->fetchAll();
    //Перезаписать в массив просто айдишники
    $user_terms_massive = [];
    foreach ($user_terms as $user_term) {
      $user_terms_massive[] = $user_term->entity_id;
    }
    //Фильтруем термины по компании и по доступу пользователя
    $filtered_terms = [];
    foreach ($terms as $term) {
      // Загружаем термин с помощью его ID, чтобы получить доступ к полю 'field_company'.
      $term_entity = Term::load($term->tid);
      // Проверяем, равно ли поле 'field_company' заданной компании.
      if ($term_entity && in_array($term_entity->id(), $user_terms_massive)) {
        if ($term_entity->get('field_company')->target_id == $company) {
          $filtered_terms[] = $term;
        }
      }
    }

    //Рекурсивный обход вверх и получение родителей термина
    foreach ($filtered_terms as $filtered_term) {
      $this->findParents($filtered_terms, $filtered_term, $terms);
    }

    // Create an index for easy lookups
    $index = [];
    foreach ($filtered_terms as $term) {
      $index[$term->tid] = $term;
    }

    foreach ($filtered_terms as $term) {
      if ($term->depth == 1) {
        $children = $this->buildStructure($term, $index);
        if (empty($children)) {
          $result_massive[$term->name] = [];
        } else {
          $result_massive[$term->name] = $children;
        }
      }
    }

    $content = $result_massive;
    return $build[] = [
      '#theme' => 'pass_list',
      '#content' => $content
    ];
  }

  //Рекурсивно строим массив перед твиг со вложенностью
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

  //Рекурсивный обход вверх чтобы записать всех родителей и вывести полную структуру
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
