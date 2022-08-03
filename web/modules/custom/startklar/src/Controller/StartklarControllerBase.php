<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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


}
