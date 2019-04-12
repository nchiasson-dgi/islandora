<?php

namespace Drupal\islandora\Utility;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper for state access.
 */
trait StateTrait {

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
    $defaults = static::stateDefaults();

    if (!array_key_exists($var, $defaults)) {
      throw new Exception(t('@var is not one of ours...', [
        '@var' => $var,
      ]));
    }

    return $defaults[$var];
  }

  /**
   * Set a single thing.
   *
   * @param string $var
   *   The key to set.
   * @param mixed $value
   *   The value to set for the key.
   *
   * @throws Exception
   *   If the passed $var is not one described in our ::stateDefaults().
   */
  public static function stateSet($var, $value) {
    if (!array_key_exists($var, static::stateDefaults())) {
      throw new Exception(t('@var is not one of ours...', [
        '@var' => $var,
      ]));
    }
    \Drupal::state()->set($var, $value);
  }

  /**
   * Set ALL our values, assuming similarly named fields in a form.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state from which to pull values. We expect value names to match
   *   the keys from ::stateDefaults().
   */
  public function stateSetAll(FormStateInterface $form_state) {
    \Drupal::state()->setMultiple(array_intersect_key(
      $form_state->getValues(),
      static::stateDefaults()
    ));
  }

  /**
   * Delete all the values defined via ::stateDefaults().
   */
  public static function stateDeleteAll() {
    \Drupal::state()->deleteMultiple(array_keys(static::stateDefaults()));
  }

}
