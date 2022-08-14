<?php

namespace Drupal\startklar\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\startklar\Model\Anreise;
use Drupal\startklar\Model\PersonAnreise;

class AnreiseService {
  protected EntityTypeManagerInterface $entityTypeManager;

  protected NodeStorageInterface $nodeStorage;

  private UuidInterface $uuid;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuid = $uuid;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  public function new (Anreise $anreise): NodeInterface {
    $node = Node::create([
      'type' => 'anreise',
      'status' => NodeInterface::PUBLISHED,
      'title' => $this->uuid->generate(),
    ]);

    $this->setAnreiseValues($node, $anreise);

    $node->save();

    return $node;
  }

  protected function setAnreiseValues(NodeInterface &$node, Anreise $anreise): void {
    $node->set('field_typ', $anreise->typ);
    $node->set('field_ziel', $anreise->ziel);

    $ankunft = new DrupalDateTime($anreise->ankunft);
    $node->set('field_ankunft', $ankunft->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));

    $abfahrt = new DrupalDateTime($anreise->abfahrt);
    $node->set('field_abfahrt', $abfahrt->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));

    if ($anreise instanceof PersonAnreise) {
      $node->set('field_mit_gruppe', $anreise->mit_gruppe);
    }
  }
}
