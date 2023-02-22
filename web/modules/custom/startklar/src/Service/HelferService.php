<?php

namespace Drupal\startklar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\startklar\Model\Anmeldung;
use Drupal\startklar\Model\Anreise;
use Drupal\startklar\Model\HelferAnmeldung;

class HelferService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\startklar\Service\PersonService
   */
  private PersonService $personService;

  protected NodeStorageInterface $nodeStorage;

  /**
   * @var \Drupal\startklar\Service\AnreiseService
   */
  private AnreiseService $anreiseService;

  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              PersonService              $personService,
                              AnreiseService             $anreiseService) {
    $this->entityTypeManager = $entity_type_manager;

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    $this->personService = $personService;
    $this->anreiseService = $anreiseService;
  }

  /**
   * Find a helfer by its ID.
   *
   * @param $id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\startklar\Service\NotFoundException
   */
  public function getById($id): ?NodeInterface {
    $node =  $this->nodeStorage->load($id);

    if (!$node) {
      throw new NotFoundException();
    }

    return $node;
  }

  /**
   * Create a new helfer.
   *
   * @param $email
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function new(string $email): NodeInterface {
    $node = $this->createNode($email);

    return $node;
  }

  public function update(NodeInterface $helfer, HelferAnmeldung $anmeldung) {
    $publishedBefore = $helfer->isPublished();

    $this->setHelferValues($helfer, $anmeldung);
    $helfer->setPublished();
    $helfer->save();

    // If this is the first update, send notification mail
    if (!$publishedBefore) {
      mail("max.bachhuber@bahuma.io", "Neue Helfer-Anmeldung zu STARTKLAR", "https://backend.startklar.bayern/node/" . $helfer->id());
      mail("melanie.krapp@kolpingjugend-bayern.de", "Neue Helfer-Anmeldung zu STARTKLAR", "https://backend.startklar.bayern/node/" . $helfer->id());
    }
  }

  /**
   * Create a helfer node and save it.
   *
   * @param string $email
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNode(string $email): NodeInterface {
    $node = Node::create([
      'type' => 'helfer',
      'status' => NodeInterface::NOT_PUBLISHED,
      'title' => $email,
      'field_mail' => $email,
    ]);

    $node->save();

    return $node;
  }

  protected function setHelferValues(NodeInterface &$node, HelferAnmeldung $anmeldung) {
    // jobs
    $jobs = [];
    foreach ($anmeldung->jobs as $jobId) {
      $jobs[] = [
        'target_id' => $jobId,
      ];
    }
    $node->set('field_jobs', $jobs);

    $person = $anmeldung->person;

    // Unterbringung
    if (isset($anmeldung->unterbringung)) {
      $node->set('field_unterbringung', $anmeldung->unterbringung);
    } else {
      $node->set('field_unterbringung', NULL);
    }

    // Create person or update its data
    try {
      /** @var NodeInterface $personNode */
      $personNode = $this->personService->getById($person->id);
      $this->personService->update($personNode, $person);
    } catch (NotFoundException $e) {
      $personNode = $this->personService->new($person);
    }

    // Delete old person if it was changed
    $oldPerson = $node->get('field_person')->entity;

    if ($oldPerson && $oldPerson->id() !== $personNode->id()) {
      $this->personService->delete($node->get('field_person')->entity);
    }

    // Store person in node
    $node->set('field_person', $personNode);
  }

  public function toDto(NodeInterface $helferNode): HelferAnmeldung {
    $helfer = new HelferAnmeldung();

    $jobs = [];
    foreach ($helferNode->get('field_jobs') as $fieldItem) {
      $jobs[] = $fieldItem->target_id;
    }

    $helfer->jobs = $jobs;

    $person = $this->personService->toDto($helferNode->get('field_person')->entity);
    $helfer->person = $person;

    if (!empty($helferNode->get('field_unterbringung'))) {
      $helfer->unterbringung = $helferNode->get('field_unterbringung')->value;
    }

    return $helfer;
  }
}
