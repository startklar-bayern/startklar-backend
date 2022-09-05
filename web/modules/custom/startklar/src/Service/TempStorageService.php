<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeStorageInterface;

class TempStorageService {
  protected EntityTypeManagerInterface $entityTypeManager;

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  public function exists(string $id): bool {
    try {
      $this->findById($id);
      return true;
    } catch (NotFoundException $e) {
      return false;
    }
  }

  /**
   * @throws \Drupal\startklar\Service\NotFoundException
   */
  public function getValue(string $id) {
    $nid = $this->findById($id);
    $node = $this->nodeStorage->load($nid);
    return $node->field_value->value;
  }

  public function setValue(string $id, string $value) {
    try {
      $nid = $this->findById($id);
      $node = $this->nodeStorage->load($nid);
    } catch (NotFoundException $e) {
      $node = $this->nodeStorage->create([
        'title' => $id,
        'type' => 'temp_storage',
      ]);
    }

    $node->set('field_value', $value);
    $node->save();
  }

  /**
   * @throws \Drupal\startklar\Service\NotFoundException
   */
  public function delete(string $id) {
    $nid = $this->findById($id);
    $node = $this->nodeStorage->load($nid);
    $node->delete();
  }

  private function findById(string $id): int {
    $result = $this->nodeStorage->getQuery()
      ->condition('type', 'temp_storage')
      ->condition('title', $id)
      ->accessCheck(FALSE)
      ->execute();

    if (count($result) == 0) {
      throw new NotFoundException('No tempStorage for this id found');
    }

    return reset($result);
  }
}
