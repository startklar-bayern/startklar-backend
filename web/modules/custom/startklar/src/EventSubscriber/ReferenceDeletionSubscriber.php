<?php

namespace Drupal\startklar\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Startklar event subscriber.
 */
class ReferenceDeletionSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ReferenceDeletionSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_DELETE => ['onEntityDelete'],
    ];
  }

  public function onEntityDelete(EntityDeleteEvent $event) {
    $entity = $event->getEntity();

    if ($entity instanceof NodeInterface) {
      // Delete anreise nodes when a person or group is deleted.
      if ($entity->hasField('field_anreise') && !$entity->field_anreise->isEmpty()) {
        $entity->field_anreise->entity->delete();
      }

      // Delete person nodes when a group is deleted
      if ($entity->hasField('field_teilnehmer')) {
        foreach ($entity->field_teilnehmer as $fieldItem) {
          if ($fieldItem->entity) {
            $fieldItem->entity->delete();
          }
        }
      }

      // Delete person nodes when a helfer anmeldung is deleted
      if ($entity->hasField('field_person')) {
        if ($entity->field_person->entity) {
          $fieldItem->entity->delete();
        }
      }
    }
  }

}
