<?php

namespace Drupal\startklar\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SiblingConstraint extends Constraint {
  public string $message = 'The person {{ uuid }} has selected the sibling {{ uuid_sibling }} that also has a sibling selected. The selected sibling should be a full paying person and therefore not have a sibling selected.';

  public function getTargets(): array|string {
    return self::CLASS_CONSTRAINT;
  }

}
