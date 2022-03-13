<?php

namespace Drupal\startklar;

use JetBrains\PhpStorm\Pure;

class ValidationException extends \RuntimeException {

  protected string $field;

  protected string $fieldError;

  /**
   * @param string $field
   * @param string $fieldError
   */
  #[Pure]
  public function __construct(string $field, string $fieldError) {
    parent::__construct("Validation error on field $field: $fieldError");

    $this->field = $field;
    $this->fieldError = $fieldError;
  }

  /**
   * @return string
   */
  public function getField(): string {
    return $this->field;
  }

  /**
   * @return string
   */
  public function getFieldError(): string {
    return $this->fieldError;
  }




}
