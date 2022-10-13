<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Validation\Helper\PersonValidatorBase;
use Symfony\Component\Validator\Constraint;

class AufsichtspersonConstraintValidator extends PersonValidatorBase {

  /**
   * @param Anmeldung $value
   * @param \Drupal\startklar\Validation\AufsichtspersonConstraint $constraint
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (!$this->validateValueAndType(Anmeldung::class, AufsichtspersonConstraint::class, $value, $constraint)) {
      return;
    }
    
    if (isset($value->teilnehmer)) {
      for ($i = 0; $i < count($value->teilnehmer); $i++) {
        $person = $value->teilnehmer[$i];

        if ($person->aufsichtsperson) {
          $aufsichtsperson = $this->getPersonByUuid($value, $person->aufsichtsperson);

          $geburtsdatum = new \DateTime($aufsichtsperson->geburtsdatum);
          $eventEndDate = new \DateTime('2023-06-11');
          $minLegalAgeBirthday = $eventEndDate->sub(\DateInterval::createFromDateString('18 years'));

          if ($geburtsdatum > $minLegalAgeBirthday) {
            $this->context->buildViolation($constraint->message)
              ->atPath('teilnehmer[' . $i . '].aufsichtsperson')
              ->addViolation();
          }
        }
      }
    }
  }

}
