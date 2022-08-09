<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UuidReferenceExistsConstraint extends Constraint {
  public string $message = "There is no person with the uuid {{ uuid }} in this Anmeldung.";

  public function getTargets(): array|string {
    return self::CLASS_CONSTRAINT;
  }
}
