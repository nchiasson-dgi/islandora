<?php

/**
 * @file
 * Functions for generating/validating/submitting the add datastream form.
 */

/**
 * Callback for an autocomplete field in the admin add datastream form.
 *
 * It lists the missing required (may be optional) datastreams.
 *
 * @param AbstractObject $object
 *   The object used to check for missing required datastreams used to populate
 *    the options in this callback.
 * @param string $query
 *   vThe user query to match against the missing required datastreams.
 */
function islandora_add_datastream_form_autocomplete_callback(AbstractObject $object, $query = '') {
  module_load_include('inc', 'islandora', 'includes/content_model');
  module_load_include('inc', 'islandora', 'includes/utilities');
  $dsids = array_keys(islandora_get_missing_datastreams_requirements($object));
  $dsids = array_combine($dsids, $dsids);
  $query = trim($query);
  if (!empty($query)) {
    $filter = function ($id) use ($query) {
      return stripos($id, $query) !== FALSE;
    };
    $dsids = array_filter($dsids, $filter);
  }
  drupal_json_output($dsids);
}
