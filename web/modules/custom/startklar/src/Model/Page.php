<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(required: ["id", "title", "path"])]
class Page {
  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(description: "The page title", format: "string", example: "Impressum")]
  public string $title;

  #[OA\Property(description: "The page body. Can contain HTML", format: "string", example: "<p>Dies ist der Inhalt mit HTML</p>")]
  public string $body;

  #[OA\Property(description: "The url for the page", format: "string", example: "/impressum")]
  public string $path;

  #[OA\Property(description: "A list of menu links for this page", type: "array", items: new OA\Items(ref: '#/components/schemas/MenuLink'))]
  /** @var MenuLink[] */
  public array $menuLinks;
}
