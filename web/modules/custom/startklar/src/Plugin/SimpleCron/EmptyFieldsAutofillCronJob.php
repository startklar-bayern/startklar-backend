<?php

namespace Drupal\startklar\Plugin\SimpleCron;


use Drupal\simple_cron\Plugin\SimpleCronPluginBase;

/**
 * @SimpleCron(
 *   id = "startklar_empty_fields_autofill",
 *   label = @Translation("STARTKLAR: Autofill empty fields", context =
 *   "Simple cron")
 * )
 */
class EmptyFieldsAutofillCronJob extends SimpleCronPluginBase{

  public function process(): void {
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    $result = $nodeStorage->getQuery()
      ->condition('type', 'person')
      ->accessCheck(FALSE)
      ->execute();

    /** @var \Drupal\node\NodeInterface $people */
    $people = $nodeStorage->loadMultiple($result);

    foreach ($people as $person) {
      $changed = FALSE;

      if ($person->field_pronomen->isEmpty()) {
        $changed = TRUE;
        $pronoun = match ($person->field_geschlecht->value) {
          'm' => 'er/ihm',
          'w' => 'sie/ihr',
          default => '',
        };
        \Drupal::logger('startklar_autofill')->info("Setting pronouns of " . $person->field_vorname->value . ' ' . $person->field_nachname->value . ' to "' .$pronoun . '"');
        $person->set('field_pronomen', $pronoun);
      }

      if ($person->field_land->isEmpty()) {
        $changed = TRUE;
        \Drupal::logger('startklar_autofill')->info("Setting country of " . $person->field_vorname->value . ' ' . $person->field_nachname->value . ' to "Deutschland"');
        $person->set('field_land', 'Deutschland');
      }

      if ($changed) {
        $person->save();
      }
    }
  }

}
