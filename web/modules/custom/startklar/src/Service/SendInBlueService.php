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

  protected int $GRUPPENANMELDUNG_TEMPLATE_ID;

  protected int $TEILNEHMER_LIST_ID;

  protected bool $isDebugMode;

  protected LoggerInterface $logger;

  public function __construct(RequestStack $requestStack) {
    $this->API_KEY = getenv('SEND_IN_BLUE_API_KEY');
    $this->NEWSLETTER_LIST_ID = intval(getenv('SEND_IN_BLUE_NEWSLETTER_LIST_ID'));
    $this->DOI_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_DOUBLE_OPT_IN_TEMPLATE_ID'));
    $this->FRONTEND_URL = getenv('FRONTEND_URL');
    $this->FRONTEND_URL_ANMELDUNG = getenv('FRONTEND_URL_ANMELDUNG');
    $this->GRUPPENANMELDUNG_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_GRUPPENANMELDUNG_TEMPLATE_ID'));
    $this->TEILNEHMER_LIST_ID = intval(getenv('SEND_IN_BLUE_TEILNEHMER_LIST_ID'));
    $this->isDebugMode = str_starts_with($requestStack->getMainRequest()->getHttpHost(), 'localhost');

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

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  public function syncTeilnehmer(array $mails): void {
    $existingMails = $this->getMailsInList($this->TEILNEHMER_LIST_ID);

    $removedMails = array_values(array_diff($existingMails, $mails));
    $this->removeMailsFromList($removedMails, $this->TEILNEHMER_LIST_ID);

    $this->importMailsToList($mails, $this->TEILNEHMER_LIST_ID);
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

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  protected function importMailsToList(array $mails, int $listId): void {
    if (count($mails) == 0) {
      return;
    }

    $body = ['EMAIL'];
    $body = array_merge($body, $mails);

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
      $this->logger->error('Error while removing contacs: ' . $e->getMessage() . ' ' . $e->getResponseBody());
      throw $e;
    }
  }

}
