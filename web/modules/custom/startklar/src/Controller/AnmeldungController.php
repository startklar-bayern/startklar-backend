<?php

namespace Drupal\startklar\Controller;

use Drupal\startklar\Model\Anmeldung;
use Laminas\Diactoros\Response\JsonResponse;
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
    return new static(
    );
  }

  public function add(Request $request) {

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
