<?php

namespace Drupal\startklar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SendInBlueController extends ControllerBase {

  public function importCallback(Request $request) {
      $this->getLogger('startklar_sendinblue')->info('Import callback received this body: ' . print_r($_POST, true));

      return new Response('ack');
  }
}
