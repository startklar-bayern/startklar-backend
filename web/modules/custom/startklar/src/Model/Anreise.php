<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\StartklarHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @Assert\GroupSequenceProvider()
 */
class Anreise implements GroupSequenceProviderInterface {

  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Choice({AnreiseTyp::MitDV, AnreiseTyp::Selbststaendig}, message=StartklarHelper::INVALID_CHOICE_MESSAGE, groups="separat")
   */
  public string $typ;

  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Choice({AnreiseZiel::Direkt, AnreiseZiel::ZugAllersberg, AnreiseZiel::ZugHilpoltstein}, message=StartklarHelper::INVALID_CHOICE_MESSAGE, groups="separat")
   */
  public string $ziel;

  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Choice({"do", "fr", "sa", "so"}, message=StartklarHelper::INVALID_CHOICE_MESSAGE, groups="separat")
   */
  // TODO: abfahrt nach ankunft
  public string $ankunft_tag;

  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Time(groups="separat")
   */
  public string $ankunft_zeit;

  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Choice({"do", "fr", "sa", "so"}, message=StartklarHelper::INVALID_CHOICE_MESSAGE, groups="separat")
   */
  // TODO: abfahrt nach ankunft
  public string $abfahrt_tag;

  /**
   * @Assert\NotBlank(groups="separat")
   * @Assert\Time(groups="separat")
   */
  public string $abfahrt_zeit;

  public function getGroupSequence() {
    return [
      'Anreise',
      'separat'
    ];
  }

}
