<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\startklar\Model\Anmeldung;

/**
 * GroupService service.
 */
class GroupService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\startklar\Service\PersonService
   */
  private PersonService $personService;

  protected NodeStorageInterface $nodeStorage;

  /**
   * @var \Drupal\startklar\Service\AnreiseService
   */
  private AnreiseService $anreiseService;

  /**
   * Constructs a GroupService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              PersonService              $personService,
                              AnreiseService             $anreiseService) {
    $this->entityTypeManager = $entity_type_manager;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    $this->personService = $personService;
    $this->anreiseService = $anreiseService;
  }

  /**
   * Find a group by it's ID.
   *
   * @param $id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\startklar\Service\NotFoundException
   */
  public function getById($id): ?NodeInterface {

    $result = $this->nodeStorage->getQuery()
      ->condition('type', 'group')
      ->condition('title', $id)
      ->accessCheck(FALSE)
      ->execute();

    if (!$result || count($result) == 0) {
      throw new NotFoundException();
    }

    return $this->nodeStorage->load(reset($result));
  }

  /**
   * Create a new group and send a mail with a link to manage it to the group
   * admin.
   *
   * @param $email
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function new(string $email): NodeInterface {
    $id = $this->generateGroupId();

    $node = $this->createNode($id, $email);

    // TODO: Send mail

    return $node;
  }

  public function update(NodeInterface $group, Anmeldung $anmeldung) {
    $this->setGroupValues($group, $anmeldung);
    $group->save();

    // TODO: Update anmeldung fields
    // TODO: Create people that are referenced first
    // TODO: Create and update people

    // TODO: Check if people were deleted, delete them
    // TODO: check if peope were added, add them
    // TODO: If this is the first update, send notification mail
    // TODO: Check if file of führungszeugnis has changed, and if so: require a new review
    // TODO: Save the node(s)

  }

  /**
   * Generate a group id that is unique.
   *
   * @return string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function generateGroupId(): string {
    $schlagworte = [
      'Mut',
      'Freude',
      'Gottvertrauen',
      'Verantwortung',
      'Begeisterung',
      'Tatkraft',
    ];

    $isUnused = FALSE;

    do {
      $id = $schlagworte[rand(0, count($schlagworte) - 1)] . '-' . rand(111, 999);

      try {
        $this->getById($id);
      } catch (NotFoundException $e) {
        $isUnused = TRUE;
      }
    } while ($isUnused === FALSE);

    return $id;
  }

  /**
   * Create a group node and save it.
   *
   * @param string $id
   * @param string $email
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNode(string $id, string $email): NodeInterface {
    $node = Node::create([
      'type' => 'group',
      'status' => NodeInterface::NOT_PUBLISHED,
      'title' => $id,
      'field_mail' => $email,
    ]);

    $node->save();

    return $node;
  }

  protected function setGroupValues(NodeInterface &$node, Anmeldung $anmeldung) {
    $node->set('field_name', $anmeldung->name);

    $node->set('field_dv', [
      'target_id' => $anmeldung->dv,
    ]);

    if (!$node->field_anreise->entity) {
      $anreise = $this->anreiseService->new($anmeldung->anreise);
      $node->set('field_anreise', $anreise);
    }
    else {
      /** @var NodeInterface $anreise */
      $anreise = $node->field_anreise->entity;
      $this->anreiseService->update($anreise, $anmeldung->anreise);
    }

    if (!$node->field_leitung->entity) {
      $person = $this->personService->new($anmeldung->leitung);
      $node->set('field_leitung', $person);
    }
    else {
      /** @var NodeInterface $person */
      $person = $node->field_leitung->entity;
      $this->personService->update($person, $anmeldung->leitung);
    }

    foreach ($anmeldung->teilnehmer as $teilnehmer) {
      $person = $this->findPersonByUuid($teilnehmer->id, $node->get('field_teilnehmer'));

      if (!$person) {
        $person = $this->personService->new($teilnehmer);
        $node->field_teilnehmer[] = $person;
      } else {
        $this->personService->update($person, $teilnehmer);
      }
    }
  }

  protected function findPersonByUuid(string $uuid, FieldItemListInterface $fieldItemList): NodeInterface|bool {
    foreach ($fieldItemList as $fieldItem) {
      if ($fieldItem->entity && $fieldItem->entity->label() == $uuid) {
        return $fieldItem->entity;
      }
    }

    return FALSE;
  }

}
