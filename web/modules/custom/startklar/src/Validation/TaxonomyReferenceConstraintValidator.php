<?php

namespace Drupal\startklar\Validation;

use Drupal\startklar\Validation\Helper\StartklarConstraintValidatorBase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class TaxonomyReferenceConstraintValidator extends StartklarConstraintValidatorBase {

  public function validate($value, Constraint $constraint) {
    if (empty($value)) {
      return;
    }

    if (!$constraint instanceof TaxonomyReferenceConstraint) {
      throw new UnexpectedTypeException($constraint, TaxonomyReferenceConstraint::class);
    }

    if (empty($constraint->vocuabluary)) {
      throw new \Exception("Property 'vocabulary' is not set");
    }

    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    if ($constraint->array) {
      if (!is_array($value)) {
        throw new UnexpectedValueException($value, "array");
      }

      foreach ($value as $val) {
        $this->validateItem($termStorage, $val, $constraint);
      }
    } else {
      if (!is_int($value)) {
        throw new UnexpectedValueException($value, "integer");
      }

      $this->validateItem($termStorage, $value, $constraint);
    }
  }

  protected  function validateItem($termStorage, $value, Constraint $constraint) {
    if (null === $value || '' === $value) {
      return;
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $termStorage->load($value);

    if (!$term || $term->bundle() !== $constraint->vocuabluary) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ id }}', $value)
        ->addViolation();
    }
  }

}
