<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\Validation\AufsichtspersonConstraint;
use Drupal\startklar\Validation\FuehrungszeugnisConstraint;
use Drupal\startklar\Validation\PersonWithLegalAgeConstraint;
use Drupal\startklar\Validation\SiblingConstraint;
use Drupal\startklar\Validation\TaxonomyReferenceConstraint;
use Drupal\startklar\Validation\UuidReferenceExistsConstraint;
use Drupal\startklar\Validation\UuidsUniqueConstraint;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
  required: ['name', 'dv', 'anreise', 'leitung', 'jugendschutzgesetz_akzeptiert']
)]
/**
 * @UuidsUniqueConstraint()
 * @UuidReferenceExistsConstraint()
 * @SiblingConstraint()
 * @AufsichtspersonConstraint()
 * @FuehrungszeugnisConstraint()
 */
class Anmeldung {

  #[OA\Property(description: "The name of the group", format: "string", example: "Kolpingjugend Dietfurt")]
  /**
   * @Assert\NotBlank()
   */
  public string $name;

  #[OA\Property(description: "ID of the Diözesanverband. Can be gathered from another endpoint.", format: "int64")]
  /**
   * @Assert\NotBlank()
   * @TaxonomyReferenceConstraint(vocuabluary="dvs")
   */
  public int $dv;

  #[OA\Property(ref: '#/components/schemas/Anreise', description: "Information about how and when the group will come to the event", type: "object")]
  /**
   * @Assert\NotBlank()
   * @Assert\Valid()
   */
  public Anreise $anreise;

  #[OA\Property(ref: '#/components/schemas/Person', description: "Information about the person who is leading the group", type: "object")]
  /**
   * @Assert\NotBlank()
   * @Assert\Valid()
   * @PersonWithLegalAgeConstraint()
   */
  public Person $leitung;

  #[OA\Property(description: "Information about the other people in the group", type: "array", items: new OA\Items(ref: '#/components/schemas/Person'))]
  /**
   * @var Person[] $teilnehmer
   *
   * @Assert\Valid()
   */
  public array $teilnehmer;

  #[OA\Property(description: "If the group leader has accepted that he*she will enforce the Jugenschutzgesetz. Has to be true", type: "boolean")]
  /**
   * @Assert\NotNull()
   * @Assert\IsTrue()
   */
  public bool $jugendschutzgesetz_akzeptiert;

}
