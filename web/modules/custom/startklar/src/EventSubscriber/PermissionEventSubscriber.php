<?php

namespace Drupal\startklar\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\node\NodeGrantDatabaseStorageInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Startklar event subscriber.
 */
class PermissionEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node.grant_storage service.
   *
   * @var \Drupal\node\NodeGrantDatabaseStorageInterface
   */
  protected $nodeGrantStorage;

  /**
   * Constructs a PermissionEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\node\NodeGrantDatabaseStorageInterface $node_grant_storage
   *   The node.grant_storage service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, NodeGrantDatabaseStorageInterface $node_grant_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeGrantStorage = $node_grant_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_UPDATE => ['onEntityUpdate'],
    ];
  }

  public function onEntityUpdate(EntityUpdateEvent $event) {
    if ($event->getEntity() instanceof NodeInterface) {
      /** @var NodeInterface $node */
      $node = $event->getEntity();

      /** @var NodeInterface $originalNode */
      $originalNode = $event->getOriginalEntity();

      /** @var \Drupal\node\NodeAccessControlHandlerInterface $accessControlHandler */
      $accessControlHandler = $this->entityTypeManager->getAccessControlHandler('node');

      if ($node->hasField('field_dv')) {
        foreach ($originalNode->field_teilnehmer as $fieldItem) {
          $person = $fieldItem->entity;

          if ($person) {
            $grants = $accessControlHandler->acquireGrants($person);
            $this->nodeGrantStorage->write($person, $grants);

            $anreise = $person->field_anreise->entity;
            if ($anreise) {
              $grants = $accessControlHandler->acquireGrants($anreise);
              $this->nodeGrantStorage->write($anreise, $grants);
            }
          }
        }

        foreach ($node->field_teilnehmer as $fieldItem) {
          $person = $fieldItem->entity;

          if ($person) {
            $grants = $accessControlHandler->acquireGrants($person);
            $this->nodeGrantStorage->write($person, $grants);

            $anreise = $person->field_anreise->entity;
            if ($anreise) {
              $grants = $accessControlHandler->acquireGrants($anreise);
              $this->nodeGrantStorage->write($anreise, $grants);
            }
          }
        }
      }

      if ($node->hasField('field_anreise')) {
        $anreise = $originalNode->field_anreise->entity;
        if ($anreise) {
          $grants = $accessControlHandler->acquireGrants($anreise);
          $this->nodeGrantStorage->write($anreise, $grants);
        }

        $anreise = $node->field_anreise->entity;
        if ($anreise) {
          $grants = $accessControlHandler->acquireGrants($anreise);
          $this->nodeGrantStorage->write($anreise, $grants);
        }
      }

      $accessControlHandler->resetCache();
    }
  }

}
