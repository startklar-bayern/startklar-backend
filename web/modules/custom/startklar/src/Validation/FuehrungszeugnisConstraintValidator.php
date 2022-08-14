<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\Person;
use Drupal\startklar\Validation\Helper\PersonValidatorBase;
use Symfony\Component\Validator\Constraint;

class FuehrungszeugnisConstraintValidator extends PersonValidatorBase {

  /**
   * @param Anmeldung $value
   * @param \Drupal\startklar\Validation\FuehrungszeugnisConstraint $constraint
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, FuehrungszeugnisConstraint::class, $value, $constraint)) {
      return;
    }

    $this->validatePerson($value, $constraint, $value->leitung, 'fuehrungszeugnis', 'leitung');
    $this->validatePerson($value, $constraint, $value->leitung, 'termin_schutzkonzept', 'leitung');

    $aufsichtspersonen = [];

    for ($i = 0; $i < count($value->teilnehmer); $i++) {
      $person = $value->teilnehmer[$i];

      if ($person->aufsichtsperson) {
        $aufsichtspersonen[] = $person->aufsichtsperson;
      }
    }

    for ($i = 0; $i < count($value->teilnehmer); $i++) {
      $person = $value->teilnehmer[$i];

      if (in_array($person->id, $aufsichtspersonen)) {
        $this->validatePerson($value, $constraint, $person, 'fuehrungszeugnis', 'teilnehmer[' . $i . ']');
      }
    }
  }

  private function validatePerson(Anmeldung $anmeldung, FuehrungszeugnisConstraint $constraint, Person $person, string $fieldName, string $path) {
    if (empty($person->{$fieldName})) {
      $this->context->buildViolation($constraint->message)
        ->atPath($path . '.' . $fieldName)
        ->addViolation();
    }
  }

}
