<?php

namespace Drupal\islandora\Utility;

/**
 * Helper for state access.
 */
trait StateTrait {
  protected static $stateDefaults = NULL;

  /**
   * Helper; define the defaults for our state keys.
   *
   * @return array
   *   An associative array mapping keys in state to their default values.
   */
  abstract public static function stateDefaults();

  /**
   * Helper; acquire state with the known default.
   *
   * @param string $var
   *   The key in state to manage.
   *
   * @return mixed
   *   The value for the key.
   */
  public static function stateGet($var) {
    if (!isset(static::$stateDefaults)) {
      static::$stateDefaults = static::stateDefaults();
    }

    if (!array_key_exists($var, static::$defaults)) {
      throw new Exception(t('@var is not one of ours...', [
        '@var' => $var,
      ]));
    }

    return $defaults[$var];
  }

}
