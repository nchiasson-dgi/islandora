<?php

/**
 * @file
 * This file contains all theme and preprocess functions.
 */

use Drupal\islandora\DublinCore;
use Drupal\Core\Url;

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_islandora_default(&$variables) {
  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_js('misc/form.js');

  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_js('misc/collapse.js');
  $islandora_object = $variables['islandora_object'];
  module_load_include('inc', 'islandora', 'includes/utilities');
  module_load_include('inc', 'islandora', 'includes/datastream');
  module_load_include('inc', 'islandora', 'includes/metadata');

  $variables['parent_collections'] = islandora_get_parents_from_rels_ext($islandora_object);

  $datastreams = array();
  foreach ($islandora_object as $ds) {
    try {
      $pid = $islandora_object->id;
      $id = $ds->id;
      $label = $ds->label;
      $download_path = islandora_datastream_get_url($ds, 'download');
      $datastreams[$id]['id'] = $id;
      $datastreams[$id]['label'] = $label;
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $datastreams[$id]['label_link'] = islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $ds) ?
//         l($label, $download_path) :
//         $label;
      //$datastreams[$id]['label_link'] = islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $ds) ? \Drupal\Core\Link::createFromRoute($label, $download_path) : $label;
      $datastreams[$id]['download_url'] = $download_path;
      $datastreams[$id]['mimetype'] = $ds->mimetype;
      $datastreams[$id]['size'] = islandora_datastream_get_human_readable_size($ds);
      $datastreams[$id]['created_date'] = $ds->createdDate->format("Y-m-d");
      $datastreams[$id]['class'] = \Drupal\Component\Utility\Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $id));
    }
    catch (RepositoryException $e) {

    }
  }
  $variables['datastreams'] = $datastreams;
  // Objects in fcrepo4 don't always contain a DC datastream.
  if (isset($islandora_object['DC']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $islandora_object['DC'])) {
    $dc_object = DublinCore::importFromXMLString($islandora_object['DC']->content);
    $dc_array = $dc_object->asArray();
  }
  // We should eventually remove the DC object and dc_array code as it only
  // exists to not break legacy implementations.
  $variables['dc_array'] = isset($dc_array) ? $dc_array : array();
  $variables['islandora_dublin_core'] = isset($dc_object) ? $dc_object : NULL;

  $variables['metadata'] = islandora_retrieve_metadata_markup($islandora_object, TRUE);
  $variables['description'] = islandora_retrieve_description_markup($islandora_object);
  $variables['islandora_object_label'] = $islandora_object->label;
  if (isset($islandora_object['TN']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $islandora_object['TN'])) {
    // @FIXME
// url() expects a route name or an external URI.
// $variables['islandora_thumbnail_url'] = url("islandora/object/{$islandora_object->id}/datastream/TN/view");
  //$variables['islandora_thumbnail_url'] = URL::fromRoute('islandora.view_datastream', array('object' => $islandora_object->id, 'datastream' => 'TN'));
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_islandora_object_print(array &$variables) {
  // Apply the print CSS in non print context.
  $only_print_media = function($o) {
    return $o['media'] == 'print';
  };
  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// $css = array_filter(drupal_add_css(), $only_print_media);

  foreach ($css as $data => $options) {
    $options['media'] = 'all';
    // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_css($data, $options);

  }
  // Allow modules to define their own theme suggestions for the given object.
  // Suggestions are defined as islandora_object_print__CMODEL__PID. For
  // example for the image object islandora:1234.
  // islandora_object_print__islandora_imagecmodel
  // islandora_object_print__islandora_imagecmodel__islandora_1234
  // would be valid theme suggestions. This step up does not support objects
  // with multiple content models in which each content model provides a theme
  // suggestion.
  $object = $variables['object'];
  $pid = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $object->id));
  $models = array_diff($object->models, array('fedora-system:FedoraObject-3.0'));
  foreach ($models as $model) {
    $model = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $model));
    $suggestions = theme_get_suggestions(array($model, $pid), 'islandora_object_print');
    $variables['theme_hook_suggestions'] = array_merge($variables['theme_hook_suggestions'], $suggestions);
  }
}

/**
 * Implements theme_hook().
 */
