<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\startklar\Model\Faq;
use Laminas\Diactoros\Response\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Startklar routes.
 */
class FaqController extends ControllerBase {

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
    path: "/faqs", description: "Get all FAQs", tags: ["FAQs"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Faq"))
      ),
    ]
  )]
  public function index() {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $nids = $nodeStorage->getQuery()
      ->condition('type', 'faq')
      ->condition('status', TRUE)
      ->sort('field_weight', 'ASC')
      ->execute();

    $faqNodes = $nodeStorage->loadMultiple($nids);

    $faqs = [];

    foreach ($faqNodes as $node) {
      $faq = new Faq();

      $faq->id = $node->id();
      $faq->question = $node->label();
      $faq->answer = $node->get('body')->value;

      $faqs[] = $faq;
    }

    return new JsonResponse($faqs);
  }

}
