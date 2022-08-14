<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AufsichtspersonLimitConstraint extends Constraint {

  public string $message = "An Aufsichtsperson can be assigned to maximum {{ max }} people. The Aufsichtsperson with uuid \"{{ uuid }}\" is assigned to {{ count }} people";
  public int $max = 5;

  public function getTargets() {
    return self::CLASS_CONSTRAINT;
  }


}
