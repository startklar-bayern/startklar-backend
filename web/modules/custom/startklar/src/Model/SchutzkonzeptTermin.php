<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["id", "date"]
)]
class SchutzkonzeptTermin {
  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(format: "date")]
  public string $date;
}
