<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\Person;
use Symfony\Component\Validator\Constraint;

class SiblingConstraintValidator extends PersonValidatorBase {

  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, SiblingConstraint::class, $value, $constraint)) {
      return;
    }

    $this->validatePerson($constraint, $value, $value->leitung, 'leitung');

    for ($i = 0; $i < count($value->teilnehmer); $i++) {
      $this->validatePerson($constraint, $value, $value->teilnehmer[$i], 'teilnehmer[' . $i . ']');
    }
  }

  protected function validatePerson(SiblingConstraint $constraint, Anmeldung $anmeldung, Person $person, $path) {
    if ($person->geschwisterkind) {
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
