<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

require_once '../vendor/symfony/property-access/PropertyAccess.php';

abstract class StartklarControllerBase extends ControllerBase {
  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected Serializer $serializer;

  /**
   * @param \Symfony\Component\Serializer\Serializer $serializer
   */
  public function __construct() {
    $this->serializer = $this->createSerializer();
  }

  protected function createSerializer() {
    $encoder = [new JsonEncoder()];
    $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
    $normalizer = [new ArrayDenormalizer(), new ObjectNormalizer(null, null, null, $extractor)];
    return new Serializer($normalizer, $encoder);
  }

  protected function getBody(Request $request, $type) {
    try {
      return $this->serializer->deserialize($request->getContent(), $type, 'json');
    } catch (\Exception $exception) {
      \Drupal::logger('startklar_anmeldung')->debug('Exception while parsing body: ' . $exception->getMessage());
      return new JsonResponse([
        'status' => 'error',
        'errors' => [
          'property' => '',
          'message' => 'Invalid body',
        ],
      ], 400);
    }

  }

  protected function isInvalid($body): JsonResponse|bool {
    $validator = Validation::createValidatorBuilder()
      ->enableAnnotationMapping()
      ->getValidator();

    $violations = $validator->validate($body);

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

    return false;
  }


}