function theme_islandora_object_print(array &$variables) {
  return \Drupal::service("renderer")->render($variables['content']);
}

/**
 * Implements hook_preprocess_theme().
 */
function template_preprocess_islandora_objects(array &$variables) {
  $display = (empty($_GET['display'])) ? 'grid' : $_GET['display'];
  $grid_display = $display == 'grid';
  $list_display = !$grid_display;
  $query_params = \Drupal\Component\Utility\UrlHelper::filterQueryParameters($_GET);
  $variables['display_links'] = array(
    array(
      'title' => t('Grid view'),
      'href' => \Drupal\Core\Url::fromRoute("<current>")->toString(),
      'attributes' => array(
        'class' => array(
          $grid_display ? 'active' : '',
        ),
      ),
      'query' => array('display' => 'grid') + $query_params,
    ),
    array(
      'title' => t('List view'),
      'href' => \Drupal\Core\Url::fromRoute("<current>")->toString(),
      'attributes' => array(
        'class' => array(
          $list_display ? 'active' : '',
        ),
      ),
      'query' => array('display' => 'list') + $query_params,
    ),
  );
  // Pager.
  $objects = $variables['objects'];
  $limit = $variables['limit'];
  $page = pager_default_initialize(count($objects), $limit);
  $objects = array_slice($objects, $page * $limit, $limit);
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// $variables['pager'] = theme('pager', array('quantity' => 10));


  $objects = array_map('islandora_objects_object_mapper', $objects);
  $theme = $grid_display ? 'islandora_objects_grid' : 'islandora_objects_list';
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// $variables['content'] = theme($theme, array('objects' => $objects));

  $module_path = drupal_get_path('module', 'islandora');
  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_css("$module_path/css/islandora.objects.css");

}

/**
 * Helper function to map objects to their values to be used in templates.
 *
 * @param string $object_id
 *   The ID of the object for which to produce a list of values.
 *
 * @return array
 *   An associative array of values, including:
 *   - label: A string containing object's label.
 *   - class: A string containing an HTML class to add to markup representing
 *     the object.
 *   - link: A string containing a textual HTML link to the object.
 *   - thumb: A string containing an image HTML link to the object.
 *   - description: A string containing a description of the object.
 *   - pid: The object's PID.
 */
function islandora_objects_object_mapper($object_id) {
  $o = islandora_object_load($object_id);

  $module_path = drupal_get_path('module', 'islandora');

  $url = "islandora/object/{$object_id}";
  $img = array(
    '#theme' => 'image',
    '#path' => ($o && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $o['TN']) ?
      "$url/datastream/TN/view" :
      "$module_path/images/folder.png"),
    '#attributes' => array(),
  );
  $img = \Drupal::service("renderer")->render($img);

  if ($o) {
    $link_options = array('html' => TRUE, 'attributes' => array('title' => $o->label));
    $description = NULL;
    if (isset($o['DC']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $o['DC'])) {
      $dc = DublinCore::importFromXMLString($o['DC']->content);
      if ($dc) {
        $dc = $dc->asArray();
        $description = $dc['dc:description']['value'];
      }
    }
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// return array(
//       'label' => $o->label,
//       'class' => \Drupal\Component\Utility\Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $o->id)),
//       'link' => l($o->label, $url, $link_options),
//       'thumb' => l($img, $url, $link_options),
//       'description' => $description,
//       'pid' => $o->id,
//     );

  }
  else {
    $link_options = array('html' => TRUE, 'attributes' => array('title' => t('(Unknown)')));
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// return array(
//       'label' => t('(Unknown)'),
//       'class' => \Drupal\Component\Utility\Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $object_id)),
//       'link' => l(t('(Unknown)'), $url, $link_options),
//       'thumb' => '',
//       'description' => '',
//       'pid' => $object_id,
//     );

  }
}

