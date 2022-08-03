<?php

namespace Drupal\startklar\Controller;

use Drupal\startklar\Model\Person;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class AnmeldungController extends StartklarControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    );
  }

  public function add(Request $request) {

    $person = $this->serializer->deserialize($request->getContent(), Person::class, 'json');

    print_r($person);
    die();
  }

}
