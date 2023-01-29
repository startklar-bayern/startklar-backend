<?php

namespace Drupal\startklar\Plugin\SimpleCron;

use Drupal\Core\Annotation\Translation;
use Drupal\node\NodeInterface;
use Drupal\simple_cron\Annotation\SimpleCron;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;

/**
 * @SimpleCron(
 *   id = "startklar_sendinblue_sync",
 *   label = @Translation("STARTKLAR: Sync people to SendInBlue", context =
 *   "Simple cron")
 * )
 */
class SyncPeopleToSendInBlueCronJob extends SimpleCronPluginBase {

  public function process(): void {
    \Drupal::logger('startklar_sendinblue')->info('Syncing Teilnehmer');

    $this->syncTeilnehmer();
    $this->syncHelfer();
  }

  protected function syncTeilnehmer() {
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

    $mails = $this->getMailsFromPersons($persons);

    try {
      $sendInBlue->syncTeilnehmer($mails);
    } catch (\Exception $e) {
      mail('max.bachhuber@bahuma.io', 'STARTKLAR: SendInBlueSync error', "An exception occured while synchronizing teilnehmer to SendInBlue: \n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString());
    }
  }

  protected function syncHelfer() {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    /** @var \Drupal\startklar\Service\SendInBlueService $sendInBlue */
    $sendInBlue = \Drupal::service('send_in_blue');

    $result = $nodeStorage->getQuery()
      ->condition('type', 'helfer')
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(FALSE)
      ->execute();

    $helfer = $nodeStorage->loadMultiple($result);

    $persons = [];

    foreach ($helfer as $helf) {
      $persons[] = $helf->get('field_person')->entity;
    }

    $mails = $this->getMailsFromPersons($persons);

    try {
      $sendInBlue->syncHelfer($mails);
    } catch (\Exception $e) {
      mail('max.bachhuber@bahuma.io', 'STARTKLAR: SendInBlueSync error', "An exception occured while synchronizing helfer to SendInBlue: \n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString());
    }
  }

  protected function getMailsFromPersons(array $persons) {
    $mails = [];

    if (empty($persons)) {
      return $mails;
    }

    foreach ($persons as $person) {
      $mails[] = $person->get('field_mail')->value;
    }

    return array_unique($mails);
  }


}
