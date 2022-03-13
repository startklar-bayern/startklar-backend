<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\startklar\Model\MenuLink;
use Drupal\startklar\Model\Page;
use Laminas\Diactoros\Response\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Startklar routes.
 */
class PageController extends ControllerBase {

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
    path: "/pages", description: "Get all pages", tags: ["Pages"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Page"))
      ),
    ]
  )]
  public function index() {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager */
    $menuLinkManager = \Drupal::service('plugin.manager.menu.link');

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $result = $nodeStorage->getQuery()
      ->condition('type', 'page')
      ->sort('created', 'DESC')
      ->execute();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $nodeStorage->loadMultiple($result);

    $pages = [];

    foreach ($nodes as $node) {
      $page = new Page();

      $page->id = $node->id();
      $page->title = $node->label();
      $page->body = $node->get('body')->value;
      $page->path = $node->toUrl()->toString();

      $menuLinks = $menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $node->id()]);

      $mLinks = [];

      foreach ($menuLinks as $menuLink) {
        $mLink = new MenuLink();

        $mLink->title = $menuLink->getTitle();
        $mLink->menu = str_replace("-menu", "", str_replace("frontend-", "", $menuLink->getMenuName()));
        $mLink->weight = $menuLink->getWeight();

        $mLinks[] = $mLink;
      }

      $page->menuLinks = $mLinks;

      $pages[] = $page;
    }

    return new JsonResponse($pages);

  }

}
