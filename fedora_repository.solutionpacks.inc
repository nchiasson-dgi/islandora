<?php

/**
 * @file
 * Invokes a hook to any dependent modules asking them if their installations require
 * any fedora objects to be present. Modules implementing this hook should return an array
 * of arrays of the form:
 *
 * array( 'pid', 'path-to-foxml-file', 'dsid', 'path-to-datastream-file', int dsversion)
 *
 * where the last three options are optional. A module can either point to a simple
 * foxml file to install, or can specify a datastreamstream to check for, with a
 * path to load the datastream from if it isn't there. Optionally a version number
 * can be included, to enable updating of content model or collection policy streams
 * that may have been updated. THis is a simple whole number that should be incremented
 * when changed.  This value appears in as an attribute of the topmost element of the stream,
 * e.g.,:
 *
 * <?xml version="1.0" encoding="utf-8"?> <content_model name="Collection" version="2" ...
 *
 * Datastreams which don't have this element are assumed to be at version 0.
 */
function fedora_repository_solution_packs_page() {
  $enabled_solution_packs = module_invoke_all('required_fedora_objects');
  $output = '';
  foreach ($enabled_solution_packs as $solution_pack_module => $solution_pack_info) {
    $objects = array();
    foreach ($solution_pack_info as $field => $value) {
      switch ($field) {
        case 'title':
          $solution_pack_name = $value;
          break;
        case 'objects':
          $objects = $value;
          break;
      }
    }
    
    //FIXME:  Something seems wrong here...  Shouldn't be adding forms to Islandora core for individual solution packs...
    //  seems kinda like going very much in the wrong direction...  Wouldn't hook_form_alter in the implementing 
    //  modules be the way to go for customization?  They could look inside the given form for the presence of their module
    //  name in 'solution_pack_module' value within the form
    $output .= drupal_get_form('fedora_repository_solution_pack_form_' . $solution_pack_module, $solution_pack_module, $solution_pack_name, $objects);
  }

  return $output;
}

/**
 * Check for installed objects and add a 'Update' or 'Install' button if some objects are missing.
 * @param array $solution_pack
 */
