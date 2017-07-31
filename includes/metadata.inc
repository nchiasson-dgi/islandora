<?php
/**
 * @file
 * Defines functions used when viewing metadata displays on Islandora objects.
 */

/**
 * Retrieves the metadata display markup for an Islandora object.
 *
 * @param AbstractObject $object
 *   An AbstractObject representing an object within Fedora.
 * @param bool $print
 *   Whether the object is being printed.
 *
 * @return string
 *   Markup to be rendered for display on Islandora object pages.
 */
function islandora_retrieve_metadata_markup(AbstractObject $object, $print = FALSE) {
  $viewers = \Drupal::moduleHandler()->invokeAll('islandora_metadata_display_info');
  $viewer = \Drupal::config('islandora.settings')->get('islandora_metadata_display');
  $markup = '';
  if (isset($viewers[$viewer]['metadata callback'])) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $hooks = islandora_build_hook_list(ISLANDORA_METADATA_OBJECT_ALTER, $object->models);
    \Drupal::moduleHandler()->alter($hooks, $object);
    $markup = call_user_func($viewers[$viewer]['metadata callback'], $object, $print);
    // The callback doesn't have any markup provided for this particular object,
    // default back to the dublin_core display.
    if ($markup === FALSE) {
      $markup = call_user_func($viewers['dublin_core']['metadata callback'], $object, $print);
    }
  }
  return $markup;
}

/**
 * Retrieves the metadata display description for an Islandora object.
 *
 * @param AbstractObject $object
 *   An AbstractObject representing an object within Fedora.
 *
 * @return string
 *   Markup to be rendered for description on Islandora object pages.
 */
function islandora_retrieve_description_markup(AbstractObject $object) {
  $viewers = \Drupal::moduleHandler()->invokeAll('islandora_metadata_display_info');
  $viewer = \Drupal::config('islandora.settings')->get('islandora_metadata_display');
  $markup = '';
  if (isset($viewers[$viewer]['description callback'])) {
    $hooks = islandora_build_hook_list(ISLANDORA_METADATA_OBJECT_DESCRIPTION_ALTER, $object->models);
    \Drupal::moduleHandler()->alter($hooks, $object);
    $markup = call_user_func($viewers[$viewer]['description callback'], $object);
    // The callback doesn't have any markup provided for this particular object,
    // default back to the dublin_core display.
    if ($markup === FALSE) {
      $markup = call_user_func($viewers['dublin_core']['description callback'], $object);
    }
  }
  return $markup;
}

/**
 * Metadata display callback for rendering Dublin Core metadata.
 *
 * @param AbstractObject $object
 *   An AbstractObject representing an object within Fedora.
 * @param bool $print
 *   Whether the display is being printed or not.
 *
 * @return string
 *   Markup representing the rendered metadata from Dublin Core.
 */
function islandora_metadata_display_callback(AbstractObject $object, $print = FALSE) {
  $elements = array(
    'islandora_object' => $object,
    'print' => $print,
  );
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// return theme('islandora_dublin_core_display', $elements);

}

/**
 * Metadata description callback for rendering Dublin Core description.
 *
 * @param AbstractObject $islandora_object
 *   An AbstractObject representing an object within Fedora.
 *
 * @return string
 *   Markup representing the rendered metadata from Dublin Core.
 */
function islandora_metadata_description_callback(AbstractObject $islandora_object) {
  $elements = array(
    'islandora_object' => $islandora_object,
  );
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// return theme('islandora_dublin_core_description', $elements);

}
