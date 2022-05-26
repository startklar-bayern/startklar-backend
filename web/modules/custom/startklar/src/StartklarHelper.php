<?php

namespace Drupal\startklar;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\startklar\Model\Image;
use Drupal\startklar\Model\ImageWithTitle;

class StartklarHelper {

  public static function buildImage(FieldItemInterface $image, string $imageStyle, CacheableMetadata &$cache, $withTitle = false) : Image|ImageWithTitle {
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
    $fileUrlGenerator = \Drupal::service('file_url_generator');

    /** @var \Drupal\image\ImageStyleStorageInterface $imageStyleStorage */
    $imageStyleStorage = \Drupal::entityTypeManager()->getStorage('image_style');

    /** @var \Drupal\image\Entity\ImageStyle $previewImageStyle */
    $previewImageStyle = $imageStyleStorage->load($imageStyle);

    /** @var \Drupal\file\Entity\File $file */
    $file = $image->entity;

    $cache->addCacheableDependency($file);
    $cache->addCacheableDependency($previewImageStyle);

    if ($withTitle) {
      $pic = new ImageWithTitle();
    } else {
      $pic = new Image();
    }

    $pic->width = $image->get('width')->getCastedValue();
    $pic->height = $image->get('height')->getCastedValue();
    $pic->altText = $image->get('alt')->getValue();
    $pic->url = $fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    $pic->previewUrl = $previewImageStyle->buildUrl($file->getFileUri());

    if ($withTitle && strlen($image->get('title')->getValue()) > 0) {
      $pic->title = $image->get('title')->getValue();
    }

    return $pic;
  }

}
