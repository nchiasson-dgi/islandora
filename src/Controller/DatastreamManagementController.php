<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\islandora\Utility\Links;

use AbstractObject;
use AbstractDatastream;

/**
 * Class DatastreamManagementController.
 *
 * @package Drupal\islandora\Controller
 */
class DatastreamManagementController extends ControllerBase {
  const VERSION_PERM = ISLANDORA_VIEW_DATASTREAM_HISTORY;

  protected $links;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->links = new Links($this->config('islandora.settings'), $this->moduleHandler());
  }

  /**
   * Generate the content for the manage/datastreams route.
   */
  public function content(AbstractObject $object) {
    module_load_include('inc', 'islandora', 'includes/breadcrumb');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $output = [];
    foreach (islandora_build_hook_list(ISLANDORA_EDIT_HOOK, $object->models) as $hook) {
      $temp = $this->moduleHandler()->invokeAll($hook, [$object]);
      if (!empty($temp)) {
        $output = array_merge_recursive($output, $temp);
      }
    }
    if (empty($output)) {
      // Add in the default, if we did not get any results.
      $output = $this->defaultContent($object);
    }
    arsort($output);
    $this->moduleHandler()->alter(ISLANDORA_EDIT_HOOK, $object, $output);
    return $output;
  }

  /**
   * Generate the default content.
   *
   * @param \AbstractObject $object
   *   The object for which we are generating content.
   *
   * @return array
   *   A renderable array for the datastream table.
   */
  protected function defaultContent(AbstractObject $object) {
    $output = [
      '#type' => 'table',
      '#header' => $this->defaultHeader($object),
    ];

    foreach ($object as $dsid => $datastream) {
      $output["datastream-$dsid"] = $this->defaultRowContent($datastream);
    }

    return $output;
  }

  /**
   * Helper; check if the current user has access to view datastream versions.
   */
  protected function canViewVersions() {
    return $this->currentUser()->hasPermission(static::VERSION_PERM);
  }

  /**
   * Generate the table header for the datastream table.
   *
   * @param \AbstractObject $object
   *   The object for which a table of datastreams is being generated.
   *
   * @return array
   *   An array as accepted by the 'table' type for the header.
   */
  protected function defaultHeader(AbstractObject $object) {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'control_group' => $this->t('Type'),
      'mime_type' => $this->t('Mime type'),
      'size' => $this->t('Size'),
    ];

    if ($this->canViewVersions()) {
      $header['versions'] = $this->t('Versions');
    }
    $header['operations'] = [
      'data' => $this->t('Operations'),
      'colspan' => 5,
    ];
    return $header;
  }

  /**
   * Generate a row for the datastream table.
   *
   * @param \AbstractDatastream $datastream
   *   The datastream for which the row is being generated.
   *
   * @return array
   *   An array as accepted by the 'table' type for a row.
   */
  protected function defaultRowContent(AbstractDatastream $datastream) {
    $row = [
      'id' => $this->links->view($datastream),
      'label' => [
        '#plain_text' => $datastream->label,
      ],
      'control_group' => [
        '#markup' => islandora_control_group_to_human_readable($datastream->controlGroup),
      ],
      'mime_type' => [
        '#plain_text' => $datastream->mimetype,
      ],
      'size' => [
        '#markup' => format_size($datastream->size),
      ],
    ];

    if (islandora_datastream_access(static::VERSION_PERM, $datastream)) {
      $row['versions'] = $this->links->versions($datastream);
    }
    elseif ($this->canViewVersions()) {
      // Can view versions, but not for the given datastream.
      $row['versions'] = '';
    }

    $row['operation_replace'] = $this->links->replace($datastream);
    $row['operation_download'] = $this->links->download($datastream);
    $row['operation_edit'] = $this->links->edit($datastream);
    $row['operation_delete'] = $this->links->delete($datastream);
    $row['operation_regenerate'] = $this->links->regenerate($datastream);

    return $row;
  }

}
