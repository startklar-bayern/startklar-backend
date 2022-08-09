<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UuidsUniqueConstraint extends Constraint {
  public string $message = 'The uuid "{{ uuid }}" is used for {{ count }} people, but should be unique.';

  public function getTargets(): array|string {
    return self::CLASS_CONSTRAINT;
  }
}
