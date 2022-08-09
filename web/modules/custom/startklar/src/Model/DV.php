<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["id", "name"]
)]
class DV {

  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(format: "string")]
  public string $name;

}
