<?php

namespace Drupal\startklar\Controller;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\CreateAnmeldungBody;
use Drupal\startklar\Model\SchutzkonzeptTermin;
use Drupal\startklar\Service\GroupService;
use Drupal\startklar\Session\AnmeldungType;
use Firebase\JWT\JWT;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnmeldungController extends StartklarControllerBase {
  protected string $JWT_KEY;

  /**
   * @var \Drupal\startklar\Service\GroupService
   */
  protected GroupService $groupService;

  /**
   * @throws \Exception
   */
  public function __construct(GroupService $groupService) {
    parent::__construct();

    $jwtKey = getenv('STARTKLAR_JWT_KEY');

    if (empty($jwtKey) || strlen($jwtKey) == 0) {
      throw new \Exception("The environment variable 'STARTKLAR_JWT_KEY' is not set.");
    }

    $this->JWT_KEY = $jwtKey;
    $this->groupService = $groupService;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('startklar.groups'));
  }

  #[OA\Post(
    path: '/anmeldung/group',
    operationId: 'create_anmeldung',
    description: 'Prepares a group Anmeldung and sends a link to fill data to the given email address',
    summary: 'Create a group Anmeldung',
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/CreateAnmeldungBody')),
    tags: ['Anmeldung'],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'success'),
          new OA\Property('message', type: 'string', example: 'Created'),
        ],
        type: "object",
      )),
      new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('errors', type: 'array', items: new OA\Items(
            properties: [
              new OA\Property('property', description: 'Which property has the error', type: 'string', example: 'vorname'),
              new OA\Property('message', type: 'string', example: 'Field "vorname" is required!'),
            ],
          )),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function new(Request $request) {
    /** @var CreateAnmeldungBody $body */
    $body = $this->getBody($request, CreateAnmeldungBody::class);

    if ($body instanceof ResponseInterface) {
      return $body;
    }

    if ($response = $this->isInvalid($body)) {
      return $response;
    }

    $node = $this->groupService->new($body->mail);

    $jwt = JWT::encode([
      'iss' => $request->getHttpHost(),
      'sub' => $node->label(),
      'type' => AnmeldungType::GROUP,
      'iat' => time(),
      'nbf' => time(),
      'exp' => strtotime("2023-12-31"),
    ], $this->JWT_KEY, 'HS256');

    return new JsonResponse([
      'status' => 'success',
      'message' => 'Anmeldung was created. The JWT is just here for testing, will be removed once on prod. The JWT will be sent to the user by email.',
      'jwt' => $jwt,
    ]);
  }

  #[OA\Put(
    path: '/anmeldung/group/{groupId}',
    operationId: 'update_anmeldung',
    description: 'Update a group Anmeldung',
    summary: 'Update a group Anmeldung',
    security: [['jwt' => []]],
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/Anmeldung')),
    tags: ['Anmeldung'],
    parameters: [
      new OA\Parameter(name: 'groupId', description: 'Id of the group', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157'))
    ],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'success'),
          new OA\Property('message', type: 'string', example: 'Updated'),
        ],
        type: "object",
      )),
      new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('errors', type: 'array', items: new OA\Items(
            properties: [
              new OA\Property('property', description: 'Which property has the error', type: 'string', example: 'vorname'),
              new OA\Property('message', type: 'string', example: 'Field "vorname" is required!'),
            ],
          )),
        ],
        type: "object",
      )),
      new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('message', type: 'string', example: 'Group not found'),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function update(Request $request, string $id) {
    // TODO: Load group by ID
    $body = $this->getBody($request, Anmeldung::class);

    if ($body instanceof Response) {
      return $body;
    }

    if ($response = $this->isInvalid($body)) {
      return $response;
    }

    // TODO: Validate conditions that are based on the complete object
    // TODO: Update group
    // TODO: Check if people were deleted, delete them
    // TODO: check if peope were added, add them
    // TODO: If this is the first update, send notification mail
    // TODO: Check if file of führungszeugnis has changed, and if so: require a new review
    // TODO: Save the node(s)

    print_r($body);
    die();
  }

  #[OA\Get(
    path: '/anmeldung/termine-schutzkonzept',
    operationId: 'get_termine_schutzkonept',
    description: 'Get Schutzkonzept Termine',
    tags: ['Anmeldung'],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/SchutzkonzeptTermin"))
      ),
    ]
  )]
  public function getTermineSchutzkonzept() {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');

    $termine = $termStorage->loadByProperties(['vid' => 'termine_schutzkonzept']);

    usort($termine, function($a, $b) {
      return strcmp($a->get('field_date')->value, $b->get('field_date')->value);
    });

    $result = [];

    foreach ($termine as $termin) {
      $item = new SchutzkonzeptTermin();
      $item->id = $termin->id();
      $item->date = $termin->get('field_date')->value;

      $result[] = $item;
    }

    return new JsonResponse($result);
  }

}
