<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use AbstractObject;

/**
 * Class ObjectManagementOverviewController.
 *
 * @package Drupal\islandora\Controller
 */
class ObjectManagementOverviewController extends ControllerBase {

  /**
   * Generate renderable array for the object management overview.
   */
  public function content(AbstractObject $object) {
    module_load_include('inc', 'islandora', 'includes/utilities');

    $to_item = function ($model) {
      if ($model == 'fedora-system:FedoraObject-3.0') {
        return FALSE;
      }

      $loaded = islandora_object_load($model);
      return $loaded ?
        [
          '#type' => 'link',
          '#title' => $loaded->label,
          '#url' => Url::fromRoute('islandora.view_object', [
            'object' => $loaded->id,
          ]),
        ] :
        $this->t('@cmodel - (This content model is not in this Islandora repository.)', [
          '@cmodel' => $loaded->id,
        ]);
    };
    $links = array_filter(array_map($to_item, $object->models));
    $output = [
      'models' => [
        '#type' => 'item',
        '#title' => $this->t('Models'),
        '#title_display' => 'invisible',
        '#description' => $this->formatPlural(count($links), "This object's behavior is defined by the Islandora content model:", "This object's behavior is defined by the Islandora content models:"),
        '#description_display' => 'before',
        'list' => [
          '#theme' => 'item_list',
          '#items' => $links,
        ],
      ],
    ];
    $hooks = islandora_build_hook_list(ISLANDORA_OVERVIEW_HOOK, $object->models);
    foreach ($hooks as $hook) {
      $temp = $this->moduleHandler()->invokeAll($hook, [$object]);
      if (!empty($temp)) {
        arsort($temp);
        $output = array_merge_recursive($output, $temp);
      }
    }
    $this->moduleHandler()->alter($hooks, $object, $output);
    return $output;
  }

}
