<?php

namespace Drupal\startklar\Plugin\views\field;

use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Provides TempStorageLink field handler.
 *
 * @ViewsField("startklar_temp_storage_link")
 *
 * @DCG
 * The plugin needs to be assigned to a specific table column through
 * hook_views_data() or hook_views_data_alter().
 * For non-existent columns (i.e. computed fields) you need to override
 * self::query() method.
 */
class TempStorageLink extends LinkBase {


  protected function getUrlInfo(ResultRow $row) {
    $node = $this->getEntity($row);

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->getEntityTypeManager()->getStorage('node');
    $result = $nodeStorage->getQuery()
      ->condition('type', 'temp_storage')
      ->condition('title', $node->label())
      ->accessCheck(FALSE)
      ->execute();

    if (count($result) > 0) {
      /** @var \Drupal\node\NodeInterface $tempStorageNode */
      $tempStorageNode = $nodeStorage->load(array_values($result)[0]);
      return $tempStorageNode->toUrl('edit',);
    } else {
      return $node->toUrl();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $node */
    if (!$this->getUrlInfo($row)) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('TempStorage');
  }

}
