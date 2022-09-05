<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\startklar\Service\NotFoundException;
use Drupal\startklar\Service\TempStorageService;
use Laminas\Diactoros\Response\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnmeldungTempStorageController extends ControllerBase {
  protected TempStorageService $tempStorageService;

  public function __construct(TempStorageService $tempStorageService) {
    $this->tempStorageService = $tempStorageService;
  }

  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('startklar.temp_storage')
    );
  }


  #[OA\Head(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_exists',
    description: "Check if something is stored in temp storage of a group or helfer",
    security: [['jwt' => []]],
    tags: ['Anmeldung Temp Storage'],
    parameters: [
      new OA\Parameter(name: 'id', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK - Something is in storage"),
      new OA\Response(response: 404, description: 'Not found - Nothing is in storage or group is false'),
      new OA\Response(response: 500, description: 'Server error'),
    ],
  )]
  public function exists($id) {
    if ($this->tempStorageService->exists($id)) {
      return new Response('exists');
    } else {
      return new Response('Does not exist', 404);
    }
  }

  #[OA\Put(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_set',
    description: "Set the value of the temp storage of a group or helfer",
    security: [['jwt' => []]],
    requestBody: new OA\RequestBody(content: [new OA\MediaType('plain/text', schema: new OA\Schema(type: "string", description: "The value to put in the temp storage."))]),
    tags: ['Anmeldung Temp Storage'],
    parameters: [
      new OA\Parameter(name: 'id', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK - Value was stored"),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function setValue(Request $request, $id) {
    $value = $request->getContent();
    $this->tempStorageService->setValue($id, $value);
    return new Response('Value was stored');
  }

  #[OA\Get(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_get',
    description: "Set the value of the temp storage of a group or helfer",
    security: [['jwt' => []]],
    requestBody: new OA\RequestBody(content: [new OA\MediaType('plain/text', schema: new OA\Schema(description: "The value to put in the temp storage.", type: "string"))]),
    tags: ['Anmeldung Temp Storage'],
    parameters: [
      new OA\Parameter(name: 'id', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK", content: [new OA\MediaType('plain/text', schema: new OA\Schema(description: "The value that was saved previously", type: "string"))]),
      new OA\Response(response: 404, description: 'Not found - Nothing is in storage or id is false', content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('message', type: 'string', example: 'Storage not found'),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function getValue($id) {
    try {
      $value = $this->tempStorageService->getValue($id);

      return new Response($value);
    } catch (NotFoundException $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Storage not found'
      ], 404);
    }
  }

  #[OA\Delete(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_delete',
    description: "Delete the value of a temp storage of a group or helfer",
    security: [['jwt' => []]],
    tags: ['Anmeldung Temp Storage'],
    parameters: [
      new OA\Parameter(name: 'id', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'success'),
          new OA\Property('message', type: 'string', example: 'Storage cleared'),
        ],
        type: "object",
      )),
      new OA\Response(response: 404, description: 'Not found - Nothing is in storage or id is false', content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('message', type: 'string', example: 'Storage not found'),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function deleteValue($id) {
    try {
      $this->tempStorageService->delete($id);

      return new JsonResponse([
        'status' => 'success',
        'message' => 'storage cleared',
      ]);
    } catch (NotFoundException $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Storage not found',
      ], 404);
    }
  }

}
