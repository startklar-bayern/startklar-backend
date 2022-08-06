<?php

namespace Drupal\startklar\Controller;

use OpenApi\Attributes as OA;

class AnmeldungTempStorageController {

  // TODO: Document authentication
  #[OA\Head(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_exists',
    description: "Check if something is stored in temp storage of a group or helfer",
    tags: ['Anmeldung Temp Storage'],
    parameters: [
      new OA\Parameter(name: 'id', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK - Something is in storage"),
      new OA\Response(response: 404, description: 'Not found - Nothing is in storage or group is false'),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function exists() {
    // TODO: Authentication
  }

  // TODO: Document authentication
  #[OA\Put(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_set',
    description: "Set the value of the temp storage of a group or helfer",
    requestBody: new OA\RequestBody(content: [new OA\MediaType('plain/text', schema: new OA\Schema(type: "string", description: "The value to put in the temp storage."))]),
    tags: ['Anmeldung Temp Storage'],
    parameters: [
      new OA\Parameter(name: 'id', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK - Something is in storage"),
      new OA\Response(response: 404, description: 'Not found - Nothing is in storage or id is false'),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function setValue() {
    // TODO: Authentication
  }

  // TODO: Document authentication
  #[OA\Get(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_get',
    description: "Set the value of the temp storage of a group or helfer",
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
  public function getValue() {
    // TODO: Authentication
  }

  // TODO: Document authentication
  #[OA\Delete(
    path: '/anmeldung/tempStorage/{id}',
    operationId: 'anmeldung_tempstorage_delete',
    description: "Delete the value of a temp storage of a group or helfer",
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
  public function deleteValue() {
    // TODO: Authentication
  }

}
