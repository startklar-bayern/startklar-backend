<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(required: ["id", "question", "answer"])]
class Faq {
  #[OA\Property(format: "int64")]
  public int $id;

  #[OA\Property(description: "The question to be answered", format: "string", example: "Wo findet das Event statt?")]
  public string $question;

  #[OA\Property(description: "The answer to the question. Can contain HTML.", format: "string", example: "Auf einem Zeltplatz in <strong>Bayern</strong>")]
  public string $answer;
}
