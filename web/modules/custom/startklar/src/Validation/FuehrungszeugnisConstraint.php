<?php

namespace Drupal\startklar\Validation;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FuehrungszeugnisConstraint extends Constraint {
  public string $message = "fuehrungszeugnis is required for this person";

  public function getTargets() {
    return self::CLASS_CONSTRAINT;
  }


}
