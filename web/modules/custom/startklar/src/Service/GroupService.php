<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;

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

  protected NodeStorageInterface $nodeStorage;

  /**
   * Constructs a GroupService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
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
  public function getById($id): ?\Drupal\Core\Entity\EntityInterface {

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
   * Create a new group and send a mail with a link to manage it to the group admin.
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

    $isUnused = false;

    do {
      $id = $schlagworte[rand(0, count($schlagworte) - 1)] . '-' . rand(111, 999);

      try {
        $this->getById($id);
      } catch (NotFoundException $e) {
        $isUnused = true;
      }
    } while($isUnused === false);

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

}
