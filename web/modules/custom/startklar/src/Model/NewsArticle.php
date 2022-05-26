<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["id", "title", "teaser", "body", "previewImage"]
)]
class NewsArticle {

  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(format: "date-time")]
  public string $created;

  #[OA\Property(description: "The title of the news article.", format: "string", example: "Spreadshirt Shop online!")]
  public string $title;

  #[OA\Property(description: "A teaser text.", format: "string", example: "In unserem Spreadshirt-Shop gibt es T-Shirts und co.\r\nHolt sie euch jetzt!")]
  public string $teaser;

  #[OA\Property(description: "The full body of the news article. Can contain HTML.", format: "string", example: "Lorem Ipsum dolor sit amet. <br>Et consetutor.")]
  public string $body;

  #[OA\Property(description: "A preview image.", type: "object", ref: '#/components/schemas/Image')]
  public Image $previewImage;

  #[OA\Property(description: "A list of additional images.", type: "array", items: new OA\Items(ref: '#/components/schemas/ImageWithTitle'))]
  /** @var \Drupal\startklar\Model\ImageWithTitle[] */
  public ?array $images;

}
