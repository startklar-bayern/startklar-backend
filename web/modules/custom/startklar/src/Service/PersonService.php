<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\startklar\Model\Person;
use Drupal\startklar\Model\PersonAnreise;

class PersonService {
  protected EntityTypeManagerInterface$entityTypeManager;

  protected AnreiseService $anreiseService;

  protected NodeStorageInterface $nodeStorage;

  protected FileService $fileService;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AnreiseService $anreiseService, FileService $fileService) {
    $this->entityTypeManager = $entity_type_manager;
    $this->anreiseService = $anreiseService;
    $this->fileService = $fileService;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * Find a person by it's ID.
   *
   * @param $id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\startklar\Service\NotFoundException
   */
  public function getById($id): ?\Drupal\Core\Entity\EntityInterface {

    $result = $this->nodeStorage->getQuery()
      ->condition('type', 'person')
      ->condition('title', $id)
      ->accessCheck(FALSE)
      ->execute();

    if (!$result || count($result) == 0) {
      throw new NotFoundException();
    }

    return $this->nodeStorage->load(reset($result));
  }

  /**
   * Create a new person node.
   *
   * @param $email
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function new(Person $person): NodeInterface {
    try {
      $this->getById($person->id);
      throw new \Exception("A person with this UUID is already existing");
    } catch (NotFoundException $ignored) {
      // Person is not already existing. Everything is fine :-)
    }

    $node = Node::create([
      'type' => 'person',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setPersonValues($node, $person);

    $node->save();

    // TODO: Add to sendinblue mailing list

    return $node;
  }

  public function update(NodeInterface &$node, Person $person) {
    $this->setPersonValues($node, $person);
    $node->save();
  }

  public function delete(NodeInterface $node) {
    if ($anreise = $node->field_anreise->entity) {
      $anreise->delete();
    }

    $node->delete();

    // TODO: Remove from sendinblue mailing list (maybe first check if email is used for another person?)
  }

  protected function setPersonValues (NodeInterface &$node, Person $person) {
    $requiredSimpleFields = [
      'title' => 'id',
      'field_vorname' => 'vorname',
      'field_nachname' => 'nachname',
      'field_geburtsdatum' => 'geburtsdatum',
      'field_geschlecht' => 'geschlecht',
      'field_strasse' => 'strasse',
      'field_plz' => 'plz',
      'field_ort' => 'ort',
      'field_telefon' => 'telefon',
      'field_mail' => 'mail',
      'field_essen' => 'essen',
    ];

    foreach ($requiredSimpleFields as $fieldName => $propertyName) {
      $node->set($fieldName, $person->$propertyName);
    }

    $node->set('field_tshirt_groesse', [
      'target_id' => $person->tshirt_groesse,
    ]);

    if (isset($person->termin_schutzkonzept)) {
      $node->set('field_termin_schutzkonzept', [
        'target_id' => $person->termin_schutzkonzept,
      ]);
    } else {
      $node->set('field_termin_schutzkonzept', NULL);
    }

    if (isset($person->telefon_eltern)) {
      $node->set('field_telefon_eltern', $person->telefon_eltern);
    } else {
      $node->set('field_telefon_eltern', NULL);
    }

    if (isset($person->mail_eltern)) {
      $node->set('field_mail_eltern', $person->mail_eltern);
    } else {
      $node->set('field_mail_eltern', NULL);
    }

    if (isset($person->essen_anmerkungen)) {
      $node->set('field_essen_anmerkungen', $person->essen_anmerkungen);
    } else {
      $node->set('field_essen_anmerkungen', NULL);
    }

    if (isset($person->anmerkungen)) {
      $node->set('field_anmerkungen', $person->anmerkungen);
    } else {
      $node->set('field_anmerkungen', NULL);
    }

    if (!$node->field_anreise->entity) {
      $anreise = $this->anreiseService->new($person->anreise);
      $node->set('field_anreise', $anreise);
    } else {
      /** @var NodeInterface $anreise */
      $anreise = $node->field_anreise->entity;
      $this->anreiseService->update($anreise, $person->anreise);
    }

    // Set to null temporarily to ensure all people are created first
    $node->set('field_geschwisterkind', NULL);
    $node->set('field_aufsichtsperson', NULL);
  }

  public function toDto(NodeInterface $entity): Person {
    $person = new Person();
    $person->id = $entity->label();
    $person->vorname = $entity->field_vorname->value;
    $person->nachname = $entity->field_nachname->value;
    $person->geburtsdatum = $entity->field_geburtsdatum->value;
    $person->geschlecht = $entity->field_geschlecht->value;
    $person->strasse = $entity->field_strasse->value;
    $person->plz = $entity->field_plz->value;
    $person->ort = $entity->field_ort->value;
    $person->telefon = $entity->field_telefon->value;
    $person->mail = $entity->field_mail->value;
    if (!$entity->field_telefon_eltern->isEmpty()) {
      $person->telefon_eltern = $entity->field_telefon_eltern->value;
    }

    if (!$entity->field_mail_eltern->isEmpty()) {
      $person->mail_eltern = $entity->field_mail_eltern->value;
    }

    if (!$entity->field_aufsichtsperson->isEmpty()) {
      $person->aufsichtsperson = $entity->field_aufsichtsperson->entity->label();#
    }

    $person->tshirt_groesse = $entity->field_tshirt_groesse->target_id;
    $person->essen = $entity->field_essen->value;

    if (!$entity->field_essen_anmerkungen->isEmpty()) {
      $person->essen_anmerkungen = $entity->field_essen_anmerkungen->value;
    }

    if (!$entity->field_anmerkungen->isEmpty()) {
      $person->anmerkungen = $entity->field_anmerkungen->value;
    }

    if (!$entity->field_geschwisterkind->isEmpty()) {
      $person->geschwisterkind = $entity->field_geschwisterkind->entity->label();
    }

    $person->anreise = $this->anreiseService->toDto($entity->field_anreise->entity, PersonAnreise::class);

    if (!$entity->field_termin_schutzkonzept->isEmpty()) {
      $person->termin_schutzkonzept = $entity->field_termin_schutzkonzept->target_id;
    }

    return $person;
  }

}
