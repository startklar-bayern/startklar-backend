<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\Person;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SiblingConstraintValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof SiblingConstraint) {
      throw new UnexpectedTypeException($constraint, SiblingConstraint::class);
    }

    if (null === $value || '' === $value) {
      return;
    }

    if (!$value instanceof Anmeldung) {
      throw new UnexpectedValueException($value, Anmeldung::class);
    }

    $this->validatePerson($constraint, $value, $value->leitung, 'leitung');

    for ($i = 0; $i < count($value->teilnehmer); $i++) {
      $this->validatePerson($constraint, $value, $value->teilnehmer[$i], 'teilnehmer[' . $i . ']');
    }
  }

  protected function getPersonByUuid(Anmeldung $anmeldung, string $uuid) {
    if ($anmeldung->leitung->id == $uuid) {
      return $anmeldung->leitung;
    }

    foreach ($anmeldung->teilnehmer as $teilnehmer) {
      if ($teilnehmer->id == $uuid) {
        return $teilnehmer;
      }
    }

    return false;
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
