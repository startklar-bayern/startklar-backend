<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Http\RequestStack;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\ApiException;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\AddContactToList;
use SendinBlue\Client\Model\CreateContact;
use SendinBlue\Client\Model\CreateDoiContact;
use SendinBlue\Client\Model\RemoveContactFromList;
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

  public function __construct(RequestStack $requestStack) {
    $this->API_KEY = getenv('SEND_IN_BLUE_API_KEY');
    $this->NEWSLETTER_LIST_ID = intval(getenv('SEND_IN_BLUE_NEWSLETTER_LIST_ID'));
    $this->DOI_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_DOUBLE_OPT_IN_TEMPLATE_ID'));
    $this->FRONTEND_URL = getenv('FRONTEND_URL');
    $this->FRONTEND_URL_ANMELDUNG = getenv('FRONTEND_URL_ANMELDUNG');
    $this->GRUPPENANMELDUNG_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_GRUPPENANMELDUNG_TEMPLATE_ID'));
    $this->TEILNEHMER_LIST_ID = intval(getenv('SEND_IN_BLUE_TEILNEHMER_LIST_ID'));
    $this->isDebugMode = str_starts_with($requestStack->getMainRequest()->getHttpHost(), 'localhost');
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
      \Drupal::logger('startklar_sendinblue')->info('Send Double Opt In Mail. ' . print_r([
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
      \Drupal::logger('startklar_sendinblue')->info('Send Gruppenanmeldung Email ' . print_r([
          '$sendSmtpEmail' => $sendSmtpEmail,
        ], TRUE));
    } else {
      $apiInstance->sendTransacEmail($sendSmtpEmail);
    }
  }

  /**
   * @throws \SendinBlue\Client\ApiException
   */
  public function addTeilnehmer($mail) {
    $apiClient = $this->getApiClient();

    $contact = new CreateContact();
    $contact->setEmail($mail);
    $contact->setListIds([$this->TEILNEHMER_LIST_ID]);

    try {
      if ($this->isDebugMode) {
        \Drupal::logger('startklar_sendinblue')->info('Add teilnehmer to list. ' . print_r([
            '$contact' => $contact,
          ], TRUE));
      } else {
        $apiClient->createContact($contact);
      }
    } catch (ApiException $e) {
      $body = json_decode($e->getResponseBody());

      if ($body && $body->code == 'duplicate_parameter') {
        // Contact already exists. Add it to list.
        try {
          $apiClient->addContactToList($this->TEILNEHMER_LIST_ID, new AddContactToList(['emails' => [$mail]]));
        } catch (ApiException $e) {
          $body = json_decode($e->getResponseBody());
          if ($body && $body->code == 'invalid_parameter') {
            // Contact is already in list. everything fine.
          } else {
            throw $e;
          }
        }
      } else {
        throw $e;
      }
    }
  }

  public function removeTeilnehmer($mail) {
    $apiClient = $this->getApiClient();

    try {
      $data = new RemoveContactFromList([
        'emails' => [$mail],
      ]);

      if ($this->isDebugMode) {
        \Drupal::logger('startklar_sendinblue')->info('Remove teilnehmer from list. ' . print_r([
            '$data' => $data,
          ], TRUE));
      } else {
        $apiClient->removeContactFromList($this->TEILNEHMER_LIST_ID, $data);
      }

    } catch (ApiException $e) {
      // If it cannot be removed, it was not in the list.
    }
  }

}
