<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Person;
use Drupal\startklar\Validation\Helper\StartklarConstraintValidatorBase;
use Symfony\Component\Validator\Constraint;

class SchutzkonzeptRequiredConstraintValidator extends StartklarConstraintValidatorBase {

  /**
   * @param Person $value
   * @param \Drupal\startklar\Validation\SchutzkonzeptRequiredConstraint $constraint
   *
   * @return void
   * @throws \Exception
   */
  public function validate($value, Constraint $constraint): void {
    if (!$this->validateValueAndType(Person::class, SchutzkonzeptRequiredConstraint::class, $value, $constraint)) {
      return;
    }

    if (empty($value->termin_schutzkonzept)) {
      $this->context->buildViolation($constraint->message)
        ->atPath('termin_schutzkonzept')
        ->addViolation();
    }
  }

}
