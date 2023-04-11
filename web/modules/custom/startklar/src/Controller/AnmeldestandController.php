<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Startklar routes.
 */
class AnmeldestandController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {
    $nodeStorage = $this->entityTypeManager()->getStorage('node');

    $groups = $nodeStorage->getQuery()
      ->condition('type', 'group')
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(FALSE)
      ->execute();

    $groupUnpublishedCount = $nodeStorage->getQuery()
      ->condition('type', 'group')
      ->condition('status', NodeInterface::NOT_PUBLISHED)
      ->count()
      ->accessCheck(FALSE)
      ->execute();

    $groupCount = count($groups);

    $output = '<strong>Gruppen: </strong> ' . $groupCount . ' ('. $groupUnpublishedCount .' Gruppen noch nicht abgeschlossen)<br><br>';

    $groups = $nodeStorage->loadMultiple($groups);

    $peopleCount = 0;
    foreach ($groups as $group) {
      $peopleCount += count ($group->field_teilnehmer);
    }

    $output .= '<strong>Teilnehmer*innen: </strong> ' . $peopleCount . '<br><br>';

    $helferCount = $nodeStorage->getQuery()
      ->condition('type', 'helfer')
      ->condition('status', NodeInterface::PUBLISHED)
      ->count()
      ->accessCheck(FALSE)
      ->execute();

    $output .= '<strong>Helfer*innen: </strong> ' . $helferCount . '<br><br>';


    $build['content'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    return $build;
  }

}
