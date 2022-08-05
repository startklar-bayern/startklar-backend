<?php

namespace Drupal\startklar\Controller;

use Drupal\startklar\Model\Anmeldung;
use Laminas\Diactoros\Response\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

class AnmeldungController extends StartklarControllerBase {

  public function __construct() {
    parent::__construct();
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  // TODO: document authentication
  #[OA\Put(
    path: '/anmeldung/group/{groupId}',
    operationId: 'update_anmeldung',
    description: 'Create or update a group Anmeldung',
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
  public function update(Request $request) {

    $person = $this->serializer->deserialize($request->getContent(), Anmeldung::class, 'json');

    $validator = Validation::createValidatorBuilder()
      ->enableAnnotationMapping()
      ->getValidator();

    $violations = $validator->validate($person);

    if (count($violations) > 0) {
      $response = [
        'status' => 'error',
        'errors' => [],
      ];

      foreach ($violations as $violation) {
        $response['errors'][] = [
          'property' => $violation->getPropertyPath(),
          'message' => $violation->getMessage(),
        ];
      }

      return new JsonResponse($response, 400);
    }

    print_r($person);
    die();
  }

}
