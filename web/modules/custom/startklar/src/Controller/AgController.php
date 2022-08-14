<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\startklar\Model\AG;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Startklar routes.
 */
class AgController extends ControllerBase {

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
    path: "/ags", description: "Get all AGs", tags: ["AGs"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/AG"))
      ),
    ]
  )]
  public function index(): CacheableJsonResponse {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $result = $nodeStorage->getQuery()
      ->condition('type', 'ag')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('field_weight')
      ->accessCheck(FALSE)
      ->execute();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $nodeStorage->loadMultiple($result);

    $ags = [];

    $cacheableMetadata = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'tags' => [
          'node_list:ag',
        ],
      ],
    ]);

    foreach ($nodes as $node) {
      $cacheableMetadata->addCacheableDependency($node);

      $ag = new AG();
      $ag->id = $node->id();
      $ag->title = $node->label();
      $ag->body = $node->get('body')->value;
      $ag->icon = $node->get('field_icon')->value;
      $ag->contactName = $node->get('field_contact_name')->value;

      if ($mail = $node->get('field_contact_mail')->value) {
        $ag->contactMail = $mail;
      }

      if ($phone = $node->get('field_contact_phone')->value) {
        $ag->contactPhone = $phone;
      }

      $ags[] = $ag;
    }

    $response = new CacheableJsonResponse($ags);
    $response->addCacheableDependency($cacheableMetadata);
    return $response;
  }

}
