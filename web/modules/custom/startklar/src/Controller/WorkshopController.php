<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\startklar\Model\AG;
use Drupal\startklar\Model\Workshop;
use Drupal\startklar\StartklarHelper;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Startklar routes.
 */
class WorkshopController extends ControllerBase {

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

  #[OA\Get(
    path: "/workshops", description: "Get all workshops", tags: ["Workshops"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Workshop"))
      ),
    ]
  )]
  public function index(): CacheableJsonResponse {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $result = $nodeStorage->getQuery()
      ->condition('type', 'workshop')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('title')
      ->accessCheck(FALSE)
      ->execute();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $nodeStorage->loadMultiple($result);

    $workshops = [];

    $cacheableMetadata = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'tags' => [
          'node_list:workshop',
        ],
      ],
    ]);

    foreach ($nodes as $node) {
      $cacheableMetadata->addCacheableDependency($node);

      $previewImage = $node->get('field_workshop_vorschaubild')[0];

      $workshop = new Workshop();
      $workshop->id = $node->id();
      $workshop->title = $node->label();
      $workshop->summary = $node->get('body')->summary;
      $workshop->body = $node->get('body')->value;

      $workshop->timeslots = [];
      foreach ($node->get('field_zeit') as $fieldItem) {
        $workshop->timeslots[] = $fieldItem->value;
      }

      if ($ort = $node->get('field_workshop_ort')->value) {
        $workshop->location = $ort;
      }

      $workshop->previewImage = StartklarHelper::buildImage($previewImage, 'workshop_preview', $cacheableMetadata, false);

      if (count($node->get('field_workshop_bilder')) > 0) {
        $imageList = [];

        foreach ($node->get('field_workshop_bilder') as $image) {
          $imageList[] = StartklarHelper::buildImage($image, 'workshops', $cacheableMetadata, true);
        }
        $workshop->images = $imageList;
      }

      $workshops[] = $workshop;
    }

    $response = new CacheableJsonResponse($workshops);
    $response->addCacheableDependency($cacheableMetadata);
    return $response;
  }

}
