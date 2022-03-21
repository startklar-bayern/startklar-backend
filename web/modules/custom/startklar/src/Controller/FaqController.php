<?php

namespace Drupal\startklar\Controller;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\startklar\Model\Faq;
use Drupal\startklar\Model\FaqAskBody;
use Drupal\startklar\ValidationException;
use Laminas\Diactoros\Response\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Startklar routes.
 */
class FaqController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected EmailValidatorInterface $emailValidator;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mailManager, EmailValidatorInterface $emailValidator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mailManager;
    $this->emailValidator = $emailValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('email.validator'),
    );
  }

  #[OA\Get(
    path: "/faqs", description: "Get all FAQs", tags: ["FAQs"],
    responses: [
      new OA\Response(
        response: 200,
        description: "OK",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Faq"))
      ),
    ]
  )]
  public function index() {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $nids = $nodeStorage->getQuery()
      ->condition('type', 'faq')
      ->condition('status', TRUE)
      ->sort('field_weight', 'ASC')
      ->execute();

    $faqNodes = $nodeStorage->loadMultiple($nids);

    $faqs = [];

    foreach ($faqNodes as $node) {
      $faq = new Faq();

      $faq->id = $node->id();
      $faq->question = $node->label();
      $faq->answer = $node->get('body')->value;

      $faqs[] = $faq;
    }

    return new JsonResponse($faqs);
  }

  #[OA\Post(
    path: '/faq/question',
    operationId: 'ask_question',
    description: 'Ask a question about the event',
    requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#components/schemas/FaqAskBody')),
    tags: ['FAQs'],
    responses: [
      new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(
        type: "array",
        items: new OA\Items(
          properties: [
            new OA\Property('status', type: 'string', example: 'success'),
            new OA\Property('message', type: 'string', example: 'Sent'),
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
            new OA\Property('message', type: 'string', example: 'Field mail is required'),
          ],
          type: 'object',
        )
      )),
      new OA\Response(response: 500, description: 'Server error'),
    ]
  )]
  public function askQuestion(Request $request) {
    try {
      $body = FaqAskBody::fromJson($request->getContent());

      if (!$this->emailValidator->isValid($body->mail)) {
        throw new ValidationException('mail', 'is invalid');
      }

      $mailText = "Es wurde eine Frage über Startklar gestellt:\n\n";
      $mailText .= "Von " . $body->name . "(" . $body->mail . ")\n\n";
      $mailText .= $body->question;

      $this->mailManager->mail('startklar', 'faq', 'info@startklar.bayern', 'de', [
        'body' => $mailText,
      ]);

      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'status' => 'success',
        'message' => 'Sent',
      ]);

    } catch (ValidationException $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'status' => 'error',
        'code' => 'VALIDATION_ERROR',
        'message' => 'Validation error... Field ' . $e->getField() . ' ' . $e->getFieldError(),
      ], 400);
    } catch (\Exception $e) {
      \Drupal::logger('startklar')
        ->error('Could not send faq mail.' . $e->getMessage(), ['exception' => $e]);

      return new JsonResponse([
        'status' => 'error',
        'code' => 'SERVER_ERROR',
        'message' => 'Internal server error. Please contact support.',
      ], 500);
    }
  }

}
