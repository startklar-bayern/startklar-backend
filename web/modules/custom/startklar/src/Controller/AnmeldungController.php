<?php

namespace Drupal\startklar\Controller;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\CreateAnmeldungBody;
use Drupal\startklar\Model\DV;
use Drupal\startklar\Model\SchutzkonzeptTermin;
use Drupal\startklar\Model\TshirtGroesse;
use Drupal\startklar\Service\GroupService;
use Drupal\startklar\Service\NotFoundException;
use Drupal\startklar\Service\SendInBlueService;
use Drupal\startklar\Session\AnmeldungType;
use Firebase\JWT\JWT;
use OpenApi\Attributes as OA;
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
   * @var \Drupal\startklar\Service\SendInBlueService
   */
  protected SendInBlueService $sendInBlueService;

  /**
   * @throws \Exception
   */
  public function __construct(GroupService $groupService, SendInBlueService $sendInBlueService) {
    parent::__construct();

    $jwtKey = getenv('STARTKLAR_JWT_KEY');

    if (empty($jwtKey) || strlen($jwtKey) == 0) {
      throw new \Exception("The environment variable 'STARTKLAR_JWT_KEY' is not set.");
    }

    $this->JWT_KEY = $jwtKey;
    $this->groupService = $groupService;
    $this->sendInBlueService = $sendInBlueService;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('startklar.groups'),
      $container->get('send_in_blue')
    );
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

    if ($body instanceof Response) {
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

    $this->sendInBlueService->sendGruppenanmeldungEmail($body->mail, $node->label(), $jwt);

    return new JsonResponse([
      'status' => 'success',
      'message' => 'Anmeldung was created. The JWT is just here for testing, will be removed once on prod. The JWT will be sent to the user by email.',
      'jwt' => $jwt, // TODO: remove
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
    try {
      $group = $this->groupService->getById($id);
    } catch (NotFoundException $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Group with id ' . $id . ' was not found',
      ], 404);
    }

    $body = $this->getBody($request, Anmeldung::class);

    if ($body instanceof Response) {
      return $body;
    }

    if ($response = $this->isInvalid($body)) {
      return $response;
    }

    /** @var Anmeldung $body */
    $this->groupService->update($group, $body);

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

    $result = $termStorage->getQuery()
      ->condition('status', 1)
      ->condition('vid', 'termine_schutzkonzept')
      ->sort('field_date')
      ->accessCheck(FALSE)
      ->execute();

    $termine = $termStorage->loadMultiple($result);

    $result = [];

    foreach ($termine as $termin) {
      $item = new SchutzkonzeptTermin();
      $item->id = $termin->id();
      $item->date = $termin->get('field_date')->value;

      $result[] = $item;
    }

    return new JsonResponse($result);
  }

  #[OA\Get(
    path: '/anmeldung/dvs',
    operationId: 'get_dvs',
    description: 'Get all DVs',
    tags: ['Anmeldung'],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/DV"))
      ),
    ]
  )]
  public function getDVs() {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');

    $result = $termStorage->getQuery()
      ->condition('status', 1)
      ->condition('vid', 'dvs')
      ->sort('weight')
      ->accessCheck(FALSE)
      ->execute();

    $dvs = $termStorage->loadMultiple($result);

    $result = [];

    foreach ($dvs as $dv) {
      $item = new DV();
      $item->id = $dv->id();
      $item->name = $dv->label();

      $result[] = $item;
    }

    return new JsonResponse($result);
  }

  #[OA\Get(
    path: '/anmeldung/tshirt-groessen',
    operationId: 'get_tshirt_groessen',
    description: 'Get all T-Shirt Größen',
    tags: ['Anmeldung'],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/TshirtGroesse"))
      ),
    ]
  )]
  public function getTshirtGroessen() {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');

    $result = $termStorage->getQuery()
      ->condition('status', 1)
      ->condition('vid', 't_shirt_groessen')
      ->sort('weight')
      ->accessCheck(FALSE)
      ->execute();

    $tshirtGroessen = $termStorage->loadMultiple($result);

    $result = [];

    foreach ($tshirtGroessen as $tshirtGroesse) {
      $item = new TshirtGroesse();
      $item->id = $tshirtGroesse->id();
      $item->name = $tshirtGroesse->label();

      $result[] = $item;
    }

    return new JsonResponse($result);
  }

}
