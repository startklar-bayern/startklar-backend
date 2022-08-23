<?php

namespace Drupal\startklar\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

class FileService {

  protected EntityStorageInterface $nodeStorage;

  protected EntityTypeManagerInterface $entityTypeManager;

  protected UuidInterface $uuid;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuid = $uuid;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * @param string $id
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\startklar\Service\NotFoundException
   */
  public function get(string $id): NodeInterface {
    $nodes = $this->nodeStorage->loadByProperties([
      'type' => 'datei',
      'title' => $id,
    ]);

    if (count($nodes) == 0) {
      throw new NotFoundException();
    } else {
      return reset($nodes);
    }

  }

}
