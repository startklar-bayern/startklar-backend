<?php

/**
 * Implements hook_views_data_alter().
 */
function startklar_views_data_alter(array &$data) {
  $data['node']['startklar_temp_storage_link'] = [
    'title' => 'TempStorage Link',
    'group' => t('Content'),
    'field' => [
      'title' => 'TempStorage Link',
      'id' => 'startklar_temp_storage_link',
    ],
  ];
}
