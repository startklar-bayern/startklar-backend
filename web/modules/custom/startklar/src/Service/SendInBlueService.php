<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Url;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\CreateDoiContact;
use SendinBlue\Client\Model\SendSmtpEmail;
use SendinBlue\Client\Model\SendSmtpEmailTo;

class SendInBlueService {

  protected string $API_KEY;

  protected int $NEWSLETTER_LIST_ID;

  protected int $DOI_TEMPLATE_ID;

  protected string $FRONTEND_URL;

  protected string $FRONTEND_URL_ANMELDUNG;

  protected string $GRUPPENANMELDUNG_TEMPLATE_ID;

  public function __construct() {
    $this->API_KEY = getenv('SEND_IN_BLUE_API_KEY');
    $this->NEWSLETTER_LIST_ID = intval(getenv('SEND_IN_BLUE_LIST_ID'));
    $this->DOI_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_DOUBLE_OPT_IN_TEMPLATE_ID'));
    $this->FRONTEND_URL = getenv('FRONTEND_URL');
    $this->FRONTEND_URL_ANMELDUNG = getenv('FRONTEND_URL_ANMELDUNG');
    $this->GRUPPENANMELDUNG_TEMPLATE_ID = getenv('SEND_IN_BLUE_GRUPPENANMELDUNG_TEMPLATE_ID');
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

    $apiClient->createDoiContact($createDoiContact);
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

    $apiInstance->sendTransacEmail($sendSmtpEmail);
  }

}
