<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

#[OA\Schema(
  required: ['mit_gruppe']
)]
/**
 * @Assert\GroupSequenceProvider()
 */
class PersonAnreise extends Anreise implements GroupSequenceProviderInterface {

  #[OA\Property(description: "If the person is coming together with the whole group, or separate.", type: 'boolean', example: 'false')]
  /**
   * @Assert\NotNull()
   */
  public bool $mit_gruppe;

  public function getGroupSequence() {
    return [
      'PersonAnreise',
      isset($this->mit_gruppe) && $this->mit_gruppe ? 'mit_gruppe': 'separat',
    ];
  }

}
