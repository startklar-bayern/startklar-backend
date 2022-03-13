<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\startklar\Model\SharePic;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Startklar routes.
 */
class SharepicController extends ControllerBase {

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
    path: "/sharepics", description: "Get all share pics", tags: ["SharePics"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/SharePic"))
      ),
    ]
  )]
  public function index(): JsonResponse {
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
    $fileUrlGenerator = \Drupal::service('file_url_generator');

    $imageStyleStorage = $this->entityTypeManager->getStorage('image_style');
    /** @var \Drupal\image\Entity\ImageStyle $previewImageStyle */
    $previewImageStyle = $imageStyleStorage->load("sharepic_preview");
    /** @var \Drupal\image\Entity\ImageStyle $shareImageStyle */
    $shareImageStyle = $imageStyleStorage->load("sharepic_share");

    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $result = $nodeStorage->getQuery()
      ->condition('type', 'sharepic')
      ->sort('created', 'DESC')
      ->execute();

    $nodes = $nodeStorage->loadMultiple($result);

    $sharePics = [];
    foreach ($nodes as $node) {
      $image = $node->get('field_image')[0];
      /** @var \Drupal\file\Entity\File $file */
      $file = $image->entity;

      $pic = new SharePic();
      $pic->id = $node->id();
      $pic->body = $node->get('body')->value;
      $pic->width = $image->get('width')->getCastedValue();
      $pic->height = $image->get('height')->getCastedValue();
      $pic->altText = $image->get('alt')->getValue();
      $pic->imageUrl = $fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      $pic->imagePreviewUrl = $previewImageStyle->buildUrl($file->getFileUri());
      $pic->imageShareUrl = $shareImageStyle->buildUrl($file->getFileUri());

      $sharePics[] = $pic;
    }

    return new JsonResponse($sharePics);
  }

}
