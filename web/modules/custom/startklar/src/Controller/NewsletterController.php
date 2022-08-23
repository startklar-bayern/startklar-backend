<?php

namespace Drupal\startklar\Controller;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\startklar\Model\NewsletterSubscribeBody;
use Drupal\startklar\Service\SendInBlueService;
use Drupal\startklar\ValidationException;
use OpenApi\Attributes as OA;
use SendinBlue\Client\ApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Startklar routes.
 */
class NewsletterController extends ControllerBase {

  protected EmailValidatorInterface $emailValidator;

  protected $entityTypeManager;

  private SendInBlueService $sendInBlueService;

  const CACHE_TAG = 'startklar_newsletter_subscriber_count';

  /**
   * The controller constructor.
   *
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email.validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EmailValidatorInterface $email_validator, EntityTypeManagerInterface $entity_type_manager, SendInBlueService $sendInBlueService) {
    $this->emailValidator = $email_validator;
    $this->entityTypeManager = $entity_type_manager;
    $this->sendInBlueService = $sendInBlueService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('entity_type.manager'),
      $container->get('send_in_blue')
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
      // Get body
      $body = NewsletterSubscribeBody::fromJson($request->getContent());


      // Validations
      if (!$this->emailValidator->isValid($body->mail)) {
        throw new ValidationException('mail', 'is invalid');
      }

      if (!$body->privacy_accepted) {
        throw new ValidationException('privacy_accepted', 'has to be true');
      }


      try {
        $this->sendInBlueService->subscribeToNewsletter($body->mail);

        Cache::invalidateTags([self::CACHE_TAG]);

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

  #[OA\Get(
    path: '/newsletter',
    description: 'Get informations about the newsletter',
    tags: ['Newsletter'],
    responses: [
      new OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
          required: ['subscriber_count'],
          properties: [
            new OA\Property('subscriber_count', description: 'The amount of users that are currently subscribed to the newsletter.', type: 'integer', format: 'int64'),
          ], type: 'object'
        )),
    ]
  )]
  public function info() {
    if ($cache = \Drupal::cache()->get(self::CACHE_TAG)) {
      $subscriberCount = $cache->data;
    }
    else {
      $subscriberCount = $this->sendInBlueService->getNewsletterSubscriberCount();
      \Drupal::cache()
        ->set(self::CACHE_TAG, $subscriberCount, CacheBackendInterface::CACHE_PERMANENT, [self::CACHE_TAG]);
    }

    // Set a minimum number of subscribers to display in the frontend.
    $minimumValue = 11;

    $data = [
      'subscriber_count' => $subscriberCount > $minimumValue ? $subscriberCount : $subscriberCount + $minimumValue,
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags([self::CACHE_TAG]);
    $cache->addCacheContexts(['url']);

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency($cache);

    return $response;
  }

  protected function successResponse(): Response {
    return new JsonResponse([
      'status' => 'success',
      'message' => 'Subscribed',
    ]);
  }

}
