<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(required: ["menu", "title", "weight"])]
class MenuLink {
  #[OA\Property(description: "The id of the menu where this link should be put in", type: "string", example: "footer")]
  public string $menu;

  #[OA\Property(description: "The title of the menu link", type: "string", example: "Impressum")]
  public string $title;

  #[OA\Property(description: "A weight that can be used for sorting", format: "int64")]
  public int $weight;
}
