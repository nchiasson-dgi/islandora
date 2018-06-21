<?php

/**
 * @file
 * The confirmation forms used to regenerate derivatives.
 */

/**
 * Creates a batch to go out and re-create all of the derivatives for an object.
 *
 * @param AbstractObject $object
 *   A AbstractObject representing an object within Fedora.
 *
 * @return array
 *   An array specifying the Drupal batch.
 */
function islandora_regenerate_object_derivatives_batch(AbstractObject $object) {
  module_load_include('inc', 'islandora', 'includes/derivatives');
  islandora_remove_defer_derivatives_flag($object);
  return [
    'title' => t('Regenerating all derivatives for @label', ['@label' => $object->label]),
    'operations' => islandora_do_batch_derivatives($object, ['force' => TRUE]),
    'init_message' => t('Preparing to regenerate derivatives...'),
    'progress_message' => t('Time elapsed: @elapsed <br/>Estimated time remaining @estimate.'),
    'error_message' => t('An error has occurred.'),
    'file' => drupal_get_path('module', 'islandora') . '/includes/regenerate_derivatives.form.inc',
    'finished' => 'islandora_regenerate_derivative_batch_finished',
  ];
}

/**
 * Creates a batch to go out and re-create the derivative for a datastream.
 *
 * @param AbstractDatastream $datastream
 *   A AbstractDatastream representing a datastream on an object within Fedora.
 *
 * @return array
 *   An array specifying the Drupal batch.
 */
function islandora_regenerate_datastream_derivative_batch(AbstractDatastream $datastream) {
  module_load_include('inc', 'islandora', 'includes/derivatives');
  islandora_remove_defer_derivatives_flag($datastream->parent);
  return [
    'title' => t('Regenerating derivatives for the @dsid datastream', ['@dsid' => $datastream->id]),
    'operations' => islandora_do_batch_derivatives($datastream->parent, [
      'force' => TRUE,
      'destination_dsid' => $datastream->id,
    ]),
    'init_message' => t('Preparing to regenerate derivatives...'),
    'progress_message' => t('Time elapsed: @elapsed <br/>Estimated time remaining @estimate.'),
    'error_message' => t('An error has occurred.'),
    'file' => drupal_get_path('module', 'islandora') . '/includes/regenerate_derivatives.form.inc',
    'finished' => 'islandora_regenerate_derivative_batch_finished',
  ];
}

/**
 * Wrapper to call out to batch operations.
 *
 * @param string $function
 *   The name of the function we are calling for derivatives.
 * @param bool|string $file
 *   FALSE if there is no file to load, the path to require otherwise.
 * @param string $pid
 *   The pid of the object we are performing.
 * @param bool $force
 *   Whether we are forcing derivative regeneration or not.
 * @param array $hook
 *   The derivative definition.
 * @param DrushBatchContext|array $context
 *   The context of the current batch operation.
 */
function islandora_derivative_perform_batch_operation($function, $file, $pid, $force, array $hook, &$context) {
  if ($file) {
    require_once $file;
  }
  if (function_exists($function)) {
    $logging = call_user_func($function, islandora_object_load($pid), $force, $hook);
    if (!empty($logging)) {
      $context['results']['logging'][] = $logging;
    }
  }
  else {
    \Drupal::logger('islandora')->error('Unable to call derivative function @function as it was not found!', ['@function' => $function]);
  }
}

/**
 * Finished function for derivative batch regeneration.
 *
 * @param bool $success
 *   Success passed from the batch.
 * @param array $results
 *   An array of results passed from the batch.
 * @param array $operations
 *   An array of operations passed from the batch.
 */
function islandora_regenerate_derivative_batch_finished($success, array $results, array $operations) {
  module_load_include('inc', 'islandora', 'includes/derivatives');
  if (!empty($results['logging'])) {
    islandora_derivative_logging($results['logging']);
  }
}
