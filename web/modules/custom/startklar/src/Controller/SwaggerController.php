<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use League\Container\ContainerAwareInterface;
use OpenApi\Generator;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Startklar routes.
 */
class SwaggerController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function spec() {
    /** @var ExtensionPathResolver $extensionListModuleService */
    $extensionListModuleService = \Drupal::service('extension.path.resolver');
    $moduleDir = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $extensionListModuleService->getPath('module', 'startklar');

    $openapi = Generator::scan([$moduleDir]);
    header('Content-Type: application/x-yaml');
    $yaml = $openapi->toYaml();

    return new Response($yaml, 200, ['Content-Type' => 'application/x-yaml']);
  }

}
