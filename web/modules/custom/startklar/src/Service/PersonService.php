<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\startklar\Model\Person;

class PersonService {
  protected EntityTypeManagerInterface$entityTypeManager;

  protected AnreiseService $anreiseService;

  protected NodeStorageInterface $nodeStorage;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AnreiseService $anreiseService) {
    $this->entityTypeManager = $entity_type_manager;
    $this->anreiseService = $anreiseService;

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

    return $node;
  }

  public function update(NodeInterface &$node, Person $person) {
    $this->setPersonValues($node, $person);
    $node->save();
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

    if (!$node->field_anreise->entity) {
      $anreise = $this->anreiseService->new($person->anreise);
      $node->set('field_anreise', $anreise);
    } else {
      /** @var NodeInterface $anreise */
      $anreise = $node->field_anreise->entity;
      $this->anreiseService->update($anreise, $person->anreise);
    }

    // TODO: fuehrungszeugnis

    // TODO: aufsichtsperson
    // TODO: geschwisterkind
  }

}
