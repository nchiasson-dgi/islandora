<?php

namespace Drupal\islandora\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Represent our hooks/events, as used by Rules.
 */
class RepositoryEvent extends Event {
  /**
   * The name of the event being triggered.
   *
   * @var string
   */
  protected $eventName;

  /**
   * The arguments for the event.
   *
   * @var array
   */
  protected $inputArgs;

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
  public function __construct($event_name, array $args = []) {
    $this->eventName = $event_name;
    $this->inputArgs = $args;

    if (\Drupal::moduleHandler()->moduleExists('rules')) {
      $event_manager = \Drupal::service('plugin.manager.rules_event');
      try {
        $event_info = $event_manager->getDefinition($event_name);
        $context = $event_info['context'];
        $mapped_args = array_combine(array_keys($context), array_slice($args, 0, count($context)));
        foreach ($mapped_args as $key => $value) {
          $this->{$key} = $value;
        }
      }
      catch (PluginNotFoundException $e) {
        // No-op; most likely the event type does not really exist.
      }
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
   * Accessor for the args.
   *
   * @return array
   *   The array of arguments.
   */
  public function getArgs() {
    return $this->inputArgs;
  }

}
