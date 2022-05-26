<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema()]
class ImageWithTitle extends Image {
  #[OA\Property(description: "A title for the image that can be used for the title tag.", format: "string", example: "Hast auch du Lust auf Lagerfeuer?")]
  public ?string $title;
}
