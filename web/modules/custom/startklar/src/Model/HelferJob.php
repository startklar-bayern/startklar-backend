<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["id", "name"]
)]
class HelferJob {
  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(format: "string", example: "Küchenteam")]
  public string $name;

  #[OA\Property(description: "Job description. Can contain HTML.", format: "string", example: "<p>Kochen von Essen für sehr viele Leute.<br/>Einkaufen und alles was dazu gehört.</p>")]
  public ?string $description;

}
