<?php

namespace Drupal\startklar\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class FileController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  #[OA\Post(
    path: '/files/{subjectId}',
    operationId: 'anmeldung_files_upload',
    description: "Upload a file that can be used in other places",
    security: [['jwt' => []]],
    requestBody: new OA\RequestBody(
      content: [new OA\MediaType(
        mediaType: 'multipart/form-data',
        schema: new OA\Schema(
          properties: [new OA\Property(property: "file", description: "file to upload", type: "string", format: "binary")],
        )
      )]
    ),
    tags: ['Files'],
    parameters: [
      new OA\Parameter(name: 'subjectId', description: 'Id of the group or helfer', in: 'path', required: TRUE, schema: new OA\Schema(type: 'string', example: 'Tatkraft-157')),
    ],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'success'),
          new OA\Property('message', type: 'string', example: 'File was created'),
          new OA\Property('fileId', type: 'string', format: 'uuid'),
        ],
        type: "object",
      )),
      new OA\Response(response: 400, description: "Client error", content: new OA\JsonContent(
        properties: [
          new OA\Property('status', type: 'string', example: 'error'),
          new OA\Property('message', type: 'string', example: 'Only these formats are allowed: pdf, png, jpg, jpeg, tiff'),
        ],
        type: "object",
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  /**
   * Upload a file
   *
   * @param string $id
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function upload(Request $request, string $id) {
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile */
    $uploadedFile = $request->files->get('file');

    if (!$uploadedFile) {
      throw new BadRequestHttpException('"file" is required');
    }

    /** @var \Drupal\Component\Uuid\UuidInterface $uuidService */
    $uuidService = \Drupal::service('uuid');

    $uuid = $uuidService->generate();

    $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'tiff'];

    if (!in_array($uploadedFile, $allowedExtensions)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Only these formats are allowed: ' . join(', ', $allowedExtensions),
      ], 400);
    }

    $originalFileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
    $fileName = Html::getClass($originalFileName) . '-' . $uuid . '.' . $uploadedFile->guessExtension();

    $directory = 'private://startklar';

    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $this->fileSystem->createFilename($originalFileName . '-' . $uuid . '.' . $uploadedFile->guessExtension(), $directory);

    $uri = $this->fileSystem->move($uploadedFile->getRealPath(), $directory . '/' . $fileName);

    $file = File::create([
      'filename' => $fileName,
      'uri' => $uri,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);

    $file->save();

    $node = Node::create([
      'type' => 'datei',
      'status' => NodeInterface::PUBLISHED,
      'title' => $uuid,
      'field_subject_id' => $id,
    ]);

    $node->set('field_file', [
      'target_id' => $file->id(),
    ]);

    $node->save();

    return new JsonResponse([
      'status' => 'success',
      'message' => 'file_created',
      'fileId' => $uuid,
    ]);
  }

}
