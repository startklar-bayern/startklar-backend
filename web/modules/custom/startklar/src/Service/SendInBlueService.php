<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Url;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\CreateDoiContact;

class SendInBlueService {

  protected string $API_KEY;

  protected int $NEWSLETTER_LIST_ID;

  protected int $DOI_TEMPLATE_ID;

  protected string $FRONTEND_URL;

  public function __construct() {
    $this->API_KEY = getenv('SEND_IN_BLUE_API_KEY');
    $this->NEWSLETTER_LIST_ID = intval(getenv('SEND_IN_BLUE_LIST_ID'));
    $this->DOI_TEMPLATE_ID = intval(getenv('SEND_IN_BLUE_DOUBLE_OPT_IN_TEMPLATE_ID'));
    $this->FRONTEND_URL = getenv('FRONTEND_URL');
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

}
