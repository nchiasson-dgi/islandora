<?php

namespace Drupal\islandora\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class IslandoraRepositoryEvent extends Event {
  protected $eventName;

  /**
   * Constructor.
   *
   * @param string $event_name
   *   The name of the Rules event to trigger.
   * @param array $args
   *   The array of arguments used by the given event.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   If the given event does not appear to be defined.
   */
  public function __construct($event_name, $args = []) {
    $this->eventName = $event_name;

    $event_manager = \Drupal::service('plugin.manager.rules_event');
    $event_info = $event_manager->getDefinition($event_name);
    $context = $event_info['context'];
    $mapped_args = array_combine(array_keys($context), array_slice($args, 0, count($context)));
    foreach ($mapped_args as $key => $value) {
      $this->{$key} = $value;
    }
  }

  /**
   * Accessor for the event name.
   *
   * @return string
   *   The name of the Rules event being triggered.
   */
  public function getEventName() {
    return $this->eventName;
  }

  /**
   * Factory method.
   *
   * @param string $event_name
   *   The name of the Rules event to trigger.
   * @param array $args
   *   The array of arguments used by the given event.
   *
   * @return IslandoraRepositoryEvent|bool
   *   If $event_name is a valid Rules event, an instance of this class;
   *   otherwise, boolean FALSE.
   */
  public static function create($event_name, $args = []) {
    try {
      return new static($event_name, $args);
    }
    catch (PluginNotFoundException $e) {
      // No-op; most likely the event type does not really exist.
      return FALSE;
    }
  }

}
