<?php

namespace Drupal\startklar\Validation\Helper;

use Drupal\startklar\Model\Anmeldung;

abstract class PersonValidatorBase extends StartklarConstraintValidatorBase {

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
}
