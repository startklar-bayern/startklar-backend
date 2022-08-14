<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FileReferenceConstraint extends Constraint {
  public string $message = 'No file with uuid "{{ uuid }}" does exist.';
}
