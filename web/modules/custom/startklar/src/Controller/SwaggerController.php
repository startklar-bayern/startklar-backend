<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use OpenApi\Attributes\Server;
use OpenApi\Generator;
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
    $currentInstanceServer = new Server(Url::fromRoute('startklar.openapi.swagger')->setAbsolute(true)->toString(), "This Instance");
    array_unshift($openapi->servers, $currentInstanceServer);

    $yaml = $openapi->toYaml();

    return new Response($yaml, 200, [
      'Content-Type' => 'text/plain',
    ]);
  }

  public function swagger() {
    return [
      '#type' => 'openapi_ui',
      '#openapi_ui_plugin' => 'swagger',
      '#openapi_schema' => Url::fromRoute('startklar.openapi.spec'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
