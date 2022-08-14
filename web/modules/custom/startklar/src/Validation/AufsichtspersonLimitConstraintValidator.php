<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Validation\Helper\PersonValidatorBase;
use Symfony\Component\Validator\Constraint;

class AufsichtspersonLimitConstraintValidator extends PersonValidatorBase {

  /**
   * @param Anmeldung $value
   * @param \Drupal\startklar\Validation\AufsichtspersonLimitConstraint $constraint
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, AufsichtspersonLimitConstraint::class, $value, $constraint)) {
      return;
    }

    $aufsichtspersonen = [];

    foreach ($value->teilnehmer as $person) {
      if ($person->aufsichtsperson) {
        if (!array_key_exists($person->aufsichtsperson, $aufsichtspersonen)) {
          $aufsichtspersonen[$person->aufsichtsperson] = 1;
        } else {
          $aufsichtspersonen[$person->aufsichtsperson]++;
        }
      }
    }

    $aufsichtspersonen = array_filter($aufsichtspersonen, function($value) use ($constraint) {
      return $value > $constraint->max;
    });

    foreach ($aufsichtspersonen as $aufsichtsperson => $count) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ uuid }}', $aufsichtsperson)
        ->setParameter('{{ max }}', $constraint->max)
        ->setParameter('{{ count }}', $count)
        ->addViolation();
    }
  }

}
