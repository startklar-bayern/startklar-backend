<?php

namespace Drupal\startklar\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;

/**
 * Requires a field to have a value when the entity is published.
 *
 * @Constraint(
 *   id = "SummaryRequiredWhenPublished",
 *   label = @Translation("Summary required when published", context = "Validation"),
 *   type = "string"
 * )
 */
class SummaryRequiredWhenPublished extends Constraint {
  public $needsValue = '%field summary is required at the time of publication.';
}
