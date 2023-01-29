<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Person;
use Drupal\startklar\Validation\Helper\StartklarConstraintValidatorBase;
use Symfony\Component\Validator\Constraint;

class PersonWithLegalAgeConstraintValidator extends StartklarConstraintValidatorBase {

  /**
   * @param Person $value
   * @param \Drupal\startklar\Validation\PersonWithLegalAgeConstraint $constraint
   *
   * @return void
   * @throws \Exception
   */
  public function validate($value, Constraint $constraint): void {
    if (!$this->validateValueAndType(Person::class, PersonWithLegalAgeConstraint::class, $value, $constraint)) {
      return;
    }

    if (!isset($value->geburtsdatum)) {
      return;
    }

    $geburtsdatum = new \DateTime($value->geburtsdatum);
    $eventEndDate = new \DateTime('2023-06-11');
    $minLegalAgeBirthday = $eventEndDate->sub(\DateInterval::createFromDateString('18 years'));

    if ($geburtsdatum > $minLegalAgeBirthday) {
      $this->context->buildViolation($constraint->message)
        ->atPath('geburtsdatum')
        ->addViolation();
    }
  }

}
