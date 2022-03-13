<?php

namespace Drupal\startklar\Model;

use Drupal\startklar\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Schema(required: ["mail", "privacy_accepted"])]
class NewsletterSubscribeBody {

  #[OA\Property(description: "The email that should be added to the mailinglist", type: "string", example: "max.mustermann@example.com")]
  public string $mail;

  #[OA\Property(description: "If the privacy policy is accepted by the user submitting the form. Always has to be true.", type: "boolean")]
  public bool $privacy_accepted;

  /**
   * @throws \JsonException
   */
  public static function fromJson($json): NewsletterSubscribeBody {
    $data = json_decode($json, FALSE, 512, JSON_THROW_ON_ERROR);

    $obj = new self();
    if (!isset($data->mail)) {
      throw new ValidationException("mail", "is missing");
    }
    $obj->mail = $data->mail;

    if (!isset($data->privacy_accepted)) {
      throw new ValidationException("privacy_accepted", "is missing");
    }
    $obj->privacy_accepted = $data->privacy_accepted;

    return $obj;
  }

}
