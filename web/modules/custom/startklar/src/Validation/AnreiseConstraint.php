<?php

namespace Drupal\startklar\Validation;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AnreiseConstraint extends Constraint {
  public string $message = "The abfahrt has to be after ankunft";

  public function getTargets() {
    return self::CLASS_CONSTRAINT;
  }
}
