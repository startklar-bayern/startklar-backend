<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\StartklarHelper;
use Drupal\startklar\Validation\TaxonomyReferenceConstraint;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

#[OA\Schema(
  required: [
    'id',
    'vorname',
    'nachname',
    'geburtsdatum',
    'geschlecht',
    'strasse',
    'plz',
    'ort',
    'telefon',
    'mail',
    'tshirt_groesse',
    'essen',
    'anreise',
  ]
)]
/**
 * @Assert\GroupSequenceProvider()
 */
class Person implements GroupSequenceProviderInterface {

  #[OA\Property(description: "UUID of the person. For new people: UUID has to be generated in frontend.", format: "uuid")]
  /**
   * @Assert\NotBlank()
   */
  public string $id;

  #[OA\Property(description: "First name", type: "string", example: 'Max')]
  /**
   * @Assert\NotBlank()
   */
  public string $vorname;

  #[OA\Property(description: "First name", type: "string", example: 'Mustermann')]
  /**
   * @Assert\NotBlank()
   */
  public string $nachname;

  #[OA\Property(description: "Day of birth", type: "string", format: 'date')]
  /**
   * @Assert\NotBlank()
   * @Assert\Date()
   * @Assert\LessThanOrEqual("2009-06-08")
   * @Assert\GreaterThanOrEqual("1923-06-08")
   */
  public string $geburtsdatum;

  #[OA\Property(description: "Gender of the person", type: "string", enum: [
    Geschlecht::Male,
    Geschlecht::Female,
    Geschlecht::Diverse,
  ])]
  /**
   * @Assert\NotBlank()
   * @Assert\Choice({Geschlecht::Male, Geschlecht::Female, Geschlecht::Diverse}, message=StartklarHelper::INVALID_CHOICE_MESSAGE)
   */
  public string $geschlecht;

  #[OA\Property(description: "Street address", type: "string", example: "Musterstr. 1")]
  /**
   * @Assert\NotBlank()
   */
  public string $strasse;

  #[OA\Property(description: "Zip code", type: "string", example: "92345")]
  /**
   * @Assert\NotBlank()
   */
  public string $plz;

  #[OA\Property(description: "City", type: "string", example: "Musterstadt")]
  /**
   * @Assert\NotBlank()
   */
  public string $ort;

  #[OA\Property(description: "Phone number. No specific format required", type: "string", example: "01234 / 567 890")]
  /**
   * @Assert\NotBlank()
   */
  public string $telefon;

  #[OA\Property(description: "Mail address", type: "string", format: "email")]
  /**
   * @Assert\NotBlank()
   * @Assert\Email()
   */
  public string $mail;

  #[OA\Property(description: "Phone number of parents. Required if person is underage. No specific format required", type: "string", example: "01234 / 567 890")]
  /**
   * @Assert\NotBlank(groups="underage", message="This value should not be blank for people under 18 years before the end date of the event.")
   */
  public ?string $telefon_eltern;

  #[OA\Property(description: "Mail address of parents. Required if person is underage. ", type: "string", format: "email")]
  /**
   * @Assert\NotBlank(groups="underage", message="This value should not be blank for people under 18 years before the end date of the event.")
   * @Assert\Email()
   */
  public ?string $mail_eltern;

  #[OA\Property(description: "UUID of the person who is taking care of this person. Required if underage.", type: "string", format: "uuid")]
  /**
   * @Assert\NotBlank(groups="underage", message="This value should not be blank for people under 18 years before the end date of the event.")
   */
  public ?string $aufsichtsperson;

  #[OA\Property(description: "T-Shit size of this person", format: "int64")]
  /**
   * @Assert\NotBlank()
   * @TaxonomyReferenceConstraint(vocuabluary="t_shirt_groessen")
   */
  public int $tshirt_groesse;

  #[OA\Property(description: "Preferences for food", type: "string", enum: [
    Essen::Normal,
    Essen::Vegetarisch,
    Essen::Vegan,
  ])]
  /**
   * @Assert\NotBlank()
   * @Assert\Choice({Essen::Normal, Essen::Vegetarisch, Essen::Vegan}, message=StartklarHelper::INVALID_CHOICE_MESSAGE)
   */
  public string $essen;

  #[OA\Property(description: "Preferences for food", type: "string", example: "Nuss-Allergie\\nViel Schokolade")]
  public ?string $essen_anmerkungen;

  #[OA\Property(description: "Things about the person we should know", type: "string", example: "Wespen-Allergie, Schlafwandler...")]
  public ?string $anmerkungen;

  #[OA\Property(description: "UUID of a full paying sibling", type: "string", format: "uuid")]
  /**
   * @Assert\Uuid()
   */
  public ?string $geschwisterkind;

  #[OA\Property(ref: '#/components/schemas/PersonAnreise', description: "Information about how and when the person will come to the event", type: "object")]
  /**
   * @Assert\Valid
   * @Assert\NotBlank
   */
  public PersonAnreise $anreise;

  #[OA\Property(description: "ID of the Schutzkonzept meeting event. Can be gathered from another endpoint.", type: "int64")]
  /**
   * @TaxonomyReferenceConstraint(vocuabluary="termine_schutzkonzept")
   */
  public ?int $termin_schutzkonzept;

  public function getGroupSequence() {
    if (!isset($this->geburtsdatum)) {
      return [];
    }

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
