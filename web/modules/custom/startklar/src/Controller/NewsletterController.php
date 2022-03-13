<?php

namespace Drupal\startklar\Controller;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\startklar\Model\NewsletterSubscribeBody;
use Drupal\startklar\ValidationException;
use GuzzleHttp\Client;
use OpenApi\Attributes as OA;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\ApiException;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\AddContactToList;
use SendinBlue\Client\Model\CreateContact;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Startklar routes.
 */
class NewsletterController extends ControllerBase {

  /**
   * The email.validator service.
   */
  protected EmailValidatorInterface $emailValidator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email.validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EmailValidatorInterface $email_validator, EntityTypeManagerInterface $entity_type_manager) {
    $this->emailValidator = $email_validator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('entity_type.manager')
    );
  }

  #[OA\Post(
    path: '/newsletter',
    operationId: 'newsletter_subscribe',
    description: 'Subscribe an email address to the newsletter',
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/NewsletterSubscribeBody')),
    tags: ['Newsletter'],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
        type: "array",
        items: new OA\Items(
          properties: [
            new OA\Property('status', type: 'string', example: 'success'),
            new OA\Property('message', type: 'string', example: 'subscribed'),
          ],
          type: 'object',
        )
      )),
      new OA\Response(response: 400, description: "Invalid body", content: new OA\JsonContent(
        type: "array",
        items: new OA\Items(
          properties: [
            new OA\Property('status', type: 'string', example: 'error'),
            new OA\Property('code', type: 'string', example: 'VALIDATION_ERROR'),
            new OA\Property('message', type: 'string', example: 'Field privacy_accepted has to be true'),
          ],
          type: 'object',
        )
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function subscribe(Request $request) {
    try {
      $apiKey = getenv('SEND_IN_BLUE_API_KEY');
      $listId = getenv('SEND_IN_BLUE_LIST_ID');

      // Get body
      $body = NewsletterSubscribeBody::fromJson($request->getContent());


      // Validations
      if (!$this->emailValidator->isValid($body->mail)) {
        throw new ValidationException('mail', 'is invalid');
      }

      if (!$body->privacy_accepted) {
        throw new ValidationException('privacy_accepted', 'has to be true');
      }


      // Get SendInBlueClient
      $config = Configuration::getDefaultConfiguration()
        ->setApiKey('api-key', $apiKey);
      $apiInstance = new ContactsApi(new Client(), $config);

      // Create contact
      $contact = new CreateContact();
      $contact->setEmail($body->mail);


      try {
        $result = $apiInstance->createContact($contact);

        $contactId = $result->getId();

        // Add contact to mailing list
        $addContactToList = new AddContactToList();
        $addContactToList->setIds([$contactId]);

        $apiInstance->addContactToList($listId, $addContactToList);

        return $this->successResponse();
      } catch (ApiException $e) {
        $body = json_decode($e->getResponseBody(), FALSE, JSON_THROW_ON_ERROR);

        if (!isset($body->code)) {
          throw $e;
        }

        // Contact already exists, everything is fine.
        if ($body->code == 'duplicate_parameter') {
          return $this->successResponse();
        }

        // Unhandled, throw on.
        throw $e;
      }
    } catch (ValidationException $e) {
      return new JsonResponse([
        'status' => 'error',
        'code' => 'VALIDATION_ERROR',
        'message' => 'Validation error... Field ' . $e->getField() . ' ' . $e->getFieldError(),
      ], 400);
    } catch (\Exception $e) {
      \Drupal::logger('startklar')
        ->error('Could not subscribe user to newsletter.' . $e->getMessage(), ['exception' => $e]);

      return new JsonResponse([
        'status' => 'error',
        'code' => 'SERVER_ERROR',
        'message' => 'Internal server error. Please contact support.',
      ], 500);
    }
  }

  protected function successResponse(): Response {
    return new JsonResponse([
      'status' => 'success',
      'message' => 'Subscribed',
    ]);
  }

}