function fedora_repository_solution_pack_form(&$form_state, $solution_pack_module, $solution_pack_name, $objects = array()) {
  // Check each object to see if it is in the repository.
  module_load_include('inc', 'fedora_repository', 'api/fedora_item');
  global $base_path;
  $needs_update = FALSE;
  $needs_install = FALSE;
  $form = array();
  $form['solution_pack_module'] = array(
    '#type' => 'hidden',
    '#value' => $solution_pack_module,
  );

  if (!$form_state['submitted']) {
    $form['soluction_pack_name'] = array(
      '#type' => 'markup',
      '#value' => t($solution_pack_name),
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
    );
    $form['objects'] = array(
      '#type' => 'fieldset',
      '#title' => "Objects",
      '#weight' => 10,
      '#attributes' => array('class' => 'collapsed'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $table_header = array('PID', 'Status');
    $table_rows = array();

    //TODO:  Should probably be made to deal with (XML) forms as well...
    foreach ($objects as $object) {
      $datastreams = NULL;
      if (isset($object['pid'])) {
        $pid = $object['pid'];

        $item = new Fedora_Item($pid);
        $table_row = array($object['pid']);
        $object_status = t('Up-to-date');
        if (!$item->exists()) {
          $object_status = 'Missing';
          $needs_install = TRUE;
        }
        else {
          if (isset($object['dsid']) && isset($object['datastream_file']) && isset($object['dsversion'])) {
            $datastreams = array(
              array(
                'dsid' => $object['dsid'],
                'datastream_file' => $object['datastream_file'],
                'dsversion' => $object['dsversion'],
              ),
            );
          }
          elseif (!empty($object['datastreams'])) {
            $datastreams = $object['datastreams'];
          }
          if (!empty($datastreams) && is_array($datastreams)) {
            foreach ($datastreams as $ds) {
              $ds_list = $item->get_datastreams_list_as_array();
              if (!array_key_exists($ds['dsid'], $ds_list)) {
                $needs_update = TRUE;
                $object_status = 'Missing datastream';
                break;
              }
              if (isset($ds['dsversion'])) {
                // Check if the datastream is versioned and needs updating.
                $installed_version = fedora_repository_get_islandora_datastream_version($item, $ds['dsid']);
                $available_version = fedora_repository_get_islandora_datastream_version(NULL, NULL, $ds['datastream_file']);
                if ($available_version > $installed_version) {
                  $needs_update = TRUE;
                  $object_status = 'Out of date';
                  break;
                }
              }
            }
          }
        }
        array_push($table_row, $object_status);
        $table_rows[] = $table_row;
      }
    }
    $form['objects']['table'] = array(
      '#type' => 'markup',
      '#value' => theme_table($table_header, $table_rows),
    );
  }

  $form['install_status'] = array(
    '#type' => 'markup',
    '#prefix' => '<strong>' . t('Object status:') . '&nbsp;</strong>',
    '#suffix' => '&nbsp;',
  );
  if (!$needs_install && !$needs_update) {
    $form['install_status']['#value'] = theme_image('misc/watchdog-ok.png') . t('All required objects are installed and up-to-date.');
  }
  else {
    $form['install_status']['#value'] = theme_image('misc/watchdog-warning.png') . t('Some objects must be re-ingested. See Objects list for details.');
  }
  $form['submit'] = array(
    '#value' => t('Install'),
    '#disabled' => !$needs_install && !$needs_update,
    '#type' => 'submit',
    '#name' => $solution_pack_module,
  );

  $form['#submit'] = array(
    'fedora_repository_solution_pack_form_submit',
  );
  return $form;
}

function fedora_repository_solution_pack_form_submit($form, &$form_state) {
  $what = $form_state;
  $module_name = $form_state['values']['solution_pack_module'];
  $solution_pack_info = module_invoke($module_name, 'required_fedora_objects');
  $batch = array(
    'title' => t('Installing / updating solution pack objects'),
    'file' => drupal_get_path('module', 'fedora_repository') . '/fedora_repository.module',
    'operations' => array(),
  );


  foreach ($solution_pack_info[$module_name]['objects'] as $object) {
    // Add this object to the batch job queue.
    $batch['operations'][] = array('fedora_repository_batch_reingest_object', array($object));
  }
  batch_set($batch);
}

function solution_pack_add_form($form_name, $form_xml) {
  $result = db_result(db_query('SELECT name FROM {xml_forms} WHERE name = "%s"', $form_name));
  if (!$result) {
    $object = new stdClass();
    $object->name = $form_name;
    $object->form = $form_xml;
    $result = drupal_write_record('xml_forms', $object);
    drupal_set_message(t('Added form "%name"', array('%name' => $form_name)));
  }
}

function solution_pack_add_form_association($content_model, $form_name, $dsid = 'MODS', $title_field = "['titleInfo']['title']", $transform = 'mods_to_dc.xsl') {
  $result = db_result(db_query('SELECT content_model FROM {islandora_content_model_forms} WHERE content_model = "%s" and form_name = "%s"', $content_model, $form_name));
  if (!$result) { //If this association doesn't already exist...
    $object = new stdClass();
    $object->content_model = $content_model;
    $object->form_name = $form_name;
    $object->dsid = $dsid;
    $object->title_field = $title_field;
    $object->transform = $transform;
    $result = drupal_write_record('islandora_content_model_forms', $object);
    if ($result === FALSE) {
      drupal_set_message(t('Failed to add association between content model "%content_model" and form "%form_name".', array(
      '%content_model' => $content_model,
      '%form_name' => $form_name
    )), 'error');
    }
    elseif ($result === SAVED_NEW) {
      drupal_set_message(t('Added association between content model "%content_model" and form "%form_name".', array(
      '%content_model' => $content_model,
      '%form_name' => $form_name
    )), 'status');
    }
    elseif ($result === SAVED_UPDATED) {
      drupal_set_message(t('Updated association between content model "%content_model" and form "%form_name".', array(
        '%content_model' => $content_model,
        '%form_name' => $form_name
      )), 'status');
    }
    else {
      drupal_set_message(t('Unknown state encountered while associating content model "%content_model" and form "%form_name".', array(
        '%content_model' => $content_model,
        '%form_name' => $form_name
      )), 'error');
    }
  }
}

