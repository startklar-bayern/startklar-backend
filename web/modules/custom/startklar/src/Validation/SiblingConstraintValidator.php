<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\Person;
use Drupal\startklar\Validation\Helper\PersonValidatorBase;
use Symfony\Component\Validator\Constraint;

class SiblingConstraintValidator extends PersonValidatorBase {

  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, SiblingConstraint::class, $value, $constraint)) {
      return;
    }

    if (isset($value->leitung)) {
      $this->validatePerson($constraint, $value, $value->leitung, 'leitung');
    }

    if (isset($value->teilnehmer)) {
      for ($i = 0; $i < count($value->teilnehmer); $i++) {
        $this->validatePerson($constraint, $value, $value->teilnehmer[$i], 'teilnehmer[' . $i . ']');
      }
    }
  }

  protected function validatePerson(SiblingConstraint $constraint, Anmeldung $anmeldung, Person $person, $path) {
    if (isset($person->geschwisterkind)) {
      $geschwisterkind = $this->getPersonByUuid($anmeldung, $person->geschwisterkind);

      if ($geschwisterkind->geschwisterkind) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('{{ uuid }}', $person->id)
          ->setParameter('{{ uuid_sibling }}', $geschwisterkind->id)
          ->atPath($path . '.geschwisterkind')
          ->addViolation();
      }
    }
  }

}
