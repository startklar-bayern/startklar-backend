<?php

namespace Drupal\startklar\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityPublishedInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SummaryRequiredWhenPublishedValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->context->getRoot()->getValue();

    if (
      $entity instanceof EntityPublishedInterface &&
      $entity->isPublished() &&
      $value->summary == NULL
    ) {
      $this->context->addViolation($constraint->needsValue, [
        '%field' => $value->getFieldDefinition()->getLabel(),
      ]);
    }
  }

}
