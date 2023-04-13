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

  public function update(NodeInterface &$node, Anreise $anreise) {
    $this->setAnreiseValues($node, $anreise);
    $node->save();
  }

  protected function setAnreiseValues(NodeInterface &$node, Anreise $anreise): void {
    if ($anreise instanceof PersonAnreise) {
      $node->set('field_mit_gruppe', $anreise->mit_gruppe);

      // Early return. Do not set other fields, if person comes together with group.
      if ($anreise->mit_gruppe == TRUE) {
        $node->set('field_typ', NULL);
        $node->set('field_ziel', NULL);
        $node->set('field_ankunft', NULL);
        $node->set('field_abfahrt', NULL);

        return;
      }
    }

    if (isset($anreise->typ)) {
      $node->set('field_typ', $anreise->typ);
    } else {
      $node->set('field_typ', NULL);
    }

    if (isset($anreise->ziel)) {
      $node->set('field_ziel', $anreise->ziel);
    } else {
      $node->set('field_ziel', NULL);
    }

    if (isset($anreise->ankunft)) {
      $ankunft = new DrupalDateTime($anreise->ankunft);
      $node->set('field_ankunft', $ankunft->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    } else {
      $node->set('field_ankunft', NULL);
    }

    if (isset($anreise->abfahrt)) {
      $abfahrt = new DrupalDateTime($anreise->abfahrt);
      $node->set('field_abfahrt', $abfahrt->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    } else {
      $node->set('field_abfahrt', NULL);
    }
  }

  public function toDto(NodeInterface $entity, string $class): Anreise|PersonAnreise {
    /** @var PersonAnreise|Anreise $anreise */
    $anreise = new $class;

    if ($anreise instanceof PersonAnreise) {
      $anreise->mit_gruppe = $entity->field_mit_gruppe->value;
    }

    if (!$entity->field_typ->isEmpty()) {
      $anreise->typ = $entity->field_typ->value;
    }

    if (!$entity->field_ziel->isEmpty()) {
      $anreise->ziel = $entity->field_ziel->value;
    }

    if (!$entity->field_ankunft->isEmpty()) {
      $date = new DrupalDateTime($entity->field_ankunft->value, DateTimeItemInterface::STORAGE_TIMEZONE);
      $date->setTimezone(new \DateTimeZone("Europe/Berlin"));
      $anreise->ankunft = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }

    if (!$entity->field_abfahrt->isEmpty()) {
      $date = new DrupalDateTime($entity->field_abfahrt->value, DateTimeItemInterface::STORAGE_TIMEZONE);
      $date->setTimezone(new \DateTimeZone("Europe/Berlin"));
      $anreise->abfahrt = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }

    return $anreise;
  }

}
