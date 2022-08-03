<?php

namespace Drupal\startklar\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @Assert\GroupSequenceProvider()
 */
class PersonAnreise extends Anreise implements GroupSequenceProviderInterface {

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
