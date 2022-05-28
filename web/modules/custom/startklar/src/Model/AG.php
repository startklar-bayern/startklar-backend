<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
  required: ["id", "title", "body", "icon", "contactName"]
)]
class AG {

  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(description: "The title of the AG.", format: "string", example: "AG Orga")]
  public string $title;

  #[OA\Property(description: "The description of the AG and what it does. Can contain HTML.", format: "string", example: "Lorem Ipsum dolor sit amet. <br>Et consetutor.")]
  public string $body;

  #[OA\Property(description: "The fontawesome icon to use", format: "string", example: "fa-user")]
  public string $icon;

  #[OA\Property(description: "The name of the contact person", format: "string", example: "Max Mustermann")]
  public string $contactName;

  #[OA\Property(description: "The mail address of the contact person", format: "string", example: "m.mustermann@example.com")]
  public ?string $contactMail;

  #[OA\Property(description: "The phone number of the contact person", format: "string", example: "01234 / 567890")]
  public ?string $contactPhone;

}
