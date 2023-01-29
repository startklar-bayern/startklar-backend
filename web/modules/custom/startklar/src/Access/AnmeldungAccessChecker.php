<?php

namespace Drupal\startklar\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\startklar\Session\AnmeldungSession;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * @DCG
 * To make use of this access checker add '_anmeldung_access_check: Some value' entry to route
 * definition under requirements section.
 */
class AnmeldungAccessChecker implements AccessInterface {

  public function access(AccountInterface $user, string $id) {
    $account = $user->getAccount();

    if (!$account instanceof AnmeldungSession) {
      return AccessResult::forbidden("You have to be authenticated with a JWT");
    }

    return AccessResult::allowedIf($account->getSubject() === $id);
  }

}
