<?php

namespace Drupal\startklar\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Startklar event subscriber.
 */
class StartklarSubscriber implements EventSubscriberInterface {
  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    if (str_starts_with($event->getRequest()->getPathInfo(), '/sites/default/files/styles/')) {
      $event->getResponse()->headers->set('Access-Control-Allow-Origin', '*');
    }
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

}
