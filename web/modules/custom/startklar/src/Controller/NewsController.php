<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\startklar\Model\NewsArticle;
use Drupal\startklar\StartklarHelper;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Startklar routes.
 */
class NewsController extends ControllerBase {
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
    path: "/news", description: "Get all news articles", tags: ["News"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/NewsArticle"))
      ),
    ]
  )]
  public function index(): JsonResponse {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $result = $nodeStorage->getQuery()
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->execute();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $nodeStorage->loadMultiple($result);

    $newsArticles = [];

    $cacheableMetadata = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'tags' => [
          'node_list:article',
        ],
      ],
    ]);

    foreach ($nodes as $node) {
      $cacheableMetadata->addCacheableDependency($node);

      $previewImage = $node->get('field_preview_image')[0];

      $newsArticle = new NewsArticle();
      $newsArticle->id = $node->id();
      $newsArticle->created = date('c', $node->getCreatedTime());
      $newsArticle->title = $node->label();
      $newsArticle->teaser = $node->get('body')->summary;
      $newsArticle->body = $node->get('body')->value;
      $newsArticle->previewImage = StartklarHelper::buildImage($previewImage, 'news_preview', $cacheableMetadata, false);

      if (count($node->get('field_images')) > 0) {
        $imageList = [];

        foreach ($node->get('field_images') as $image) {
          $imageList[] = StartklarHelper::buildImage($image, 'news', $cacheableMetadata, true);
        }
        $newsArticle->images = $imageList;
      }

      $newsArticles[] = $newsArticle;
    }

    $response = new CacheableJsonResponse($newsArticles);
    $response->addCacheableDependency($cacheableMetadata);
    return $response;
  }
}