/**
 * Prepares variables for islandora_objects_subset templates.
 *
 * A variant of "islandora_objects" which accepts a subset of object to theme.
 *
 * @see template_preprocess_islandora_objects()
 *
 * @param array $variables
 *   An associative array containing:
 *   - objects: An array of PIDs to render for the given page.
 *   - limit: An integer indicating the number of items per page, so we can
 *     render a pager.
 *   - total: An integer indicating the total number of items in the set, so
 *     can render a pager.
 *   - pager_element: An integer identifying which "pager" this display should
 *     use. Defaults to 0.
 *   - display: The default display to use when one is not provided in the URL.
 *     One of:
 *     - 'grid'
 *     - 'list'
 *   This function sets:
 *   - display_links: An array containing link structure, to allow the view to
 *     be toggled between a "grid" and "list" view.
 *   - pager: A renderable array for the pager.
 *   - content: A renderable array for the main content of the page.
 */
function template_preprocess_islandora_objects_subset(&$variables) {
  $display = (empty($_GET['display'])) ? $variables['display'] : $_GET['display'];
  $grid_display = $display == 'grid';
  $list_display = !$grid_display;
  $query_params = \Drupal\Component\Utility\UrlHelper::filterQueryParameters($_GET);

  // XXX: In l(), Drupal automatically adds the "active" class if it looks like
  // you are generating a link to the same page, based on the path and language.
  // Here, we use the "language" side of things to assert our links belong to a
  // non-existent language, so we can take control of adding our "active" class.
  $language_hack = new stdClass();
  $language_hack->language = 'a-non-existent-language';

  $variables['display_links'] = array(
    array(
      'title' => t('Grid view'),
      'href' => \Drupal\Core\Url::fromRoute("<current>")->toString(),
      'attributes' => array(
        'class' => array(
          $grid_display ? 'active' : '',
        ),
      ),
      'query' => array('display' => 'grid') + $query_params,
      'language' => $language_hack,
    ),
    array(
      'title' => t('List view'),
      'href' => \Drupal\Core\Url::fromRoute("<current>")->toString(),
      'attributes' => array(
        'class' => array(
          $list_display ? 'active' : '',
        ),
      ),
      'query' => array('display' => 'list') + $query_params,
      'language' => $language_hack,
    ),
  );

  $variables['pager'] = array(
    '#theme' => 'pager',
    '#element' => $variables['pager_element'],
  );
  $module_path = drupal_get_path('module', 'islandora');
  $variables['content'] = array(
    '#attached' => array(
      'css' => array(
        "$module_path/css/islandora.objects.css",
      ),
    ),
    '#theme' => $grid_display ? 'islandora_objects_grid' : 'islandora_objects_list',
    '#objects' => $variables['objects'],
  );
}

/**
 * Process variables for islandora_objects_subset templates.
 */
function template_process_islandora_objects_subset(&$variables) {
  pager_default_initialize($variables['total'], $variables['limit'], $variables['pager_element']);
  $variables['pager'] = \Drupal::service("renderer")->render($variables['pager']);
  $variables['content']['#objects'] = array_map('islandora_objects_object_mapper', $variables['content']['#objects']);
  $variables['content'] = \Drupal::service("renderer")->render($variables['content']);
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_islandora_dublin_core_display(array &$variables) {
  $islandora_object = $variables['islandora_object'];
  if (islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $islandora_object['DC'])) {
    try {
      $dc = $islandora_object['DC']->content;
      $dc_object = DublinCore::importFromXMLString($dc);
    }
    catch (Exception $e) {
      drupal_set_message(t('Error retrieving object %s %t', array('%s' => $islandora_object->id, '%t' => $e->getMessage())), 'error', FALSE);
    }
  }
  $variables['dc_array'] = isset($dc_object) ? $dc_object->asArray() : array();
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_islandora_dublin_core_description(array &$variables) {
  $islandora_object = $variables['islandora_object'];
  if (islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $islandora_object['DC'])) {
    try {
      $dc = $islandora_object['DC']->content;
      $dc_object = DublinCore::importFromXMLString($dc);
    }
    catch (Exception $e) {
      drupal_set_message(t('Error retrieving object %s %t', array('%s' => $islandora_object->id, '%t' => $e->getMessage())), 'error', FALSE);
    }
  }
  $variables['dc_array'] = isset($dc_object) ? $dc_object->asArray() : array();
  if (isset($variables['dc_array']['dc:description']['value'])) {
    $variables['dc_array']['dc:description']['value'] = nl2br(\Drupal\Component\Utility\Xss::filter($variables['dc_array']['dc:description']['value']));
  }
}
