<?php

namespace Drupal\algus_pass\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;


/**
 * Form controller for Company edit forms.
 *
 * @ingroup algus_pass
 */
class CompanyEntityForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\algus_pass\Entity\CompanyEntity $entity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    //Создаём термин таксономии(компанию)(уровень 0)
    $this->createTaxonomyTerm($entity->label(), $entity->id());

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Company.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Company.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.company_entity.canonical', ['company_entity' => $entity->id()]);
  }
  public function createTaxonomyTerm($term_name, $company_id){

    //Машинное имя таксономии
    $taxonomy_name = 'taxonomy_folders';

    // Создаем новый термин.
    $term = Term::create([
      'vid' => $taxonomy_name, // Машинное имя таксономии.
      'name' => $term_name,
    ]);

    // Сохраняем термин.
    $term->save();
  }
}
