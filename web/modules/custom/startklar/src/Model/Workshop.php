<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;
use Prophecy\Doubler\Generator\ReflectionInterface;

#[OA\Schema(
  required: ["id", "title", "summary", "body", "previewImage", "timeslots"]
)]
class Workshop {

  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(description: "The title of the Workshop.", format: "string", example: "Handmassage")]
  public string $title;

  #[OA\Property(description: "A short summary of the workshop", format: "string", example: "Lorem Ipsum dolor sit amet.\r\nEt consetutor.")]
  public string $summary;

  #[OA\Property(description: "The full description and long text of the Workshop. Can contain HTML.", format: "string", example: "Lorem Ipsum dolor sit amet. <br>Et consetutor.")]
  public string $body;

  #[OA\Property(description: "When the workshop will take place", type: "array", items: new OA\Items(
    type: "string",
    enum: [
      WorkshopTimeslot::FRIDAY_MORNING,
      WorkshopTimeslot::FRIDAY_AFTERNOON,
      WorkshopTimeslot::SATURDAY_MORNING,
      WorkshopTimeslot::SATURDAY_AFTERNOON,
    ]))]
  /** @var \Drupal\startklar\Model\WorkshopTimeslot[] $timeslots */
  public array $timeslots;

  #[OA\Property(description: "Where the workshop will take place", format: "string", example: "Hauptbühne")]
  public ?string $location;

  #[OA\Property(ref: '#/components/schemas/Image', description: "A preview image.", type: "object")]
  public Image $previewImage;

  #[OA\Property(description: "A list of additional images that can be displayed on a detail page.", type: "array", items: new OA\Items(ref: '#/components/schemas/Image'))]
  /** @var \Drupal\startklar\Model\Image[] $images */
  public ?array $images;

}
