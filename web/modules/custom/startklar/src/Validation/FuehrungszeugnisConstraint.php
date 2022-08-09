<?php

namespace Drupal\startklar\Validation;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FuehrungszeugnisConstraint extends Constraint {
  public string $message = "This field is required for this person. Either because the person is a Aufsichtsperson or the Leitung of the group.";

  public function getTargets() {
    return self::CLASS_CONSTRAINT;
  }


}
