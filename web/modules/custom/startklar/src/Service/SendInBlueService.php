<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Http\RequestStack;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\ApiException;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\CreateDoiContact;
use SendinBlue\Client\Model\RemoveContactFromList;
use SendinBlue\Client\Model\RequestContactImport;
use SendinBlue\Client\Model\SendSmtpEmail;
use SendinBlue\Client\Model\SendSmtpEmailTo;

class SendInBlueService {

  protected string $API_KEY;

  protected int $NEWSLETTER_LIST_ID;

  protected int $DOI_TEMPLATE_ID;

  protected string $FRONTEND_URL;

  protected string $FRONTEND_URL_ANMELDUNG;
  protected string $FRONTEND_URL_HELFERANMELDUNG;

  protected int $GRUPPENANMELDUNG_TEMPLATE_ID;

  protected int $HELFERANMELDUNG_TEMPLATE_ID;

  protected int $TEILNEHMER_LIST_ID;
  protected int $HELFER_LIST_ID;
  protected int $HELFER_INCOMPLETE_LIST_ID;
  protected int $GROUPS_LIST_ID;
  protected int $GROUPS_INCOMPLETE_LIST_ID;

  protected bool $isDebugMode;

  protected LoggerInterface $logger;

  public function __construct(RequestStack $requestStack) {
    $this->API_KEY = getenv('SEND_IN_BLUE_API_KEY');
    $this->NEWSLETTER_LIST_ID = intval(getenv('SEND_IN_BLUE_NEWSLETTER_LIST_ID'));
    $this->DOI_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_DOUBLE_OPT_IN_TEMPLATE_ID'));
    $this->FRONTEND_URL = getenv('FRONTEND_URL');
    $this->FRONTEND_URL_ANMELDUNG = getenv('FRONTEND_URL_ANMELDUNG');
    $this->FRONTEND_URL_HELFERANMELDUNG = getenv('FRONTEND_URL_HELFERANMELDUNG');
    $this->GRUPPENANMELDUNG_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_GRUPPENANMELDUNG_TEMPLATE_ID'));
    $this->HELFERANMELDUNG_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_HELFERANMELDUNG_TEMPLATE_ID'));
    $this->TEILNEHMER_LIST_ID = intval(getenv('SEND_IN_BLUE_TEILNEHMER_LIST_ID'));
    $this->HELFER_LIST_ID = intval(getenv('SEND_IN_BLUE_HELFER_LIST_ID'));
    $this->HELFER_INCOMPLETE_LIST_ID = intval(getenv('SEND_IN_BLUE_HELFER_INCOMPLETE_LIST_ID'));
    $this->GROUPS_LIST_ID = intval(getenv('SEND_IN_BLUE_GROUPS_LIST_ID'));
    $this->GROUPS_INCOMPLETE_LIST_ID = intval(getenv('SEND_IN_BLUE_GROUPS_INCOMPLETE_LIST_ID'));
    $this->isDebugMode = getenv('SEND_IN_BLUE_DEBUG') == "enabled";

    $this->logger = \Drupal::logger('startklar_sendinblue');
  }

