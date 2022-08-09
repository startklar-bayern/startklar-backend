<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Validation\Helper\StartklarConstraintValidatorBase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class FileReferenceConstraintValidator extends StartklarConstraintValidatorBase {

  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof FileReferenceConstraint) {
      throw new UnexpectedTypeException($constraint, FileReferenceConstraint::class);
    }

    if (null === $value || '' === $value) {
      return;
    }

    if (!is_string($value)) {
      throw new UnexpectedValueException($value, "string");
    }

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    $nodes = $nodeStorage->loadByProperties([
      'type' => 'datei',
      'title' => $value,
    ]);

    if (count($nodes) == 0) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ uuid }}', $value)
        ->addViolation();
    }
  }

}
