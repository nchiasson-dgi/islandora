<?php

/**
 * @file
 * This file contains all theme and preprocess functions.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\islandora\DublinCore;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_islandora_default(&$variables) {
  $islandora_object = $variables['islandora_object'];
  module_load_include('inc', 'islandora', 'includes/utilities');
  module_load_include('inc', 'islandora', 'includes/datastream');
  module_load_include('inc', 'islandora', 'includes/metadata');

  $variables['parent_collections'] = islandora_get_parents_from_rels_ext($islandora_object);

  $datastreams = [];
  foreach ($islandora_object as $ds) {
    try {
      $id = $ds->id;
      $label = $ds->label;
      $download_path = islandora_datastream_get_url($ds, 'download');
      $datastreams[$id]['id'] = $id;
      $datastreams[$id]['label'] = $label;
      $datastreams[$id]['label_link'] = islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $ds) ? Link::createFromRoute($label, 'islandora.download_datastream', ['object' => $islandora_object->id, 'datastream' => $id]) : $label;
      $datastreams[$id]['download_url'] = $download_path;
      $datastreams[$id]['mimetype'] = $ds->mimetype;
      $datastreams[$id]['size'] = islandora_datastream_get_human_readable_size($ds);
      $datastreams[$id]['created_date'] = $ds->createdDate->format("Y-m-d");
      $datastreams[$id]['class'] = Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $id));
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
  $variables['dc_array'] = isset($dc_array) ? $dc_array : [];
  $variables['islandora_dublin_core'] = isset($dc_object) ? $dc_object : NULL;

  $variables['metadata'] = islandora_retrieve_metadata_markup($islandora_object, TRUE);
  $variables['description'] = islandora_retrieve_description_markup($islandora_object);
  $variables['islandora_object_label'] = $islandora_object->label;
  if (isset($islandora_object['TN']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $islandora_object['TN'])) {
    $variables['islandora_thumbnail_url'] = URL::fromRoute('islandora.view_datastream', ['object' => $islandora_object->id, 'datastream' => 'TN']);
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_islandora_object_print(array &$variables) {
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
  $models = array_diff($object->models, ['fedora-system:FedoraObject-3.0']);
  foreach ($models as $model) {
    $model = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $model));
    $suggestions = theme_get_suggestions([$model, $pid], 'islandora_object_print');
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
  $query_params = UrlHelper::filterQueryParameters($_GET);

  $renderable_grid_link = Link::createFromRoute(
    t('Grid view'),
    '<current>',
    [],
    [
      'query' => array_merge($query_params, ['display' => 'grid']),
      'attributes' => ['class' => [$grid_display ? 'active' : '']],
    ]
  )->toRenderable();
  $renderable_list_link = Link::createFromRoute(
    t('List view'),
    '<current>',
    [],
    [
      'query' => array_merge($query_params, ['display' => 'list']),
      'attributes' => ['class' => [$list_display ? 'active' : '']],
    ]
  )->toRenderable();

  $variables['display_links'] = [
    $renderable_grid_link,
    $renderable_list_link,
  ];
  // Pager.
  $objects = $variables['objects'];
  $limit = $variables['limit'];
  $page = pager_default_initialize(count($objects), $limit);
  $objects = array_slice($objects, $page * $limit, $limit);

  $variables['pager'] = ['#type' => 'pager', 'quantity' => 10];

  $objects = array_map('islandora_objects_object_mapper', $objects);
  $theme = $grid_display ? 'islandora_objects_grid' : 'islandora_objects_list';
  $variables['content'] = [
    '#theme' => $theme,
    '#objects' => $objects,
  ];
  $variables['content']['#attached']['library'][] = 'islandora/islandora-objects';
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
  $object = islandora_object_load($object_id);
  $module_path = drupal_get_path('module', 'islandora');

  $url = Url::fromRoute('islandora.view_object', ['object' => $object_id]);
  $img = [
    '#theme' => 'image',
    '#uri' => ($object && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $object['TN']) ?
      Url::fromRoute('islandora.view_datastream_view', ['object' => $object_id, 'datastream' => 'TN'])->toString() :
      "$module_path/images/folder.png"),
  ];
  $img = \Drupal::service("renderer")->render($img);

  if ($object) {
    $description = NULL;
    if (isset($object['DC']) && islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $object['DC'])) {
      $dc = DublinCore::importFromXMLString($object['DC']->content);
      if ($dc) {
        $dc = $dc->asArray();
        $description = $dc['dc:description']['value'];
      }
    }
    return [
      'label' => $object->label,
      'class' => Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $object->id)),
      'link' => Link::fromTextAndUrl($object->label, $url),
      'thumb' => Link::fromTextAndUrl($img, $url),
      'description' => $description,
      'pid' => $object->id,
    ];
  }
  else {
    return [
      'label' => t('(Unknown)'),
      'class' => Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $object_id)),
      'link' => Link::fromTextAndUrl($object->label, $url),
      'thumb' => '',
      'description' => '',
      'pid' => $object_id,
    ];
  }
}

/**
 * Prepares variables for islandora_objects_subset templates.
 *
 * A variant of "islandora_objects" which accepts a subset of objects to theme.
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
 *
 * @see template_preprocess_islandora_objects()
 */
function template_preprocess_islandora_objects_subset(array &$variables) {
  $display = (empty($_GET['display'])) ? $variables['display'] : $_GET['display'];
  $grid_display = $display == 'grid';
  $list_display = !$grid_display;
  $query_params = UrlHelper::filterQueryParameters($_GET);

  $renderable_grid_link = Link::createFromRoute(
    t('Grid view'),
    '<current>',
    [],
    [
      'query' => array_merge($query_params, ['display' => 'grid']),
      'attributes' => ['class' => [$grid_display ? 'active' : '']],
    ]
  )->toRenderable();
  $renderable_list_link = Link::createFromRoute(
    t('List view'),
    '<current>',
    [],
    [
      'query' => array_merge($query_params, ['display' => 'list']),
      'attributes' => ['class' => [$list_display ? 'active' : '']],
    ]
  )->toRenderable();

  $variables['display_links'] = [
    $renderable_grid_link,
    $renderable_list_link,
  ];

  $variables['pager'] = [
    '#type' => 'pager',
    '#element' => $variables['pager_element'],
  ];
  $variables['content'] = [
    '#theme' => $grid_display ? 'islandora_objects_grid' : 'islandora_objects_list',
    '#objects' => $variables['objects'],
  ];
  $variables['content']['#attached']['library'][] = 'islandora/islandora-objects';

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
      drupal_set_message(t('Error retrieving object %s %t', ['%s' => $islandora_object->id, '%t' => $e->getMessage()]), 'error', FALSE);
    }
  }
  $variables['dc_array'] = isset($dc_object) ? $dc_object->asArray() : [];
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
      drupal_set_message(t('Error retrieving object %s %t', ['%s' => $islandora_object->id, '%t' => $e->getMessage()]), 'error', FALSE);
    }
  }
  $variables['dc_array'] = isset($dc_object) ? $dc_object->asArray() : [];
  if (isset($variables['dc_array']['dc:description']['value'])) {
    $variables['dc_array']['dc:description']['value'] = nl2br(Xss::filter($variables['dc_array']['dc:description']['value']));
  }
}
