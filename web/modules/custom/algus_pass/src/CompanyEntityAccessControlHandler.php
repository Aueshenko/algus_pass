<?php

namespace Drupal\algus_pass;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Company entity.
 *
 * @see \Drupal\algus_pass\Entity\CompanyEntity.
 */
class CompanyEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\algus_pass\Entity\CompanyEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished company entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published company entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit company entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete company entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add company entities');
  }


}
