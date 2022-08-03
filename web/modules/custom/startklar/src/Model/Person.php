<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\StartklarHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @Assert\GroupSequenceProvider()
 */
class Person implements GroupSequenceProviderInterface {
  /**
   * @Assert\NotBlank()
   */
  public string $id;

  /**
   * @Assert\NotBlank()
   */
  public string $vorname;

  /**
   * @Assert\NotBlank()
   */
  public string $nachname;

  /**
   * @Assert\NotBlank()
   * @Assert\Date()
   */
  // TODO: min max
  public string $geburtsdatum;

  /**
   * @Assert\NotBlank()
   * @Assert\Choice({Geschlecht::Male, Geschlecht::Female, Geschlecht::Diverse}, message=StartklarHelper::INVALID_CHOICE_MESSAGE)
   */
  public string $geschlecht;

  /**
   * @Assert\NotBlank()
   */
  public string $strasse;

  /**
   * @Assert\NotBlank()
   */
  public string $plz;

  /**
   * @Assert\NotBlank()
   */
  public string $ort;

  /**
   * @Assert\NotBlank()
   */
  public string $telefon;

  /**
   * @Assert\NotBlank()
   * @Assert\Email()
   */
  public string $mail;

  /**
   * @Assert\NotBlank(groups="underage", message="This value should not be blank for people under 18 years before the end date of the event.")
   */
  public string $telefon_eltern;

  /**
   * @Assert\NotBlank(groups="underage", message="This value should not be blank for people under 18 years before the end date of the event.")
   * @Assert\Email()
   */
  public string $mail_eltern;

  /**
   * @Assert\NotBlank(groups="underage", message="This value should not be blank for people under 18 years before the end date of the event.")
   */
  public string $aufsichtsperson;

  /**
   * @Assert\NotBlank()
   * @Assert\Choice({Essen::Normal, Essen::Vegetarisch, Essen::Vegan}, message=StartklarHelper::INVALID_CHOICE_MESSAGE)
   */
  public string $essen;

  public string $essen_anmerkungen;

  // TODO
  public string $geschwisterkind;

  /**
   * @Assert\Valid
   * @Assert\NotBlank
   */
  public PersonAnreise $anreise;

  // TODO
  public string $fuehrungszeugnis;

  // TODO
  public int $termin_schutzkonzept;

  public function getGroupSequence() {
    $geburtsdatum = new \DateTime($this->geburtsdatum);
    $eventEndDate = new \DateTime('2023-06-11');
    $minLegalAgeBirthday = $eventEndDate->sub(\DateInterval::createFromDateString('18 years'));

    $groups = ['Person'];

    if ($geburtsdatum > $minLegalAgeBirthday) {
      $groups[] = 'underage';
    }

    return $groups;
  }

}
