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

    $this->syncGroups();
    $this->syncTeilnehmer();
    $this->syncHelfer();
  }

  protected function syncGroups() {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    /** @var \Drupal\startklar\Service\SendInBlueService $sendInBlue */
    $sendInBlue = \Drupal::service('send_in_blue');

    $result = $nodeStorage->getQuery()
      ->condition('type', 'group')
      ->accessCheck(FALSE)
      ->execute();

    /** @var NodeInterface[] $groups */
    $groups = $nodeStorage->loadMultiple($result);

    $groupsCompleteEmails = [];
    $groupsIncompleteEmails = [];
    foreach ($groups as $group) {
      if ($group->isPublished()) {
        $groupsCompleteEmails[] = $group->get('field_mail')->value;
      } else {
        $groupsIncompleteEmails[] = $group->get('field_mail')->value;
      }
    }
    $groupsCompleteEmails = array_unique($groupsCompleteEmails);
    $groupsIncompleteEmails = array_unique($groupsIncompleteEmails);

    // Remove incomplete emails that have another group entity with the same email that is completed
    $groupsIncompleteEmails = array_filter($groupsIncompleteEmails, function ($helf) use ($groupsCompleteEmails) {
      return !in_array($helf, $groupsCompleteEmails);
    });

    try {
      $sendInBlue->syncGroupsComplete($groupsCompleteEmails);
    } catch (\Exception $e) {
      mail('max.bachhuber@bahuma.io', 'STARTKLAR: SendInBlueSync error', "An exception occured while synchronizing groups complete to SendInBlue: \n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString());
    }

    try {
      $sendInBlue->syncGroupsIncomplete($groupsIncompleteEmails);
    } catch (\Exception $e) {
      mail('max.bachhuber@bahuma.io', 'STARTKLAR: SendInBlueSync error', "An exception occured while synchronizing groups incomplete to SendInBlue: \n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString());
    }
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
      ->accessCheck(FALSE)
      ->execute();

    /** @var NodeInterface[] $helfer */
    $helfer = $nodeStorage->loadMultiple($result);

    $helferCompleteEmails = [];
    $helferIncompleteEmails = [];
    foreach ($helfer as $helf) {
      if ($helf->isPublished()) {
        $helferCompleteEmails[] = $helf->get('field_mail')->value;
      } else {
        $helferIncompleteEmails[] = $helf->get('field_mail')->value;
      }
    }
    $helferCompleteEmails = array_unique($helferCompleteEmails);
    $helferIncompleteEmails = array_unique($helferIncompleteEmails);

    // Remove incomplete emails that have another helfer entity with the same email that is completed
    $helferIncompleteEmails = array_filter($helferIncompleteEmails, function ($helf) use ($helferCompleteEmails) {
      return !in_array($helf, $helferCompleteEmails);
    });

    try {
      $sendInBlue->syncHelferIncomplete($helferIncompleteEmails);
    } catch (\Exception $e) {
      mail('max.bachhuber@bahuma.io', 'STARTKLAR: SendInBlueSync error', "An exception occured while synchronizing helfer incomplete to SendInBlue: \n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString());
    }

    // Persons of published helfer entities
    $helfer = array_filter($helfer, function ($helf) {
      return $helf->isPublished();
    });

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
