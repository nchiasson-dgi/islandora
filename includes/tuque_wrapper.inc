<?php

/**
 * @file
 * Wrapper around the tuque library, allows for autoloading of Islandora Tuque
 * Objects.
 *
 * @todo Overload functions and apply pre/post hooks.
 */

/**
 * Allow modules to alter an object before a mutable event occurs.
 */
function islandora_alter_object(AbstractObject $object, array &$context) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  \Drupal::moduleHandler()->alter(islandora_build_hook_list('islandora_object', $object->models), $object, $context);
}

/**
 * Allow modules to alter a datastream before a mutable event occurs.
 */
function islandora_alter_datastream(AbstractObject $object, AbstractDatastream $datastream, array &$context) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  $types = [];
  foreach ($object->models as $model) {
    $types[] = "{$model}_{$datastream->id}";
  }
  \Drupal::moduleHandler()->alter(islandora_build_hook_list('islandora_datastream', $types), $object, $datastream, $context);
}

/**
 * Constructs a list of hooks from the given parameters and invokes them.
 */
function islandora_invoke_object_hooks($hook, array $models) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  return islandora_invoke_hook_list($hook, $models, array_slice(func_get_args(), 2));
}

/**
 * Constructs a list of hooks from the given parameters and invokes them.
 */
function islandora_invoke_datastream_hooks($hook, array $models, $dsid) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  $refinements = [];
  foreach ($models as $model) {
    $refinements[] = "{$model}_{$dsid}";
  }
  return islandora_invoke_hook_list($hook, $refinements, array_slice(func_get_args(), 3));
}
