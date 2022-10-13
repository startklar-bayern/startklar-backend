<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\Person;
use Drupal\startklar\Validation\Helper\PersonValidatorBase;
use Symfony\Component\Validator\Constraint;

class UuidReferenceExistsConstraintValidator extends PersonValidatorBase {

  /**
   * @param Anmeldung $value
   * @param \Drupal\startklar\Validation\UuidReferenceExistsConstraint $constraint
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, UuidReferenceExistsConstraint::class, $value, $constraint)) {
      return;
    }

    $this->validatePerson($constraint, $value, $value->leitung, 'geschwisterkind', 'leitung');
    $this->validatePerson($constraint, $value, $value->leitung, 'aufsichtsperson', 'leitung');

    for ($i = 0; $i < count($value->teilnehmer); $i++) {
      $this->validatePerson($constraint, $value, $value->teilnehmer[$i], 'geschwisterkind', 'teilnehmer[' . $i . ']');
      $this->validatePerson($constraint, $value, $value->teilnehmer[$i], 'aufsichtsperson', 'teilnehmer[' . $i . ']');
    }

  }

  protected function validatePerson(UuidReferenceExistsConstraint $constraint, Anmeldung $anmeldung, Person $person, $fieldName, $path) {
    if (isset($person->{$fieldName})) {
      $referencedPerson = $this->getPersonByUuid($anmeldung, $person->{$fieldName});

      if (!$referencedPerson) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('{{ uuid }}', $person->{$fieldName})
          ->atPath($path . '.' . $fieldName)
          ->addViolation();
      }

      if ($referencedPerson->id == $person->id) {
        $this->context->buildViolation("A person cannot reference itself as " . $fieldName . ".")
          ->atPath($path . '.' . $fieldName)
          ->addViolation();
      }
    }
  }

}
