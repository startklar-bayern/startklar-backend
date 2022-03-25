<?php

namespace Drupal\startklar\Controller;

use Drupal\image\Controller\ImageStyleDownloadController;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\HttpFoundation\Request;

class StartklarImageStyleDownloadController extends ImageStyleDownloadController {

  public function deliver(Request $request, $scheme, ImageStyleInterface $image_style) {
   $response = parent::deliver($request, $scheme, $image_style);
   $response->headers->set('Access-Control-Allow-Origin', '*');

   return $response;
  }


}
