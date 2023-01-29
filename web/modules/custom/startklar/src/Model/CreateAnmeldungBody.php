<?php

namespace Drupal\startklar\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
  required: ['mail', 'participant_privacy_accepted', 'privacy_accepted']
)]
class CreateAnmeldungBody {

  #[OA\Property(description: "Mail address of the person who administers the group or helfer", type: "string", format: "email")]
  /**
   * @Assert\NotBlank()
   * @Assert\Email()
   */
  public string $mail;

  #[OA\Property(description: "If the sender has the permission of all participants, to give their personal data to us.", type: "boolean")]
  /**
   * @Assert\NotNull()
   * @Assert\IsTrue()
   */
  public bool $participant_privacy_accepted;

  #[OA\Property(description: "If the sender has read and accepted the privacy policy", type: "boolean")]
  /**
   * @Assert\NotNull()
   * @Assert\IsTrue()
   */
  public bool $privacy_accepted;
}
