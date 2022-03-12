<?php

namespace Drupal\startklar;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
  info: new OA\Info(
    version: 1.0,
    title: "Startklar API",
    contact: new OA\Contact("Max Bachhuber", "https://bahuma.io", "max.bachhuber@bahuma.io"),
  ),
  servers: [
    new OA\Server(url: "https://backend.startklar.bayern/api", description: "Production Server"),
  ],
  externalDocs: new OA\ExternalDocumentation(description: "Swaggger UI for this file", url: "https://backend.startklar.bayern/api"),
)]
class OpenApi {

}
