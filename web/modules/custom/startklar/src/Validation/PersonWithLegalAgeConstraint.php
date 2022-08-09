<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PersonWithLegalAgeConstraint extends Constraint {
  public string $message = "This person is under 18 years at the event";
}
