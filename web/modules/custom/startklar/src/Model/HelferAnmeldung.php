<?php

namespace Drupal\startklar\Model;
use OpenApi\Attributes as OA;
use Drupal\startklar\Validation\AufsichtspersonConstraint;
use Drupal\startklar\Validation\AufsichtspersonLimitConstraint;
use Drupal\startklar\Validation\PersonWithLegalAgeConstraint;
use Drupal\startklar\Validation\SiblingConstraint;
use Drupal\startklar\Validation\TaxonomyReferenceConstraint;
use Drupal\startklar\Validation\UuidReferenceExistsConstraint;
use Drupal\startklar\Validation\UuidsUniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
  required: ['person', 'jobs']
)]
class HelferAnmeldung {
  #[OA\Property(ref: '#/components/schemas/Person', description: "Information about the person", type: "object")]
  /**
   * @Assert\NotBlank()
   * @Assert\Valid()
   * @PersonWithLegalAgeConstraint()
   */
  public Person $person;

  #[OA\Property(description: "Where the person wants to sleep", type: "string", example: 'egal')]
  public ?string $unterbringung;

  #[OA\Property(description: "What jobs the person wants to do", type: "array", items: new OA\Items(description: "ID of the Job. Can be gathered from another endpoint", type: "integer", minItems: 1))]
  /**
   * @var int[] $jobs
   * @TaxonomyReferenceConstraint(vocuabluary="helfer_jobs", array=true)
   * @Assert\Count(min=1)
   */
  public array $jobs;
}
