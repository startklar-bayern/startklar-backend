<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SchutzkonzeptRequiredConstraint extends Constraint {
  public string $message = "This person must select a date for Schutzkonzept";
}
