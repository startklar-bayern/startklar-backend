<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

abstract class StartklarConstraintValidatorBase extends ConstraintValidator {

  protected function validateValueAndType($expectedValueClass, $expectedConstraintClass, $value, $constraint): bool {
    if (!($constraint::class === $expectedConstraintClass || is_subclass_of($constraint, $expectedConstraintClass))) {
      throw new UnexpectedTypeException($constraint, $expectedConstraintClass);
    }

    if (null === $value || '' === $value) {
      return FALSE;
    }

    if (!($value::class === $expectedValueClass || is_subclass_of($value, $expectedValueClass))) {
      throw new UnexpectedValueException($value, $expectedValueClass);
    }

    return TRUE;
  }

}
