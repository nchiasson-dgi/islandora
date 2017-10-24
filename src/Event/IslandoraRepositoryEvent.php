<?php

namespace Drupal\islandora\Event;

use Symfony\Component\EventDispatcher\Event;

class IslandoraRepositoryEvent extends Event {
  protected $eventName;

  public function __construct($event_name) {
    $this->eventName = $event_name;
  }

  public function getEventName() {
    return $this->eventName;
  }

  public static function create($event_name, $info) {
    $instance = new static($event_name);
    foreach ($info as $key => $value) {
      $instance->{$key} = $value;
    }
    return $instance;
  }
}
