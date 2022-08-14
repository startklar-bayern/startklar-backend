<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\StartklarHelper;
use Drupal\startklar\Validation\AnreiseConstraint;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;


/**
 * @Assert\GroupSequenceProvider()
 * @AnreiseConstraint()
 */
#[OA\Schema(
  required: ['typ', 'ziel', 'ankunft', 'abfahrt']
)]
class Anreise implements GroupSequenceProviderInterface {

  #[OA\Property(description: "Will you come by yourself or with your DV.", type: "string", enum: [AnreiseTyp::Selbststaendig, AnreiseTyp::MitDV])]
  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Choice({AnreiseTyp::MitDV, AnreiseTyp::Selbststaendig}, message=StartklarHelper::INVALID_CHOICE_MESSAGE, groups="separat")
   */
  public ?string $typ;

  #[OA\Property(description: "Where will you come to. Backup if no transport by DV possible", type: "string", enum: [AnreiseZiel::Direkt, AnreiseZiel::ZugHilpoltstein, AnreiseZiel::ZugHilpoltstein])]
  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Choice({AnreiseZiel::Direkt, AnreiseZiel::ZugAllersberg, AnreiseZiel::ZugHilpoltstein}, message=StartklarHelper::INVALID_CHOICE_MESSAGE, groups="separat")
   */
  public ?string $ziel;

  #[OA\Property(description: "When will you arrive", type: "string", format: 'date-time', example: '2023-06-08T10:00:00+02:00')]
  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\DateTime(format=\DateTime::RFC3339, groups="separat")
   * @Assert\LessThanOrEqual("2023-06-11T23:59:59+02:00")
   * @Assert\GreaterThanOrEqual("2023-06-08T00:00:00+02:00")
   */
  public ?string $ankunft;

  #[OA\Property(description: "When will you leave", type: "string", format: 'date-time', example: '2023-06-11T13:00:00+02:00')]
  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\DateTime(format=\DateTime::RFC3339, groups="separat")
   * @Assert\LessThanOrEqual("2023-06-11T23:59:59+02:00")
   * @Assert\GreaterThanOrEqual("2023-06-08T00:00:00+02:00")
   */
  public ?string $abfahrt;

  public function getGroupSequence() {
    return [
      'Anreise',
      'separat'
    ];
  }

}
