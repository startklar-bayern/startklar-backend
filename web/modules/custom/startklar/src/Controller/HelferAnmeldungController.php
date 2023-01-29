<?php

namespace Drupal\startklar\Controller;

use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\CreateAnmeldungBody;
use Drupal\startklar\Model\HelferAnmeldung;
use Drupal\startklar\Model\HelferJob;
use Drupal\startklar\Model\TshirtGroesse;
use Drupal\startklar\Service\HelferService;
use Drupal\startklar\Service\NotFoundException;
use Drupal\startklar\Service\SendInBlueService;
use Drupal\startklar\Session\AnmeldungType;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use OpenApi\Attributes as OA;

class HelferAnmeldungController extends StartklarControllerBase {

  protected string $JWT_KEY;

  /**
   * @var \Drupal\startklar\Service\SendInBlueService
   */
  protected SendInBlueService $sendInBlueService;

  /**
   * @var \Drupal\startklar\Service\HelferService
   */
  protected HelferService $helferService;

  /**
   * @throws \Exception
   */
  public function __construct(HelferService $helferService, SendInBlueService $sendInBlueService) {
    parent::__construct();

    $jwtKey = getenv('STARTKLAR_JWT_KEY');

    if (empty($jwtKey) || strlen($jwtKey) == 0) {
      throw new \Exception("The environment variable 'STARTKLAR_JWT_KEY' is not set.");
    }

    $this->JWT_KEY = $jwtKey;
    $this->helferService = $helferService;
    $this->sendInBlueService = $sendInBlueService;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('startklar.helfer'),
      $container->get('send_in_blue')
    );
  }

  #[OA\Post(
    path: '/anmeldung/helfer',
    operationId: 'create_helfer_anmeldung',
    description: 'Prepares a helfer Anmeldung and sends a link to fill data to the given email address',
    summary: 'Create a helfer Anmeldung',
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/CreateAnmeldungBody')),
    tags: ['Anmeldung Helfer'],
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

    $node = $this->helferService->new($body->mail);

    $jwt = JWT::encode([
      'iss' => $request->getHttpHost(),
      'sub' => $node->id(),
      'type' => AnmeldungType::HELPER,
      'iat' => time(),
      'nbf' => time(),
      'exp' => strtotime("2023-12-31"),
    ], $this->JWT_KEY, 'HS256');

    $this->sendInBlueService->sendHelferanmeldungEmail($body->mail, $node->id(), $jwt);

    return new JsonResponse([
      'status' => 'success',
      'message' => 'Anmeldung was created.',
    ]);
  }

  #[OA\Put(
    path: '/anmeldung/helfer/{helferId}',
    operationId: 'update_helfer_anmeldung',
    description: 'Update a helfer Anmeldung',
    summary: 'Update a helfer Anmeldung',
    security: [['jwt' => []]],
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/HelferAnmeldung')),
    tags: ['Anmeldung Helfer'],
    parameters: [
      new OA\Parameter(name: 'helferId', description: 'Id of the helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'int64')),
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
          new OA\Property('message', type: 'string', example: 'Helfer not found'),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function update(Request $request, string $id) {
    try {
      $helfer = $this->helferService->getById($id);
    } catch (NotFoundException $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Helfer with id ' . $id . ' was not found',
      ], 404);
    }

    $body = $this->getBody($request, HelferAnmeldung::class);

    if ($body instanceof Response) {
      return $body;
    }

    if ($response = $this->isInvalid($body)) {
      return $response;
    }

    /** @var Anmeldung $body */
    $this->helferService->update($helfer, $body);

    return new JsonResponse([
      'status' => 'success',
      'message' => 'Anmeldung updated',
    ]);
  }

  #[OA\Get(
    path: '/anmeldung/helfer/{helferId}',
    operationId: 'get_helfer_anmeldung',
    description: 'Get a helfer Anmeldung',
    summary: 'Get a helfer Anmeldung',
    security: [['jwt' => []]],
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/HelferAnmeldung')),
    tags: ['Anmeldung Helfer'],
    parameters: [
      new OA\Parameter(name: 'helferId', description: 'Id of the helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'int64')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: '#components/schemas/HelferAnmeldung')),
      new OA\Response(response: 400, description: 'Not yet submitted', content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('message', type: 'string', example: 'Helfer with id 123 was not yet submitted'),
        ],
        type: "object",
      )),
      new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('message', type: 'string', example: 'Helfer with id 123 was not found'),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function get(string $id): JsonResponse {
    try {
      $helfer = $this->helferService->getById($id);

      if (!$helfer->isPublished()) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Helfer with id ' . $id . ' was not yet submitted',
        ], 400);
      }

      $anmeldung = $this->helferService->toDto($helfer);

      return new JsonResponse($anmeldung);
    } catch (NotFoundException) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Helfer with id ' . $id . ' was not found',
      ], 404);
    }
  }

  #[OA\Get(
    path: '/anmeldung/helfer-jobs',
    operationId: 'get_helfer_jobs',
    description: 'Get all Helfer Jobs',
    tags: ['Anmeldung Helfer'],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/HelferJob"))
      ),
    ]
  )]
  public function getHelferJobs() {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');

    $result = $termStorage->getQuery()
      ->condition('status', 1)
      ->condition('vid', 'helfer_jobs')
      ->sort('weight')
      ->accessCheck(FALSE)
      ->execute();

    /** @var \Drupal\taxonomy\Entity\Term[] $helferJobs */
    $helferJobs = $termStorage->loadMultiple($result);

    $result = [];

    foreach ($helferJobs as $helferJob) {
      $item = new HelferJob();
      $item->id = $helferJob->id();
      $item->name = $helferJob->label();

      if (!empty($helferJob->getDescription())) {
        $item->description = $helferJob->getDescription();
      }

      $result[] = $item;
    }

    return new JsonResponse($result);
  }

}
