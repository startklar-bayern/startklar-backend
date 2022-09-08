<?php

namespace Drupal\startklar\Plugin\SimpleCron;

use Drupal\Core\Annotation\Translation;
use Drupal\node\NodeInterface;
use Drupal\simple_cron\Annotation\SimpleCron;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;

/**
 * @SimpleCron(
 *   id = "startklar_sendinblue_sync",
 *   label = @Translation("STARTKLAR: Sync people to SendInBlue", context = "Simple cron")
 * )
 */
class SyncPeopleToSendInBlueCronJob extends SimpleCronPluginBase {
    public function process(): void {
    \Drupal::logger('startklar_sendinblue')->info('Syncing Teilnehmer');

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    /** @var \Drupal\startklar\Service\SendInBlueService $sendInBlue */
    $sendInBlue = \Drupal::service('send_in_blue');

    $result = $nodeStorage->getQuery()
      ->condition('type', 'person')
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(FALSE)
      ->execute();

    $persons = $nodeStorage->loadMultiple($result);

    $mails = [];

    foreach ($persons as $person) {
      $mails[] = $person->get('field_mail')->value;
    }

    $mails = array_unique($mails);

    try {
      $sendInBlue->syncTeilnehmer($mails);
    } catch (\Exception $e) {
      mail('max.bachhuber@bahuma.io', 'STARTKLAR: SendInBlueSync error', "An exception occured while synchronizing teilnehmer to SendInBlue: \n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString());
    }

  }

}
