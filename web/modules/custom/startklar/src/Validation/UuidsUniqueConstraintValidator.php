<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Validation\Helper\PersonValidatorBase;
use Symfony\Component\Validator\Constraint;

class UuidsUniqueConstraintValidator extends PersonValidatorBase {

  /**
   * @param Anmeldung $value
   * @param \Drupal\startklar\Validation\UuidsUniqueConstraint $constraint
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, UuidsUniqueConstraint::class, $value, $constraint)) {
      return;
    }

    $uuids = [];

    if(isset($value->leitung)) {
      $uuids[$value->leitung->id] = 1;
    }

    foreach ($value->teilnehmer as $person) {
      if (!array_key_exists($person->id, $uuids)) {
        $uuids[$person->id] = 1;
      } else {
        $uuids[$person->id]++;
      }
    }

    $uuids = array_filter($uuids, function($value) {
      return $value !== 1;
    });

    foreach ($uuids as $uuid => $count) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ uuid }}', $uuid)
        ->setParameter('{{ count }}', $count)
        ->addViolation();
    }
  }


}
