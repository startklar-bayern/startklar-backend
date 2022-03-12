<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["id", "imageUrl", "altText", "width", "height"]
)]
class SharePic {

  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(description: "The full size image URL", format: "string", example: "https://backend.startklar.bayern/sites/default/files/images/123.jpg")]
  public string $imageUrl;

  #[OA\Property(description: "An image URL for a reduced version that should be displayed on the website", format: "string", example: "https://backend.startklar.bayern/sites/default/files/styles/sharpic_preview/public/images/123.jpg")]
  public string $imagePreviewUrl;

  #[OA\Property(description: "An image URL for a reduced version that should be used for sharing", format: "string", example: "https://backend.startklar.bayern/sites/default/files/styles/sharpic_share/public/images/123.jpg")]
  public string $imageShareUrl;

  #[OA\Property(description: "A text that should be shared together with the image. Can contain HTML.", format: "string", example: "Ich bin <strong>startklar</strong>. Du auch?")]
  public string $body;

  #[OA\Property(description: "A description of the image content that can be used for the alt tag.", format: "string", example: "Lachende Personen am Lagerfeuer")]
  public string $altText;

  #[OA\Property(description: "The width of the full image in pixel", format: "int64")]
  public int $width;

  #[OA\Property(description: "The height of the full image in pixel", format: "int64")]
  public int $height;

}
