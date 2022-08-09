<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anreise;
use Drupal\startklar\Validation\Helper\StartklarConstraintValidatorBase;
use Symfony\Component\Validator\Constraint;

class AnreiseConstraintValidator extends StartklarConstraintValidatorBase {

  /**
   * @param Anreise $value
   * @param \Drupal\startklar\Validation\AnreiseConstraint $constraint
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anreise::class, AnreiseConstraint::class, $value, $constraint)) {
      return;
    }

    $ankunft = new \DateTime($value->ankunft);
    $abfahrt = new \DateTime($value->abfahrt);

    if ($abfahrt < $ankunft) {
      $this->context->buildViolation($constraint->message)
        ->addViolation();
    }
  }

}
