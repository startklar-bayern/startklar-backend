<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Schema(required: ["mail", "privacy_accepted"])]
class FaqAskBody {
  #[OA\Property(description: "The email of the user that asks the question", type: "string", example: "max.mustermann@example.com")]
  public string $mail;

  #[OA\Property(description: "The name of the user that asks the question", type: "string", example: "Max Mustermann")]
  public string $name;

  #[OA\Property(description: "The body of the question", type: "string", example: "Wird es Fahrgemeinschaften geben?")]
  public string $question;

  /**
   * @throws \JsonException
   */
  public static function fromJson($json): FaqAskBody {
    $data = json_decode($json, FALSE, 512, JSON_THROW_ON_ERROR);

    $obj = new self();

    if (!isset($data->mail)) {
      throw new ValidationException("mail", "is missing");
    }
    $obj->mail = $data->mail;

    if (!isset($data->name)) {
      throw new ValidationException("name", "is missing");
    }
    $obj->name = $data->name;

    if (!isset($data->question)) {
      throw new ValidationException("question", "is missing");
    }
    $obj->question = $data->question;

    return $obj;
  }
}
