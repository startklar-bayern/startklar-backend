<?php

namespace Drupal\startklar\Session;

use Drupal\Core\Session\UserSession;

class AnmeldungSession extends UserSession {
  protected AnmeldungType $anmeldungType;

  protected string $subject;

  /**
   * @return \Drupal\startklar\Session\AnmeldungType
   */
  public function getAnmeldungType(): AnmeldungType {
    return $this->anmeldungType;
  }

  /**
   * @return string
   */
  public function getSubject(): string {
    return $this->subject;
  }

}
