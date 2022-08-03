<?php

namespace Drupal\startklar\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Anmeldung {

  /**
   * @Assert\NotBlank()
   */
  public string $name;

  /**
   * @Assert\NotBlank()
   */
  public int $dv;

  /**
   * @Assert\NotBlank()
   * @Assert\Valid()
   */
  public Anreise $anreise;

  /**
   * @Assert\NotBlank()
   * @Assert\Valid()
   */
  public Person $leitung;

  /**
   * @var Person[] $teilnehmer
   *
   * @Assert\Valid()
   */
  public array $teilnehmer;

  /**
   * @Assert\NotNull()
   * @Assert\IsTrue()
   */
  public bool $jugendschutzgesetz_akzeptiert;
}
