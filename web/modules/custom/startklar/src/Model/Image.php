<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["url", "previewUrl", "altText", "width", "height"]
)]
class Image {
  #[OA\Property(description: "The full size image URL", format: "string", example: "https://backend.startklar.bayern/sites/default/files/images/123.jpg")]
  public string $url;

  #[OA\Property(description: "The reduced size image preview URL", format: "string", example: "https://backend.startklar.bayern/sites/default/files/styles/preview/public/images/123.jpg")]
  public string $previewUrl;

  #[OA\Property(description: "A description of the image content that can be used for the alt tag.", format: "string", example: "Lachende Personen am Lagerfeuer")]
  public string $altText;

  #[OA\Property(description: "The width of the full image in pixel. Should be used to tell the browser image dimensions.", format: "int64")]
  public int $width;

  #[OA\Property(description: "The height of the full image in pixel. Should be used to tell the browser image dimensions.", format: "int64")]
  public int $height;
}