  protected function getApiClient() {
    $config = Configuration::getDefaultConfiguration()
      ->setApiKey('api-key', $this->API_KEY);
    return new ContactsApi(new Client(), $config);
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  public function subscribeToNewsletter(string $mail) {
    $apiClient = $this->getApiClient();

    // Create contact
    $createDoiContact = new CreateDoiContact();
    $createDoiContact->setTemplateId($this->DOI_TEMPLATE_ID);
    $createDoiContact->setEmail($mail);
    $createDoiContact->setIncludeListIds([$this->NEWSLETTER_LIST_ID]);
    $createDoiContact->setRedirectionUrl(Url::fromUri($this->FRONTEND_URL, ['query' => ['emailConfirmed' => TRUE]])->setAbsolute(TRUE)->toString());

    if ($this->isDebugMode) {
      $this->logger->info('Send Double Opt In Mail. ' . print_r([
        'createDoiContact' => $createDoiContact,
      ], TRUE));
    } else {
      $apiClient->createDoiContact($createDoiContact);
    }
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  public function getNewsletterSubscriberCount(): int {
    $apiClient = $this->getApiClient();

    $list = $apiClient->getList($this->NEWSLETTER_LIST_ID);

    return $list->getTotalSubscribers();
  }

  public function sendGruppenanmeldungEmail(string $recipient, string $groupId, string $jwt) {
    $config = Configuration::getDefaultConfiguration()
      ->setApiKey('api-key', $this->API_KEY);

    $apiInstance = new TransactionalEmailsApi(null, $config);

    $sendSmtpEmail = new SendSmtpEmail();
    $sendSmtpEmail->setTo([new SendSmtpEmailTo(['email' => $recipient])]);
    $sendSmtpEmail->setTemplateId($this->GRUPPENANMELDUNG_TEMPLATE_ID);

    $anmeldungUrl = str_replace('{{groupId}}', $groupId, $this->FRONTEND_URL_ANMELDUNG);

    $sendSmtpEmail['params'] = [
      'STARTKLAR_LINK' => Url::fromUri($anmeldungUrl, ['query' => ['token' => $jwt]])->setAbsolute(TRUE)->toString(),
      'STARTKLAR_GRUPPENNUMMER' => $groupId,
    ];

    if ($this->isDebugMode) {
      $this->logger->info('Send Gruppenanmeldung Email ' . print_r([
          '$sendSmtpEmail' => $sendSmtpEmail,
        ], TRUE));
    } else {
      $apiInstance->sendTransacEmail($sendSmtpEmail);
    }
  }

  public function sendHelferanmeldungEmail(string $recipient, string $helferId, string $jwt) {
    $config = Configuration::getDefaultConfiguration()
      ->setApiKey('api-key', $this->API_KEY);

    $apiInstance = new TransactionalEmailsApi(null, $config);

    $sendSmtpEmail = new SendSmtpEmail();
    $sendSmtpEmail->setTo([new SendSmtpEmailTo(['email' => $recipient])]);
    $sendSmtpEmail->setTemplateId($this->HELFERANMELDUNG_TEMPLATE_ID);

    $anmeldungUrl = str_replace('{{helferId}}', $helferId, $this->FRONTEND_URL_HELFERANMELDUNG);

    $sendSmtpEmail['params'] = [
      'STARTKLAR_LINK' => Url::fromUri($anmeldungUrl, ['query' => ['token' => $jwt]])->setAbsolute(TRUE)->toString(),
    ];

    if ($this->isDebugMode) {
      $this->logger->info('Send Helferanmeldung Email ' . print_r([
          '$sendSmtpEmail' => $sendSmtpEmail,
        ], TRUE));
    } else {
      $apiInstance->sendTransacEmail($sendSmtpEmail);
    }
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  public function syncTeilnehmer(array $mails): void {
    $existingMails = $this->getMailsInList($this->TEILNEHMER_LIST_ID);

    $removedMails = array_values(array_diff($existingMails, $mails));
    $this->removeMailsFromList($removedMails, $this->TEILNEHMER_LIST_ID);

    $this->importPeopleToList($this->convertToBody($mails), $this->TEILNEHMER_LIST_ID);
  }

  public function syncHelfer(array $mails): void {
    $existingMails = $this->getMailsInList($this->HELFER_LIST_ID);

    $removedMails = array_values(array_diff($existingMails, $mails));
    $this->removeMailsFromList($removedMails, $this->HELFER_LIST_ID);

    $this->importPeopleToList($this->convertToBody($mails), $this->HELFER_LIST_ID);
  }

  public function syncHelferIncomplete(array $mails): void {
    $existingMails = $this->getMailsInList($this->HELFER_INCOMPLETE_LIST_ID);

    $removedMails = array_values(array_diff($existingMails, $mails));
    $this->removeMailsFromList($removedMails, $this->HELFER_INCOMPLETE_LIST_ID);

    $this->importPeopleToList($this->convertToBody($mails), $this->HELFER_INCOMPLETE_LIST_ID);
  }

  public function syncGroupsComplete(array $data): void {
    $mails = array_keys($data);

    $existingMails = $this->getMailsInList($this->GROUPS_LIST_ID);

    $removedMails = array_values(array_diff($existingMails, $mails));
    $this->removeMailsFromList($removedMails, $this->GROUPS_LIST_ID);

    $body = ['EMAIL;GRUPPEN_HELFER_ID'];
    foreach ($data as $mail => $id) {
      $body[] = $mail. ';' . $id;
    }

    $this->importPeopleToList($body, $this->GROUPS_LIST_ID);
  }

  public function syncGroupsIncomplete(array $data): void {
    $mails = array_keys($data);

    $existingMails = $this->getMailsInList($this->GROUPS_INCOMPLETE_LIST_ID);

    $removedMails = array_values(array_diff($existingMails, $mails));
    $this->removeMailsFromList($removedMails, $this->GROUPS_INCOMPLETE_LIST_ID);

    $body = ['EMAIL;GRUPPEN_HELFER_ID'];
    foreach ($data as $mail => $id) {
      $body[] = $mail. ';' . $id;
    }

    $this->importPeopleToList($body, $this->GROUPS_INCOMPLETE_LIST_ID);
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  protected function getMailsInList(string $listId, $offset = 0): array {
    $apiClient = $this->getApiClient();

    $mails = [];

    try {
      $result = $apiClient->getContactsFromList($listId, NULL, 500);
    } catch (ApiException $e) {
      $this->logger->error('Error while getting contacts in list: ' . $e->getMessage() . ' ' . $e->getResponseBody());
      throw $e;
    }

    foreach ($result->getContacts() as $contact) {
      $mails[] = $contact['email'];
    }

    if ($result->getCount() > $offset + 500) {
      array_merge($mails, $this->getMailsInList($listId, $offset + 500));
    }

    return array_unique($mails);
  }

  protected function convertToBody(array $mails) {
    $body = ['EMAIL'];
    return array_merge($body, $mails);
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  protected function importPeopleToList(array $body, int $listId): void {
    if (count($body) == 0) {
      return;
    }

    $apiClient = $this->getApiClient();

    $requestContactImport = new RequestContactImport();
    $requestContactImport->setListIds([$listId]);
    $requestContactImport->setUpdateExistingContacts(FALSE);
    $requestContactImport->setFileBody(join("\n", $body));
    $requestContactImport->setNotifyUrl(Url::fromRoute('startklar.sendinblue.import_callback')->setAbsolute()->toString());

    try {
      $apiClient->importContacts($requestContactImport);
    } catch (ApiException $e) {
      $this->logger->error('Error while importing contacts: ' . $e->getMessage() . ' ' . $e->getResponseBody());
      throw $e;
    }
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  protected function removeMailsFromList(array $mails, int $listId): void {
    if (count($mails) == 0) {
      return;
    }

    $mails = array_unique($mails);

    $apiClient = $this->getApiClient();
    $removeContactsFromList = new RemoveContactFromList();
    $removeContactsFromList->setEmails($mails);

    try {
      $apiClient->removeContactFromList($listId, $removeContactsFromList);
    } catch (ApiException $e) {
      $this->logger->error('Error while removing contacts: ' . $e->getMessage() . ' ' . $e->getResponseBody());
      throw $e;
    }
  }

}
