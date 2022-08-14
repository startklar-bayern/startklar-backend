<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TaxonomyReferenceConstraint extends Constraint {
  public string $message = "No entity with id {{ id }} found";

  public string $vocuabluary;
}
