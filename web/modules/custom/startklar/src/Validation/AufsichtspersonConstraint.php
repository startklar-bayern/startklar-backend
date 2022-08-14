<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AufsichtspersonConstraint extends Constraint {
  public string $message = "The selected Aufsichtsperson is under 18 years at the event";

  public function getTargets(): array|string {
    return self::CLASS_CONSTRAINT;
  }


}
