<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraDeletedObjectsManageForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraDeletedObjectsManageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_deleted_objects_manage_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $serialized_chosen = NULL) {
    $form['previous'] = [
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#attributes' => [
        'source' => 'previous'
        ],
    ];

    $chosen_contentmodels = unserialize($serialized_chosen);
    $content_models_with_deleted = islandora_get_contentmodels_with_deleted_members();
    foreach ($chosen_contentmodels as $contentmodel) {
      if (!array_key_exists($contentmodel, $content_models_with_deleted)) {
        unset($chosen_contentmodels[$contentmodel]);
      }
    }

    if (empty($chosen_contentmodels)) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => t("There are no deleted objects with the selected content models in this repository."),
      ];
      return $form;
    }

    if (is_array($chosen_contentmodels)) {
      foreach ($chosen_contentmodels as $key => $value) {
        if (in_array($key, $content_models_with_deleted)) {
          $chosen_contentmodels[$key] = $content_models_with_deleted[$key];
        }
      }
    }

    $tuque = islandora_get_tuque_connection();
    $repository = $tuque->repository;
    // Query brings back fedora-system:FedoraObject-3.0, doubling the results.
    $total = $repository->ri->countQuery(islandora_get_deleted_query($chosen_contentmodels), 'sparql') / 2;
    $limit = 25;
    if ($total < 28) {
      $limit = $total;
    }
    $current_page = pager_default_initialize($total, $limit);
    $query_limit = $limit * 2;
    $offset = $current_page * $query_limit;
    $options = islandora_get_deleted_objects($chosen_contentmodels, $query_limit, $offset);

    foreach ($options as &$option) {
      $option['content_model'] = $content_models_with_deleted[$option['content_model']];
    }
    $form['serialized_chosen'] = [
      '#type' => 'hidden',
      '#value' => $serialized_chosen,
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $form['pager'] = array(
    //     '#type' => 'markup',
    //     '#markup' => theme('pager', array('quantity', count($options))),
    //   );

    $form['propogate'] = [
      '#title' => t('Apply changes to related objects?'),
      '#default_value' => TRUE,
      '#description' => t("Objects associated with selected objects will also be purged/restored. ie page objects associated with a book object."),
      '#type' => 'checkbox',
    ];
    $form['chosen'] = [
      '#type' => 'hidden',
      '#value' => $chosen_contentmodels,
    ];
    $form['objects_to_process'] = [
      '#type' => 'tableselect',
      '#header' => [
        'title' => t('Name'),
        'pid' => t('PID'),
        'content_model' => t('Content Model'),
      ],
      '#multiple' => TRUE,
      '#options' => $options,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Restore selected objects'),
      '#attributes' => [
        'source' => 'restore'
        ],
    ];
    if (\Drupal::currentUser()->hasPermission(ISLANDORA_PURGE)) {
      $form['purge'] = [
        '#type' => 'submit',
        '#value' => t('Irrevocably purge selected objects'),
        '#attributes' => [
          'source' => 'purge'
          ],
      ];
    }
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $serialized_chosen = !$form_state->getValue(['serialized_chosen']) ? $form_state->getValue(['serialized_chosen']) : NULL;

    if (!$form_state->get(['clicked_button', '#attributes', 'source']) && $form_state->get(['clicked_button', '#attributes', 'source']) == 'previous') {
      drupal_goto("admin/islandora/restore/prep/$serialized_chosen");
    }
    if ($form_state->get(['clicked_button', '#attributes', 'source']) == 'restore') {
      $descriptor = "Restoring";
      $action = 'islandora_restore_object_by_pid';
    }
    if ($form_state->get(['clicked_button', '#attributes', 'source']) == 'purge') {
      $descriptor = "Purging";
      $action = 'islandora_purge_object_by_pid';
    }
    $objects_to_process = array_filter($form_state->getValue(['objects_to_process']));
    $pids_to_restore = $objects_to_process;
    if ($form_state->getValue(['propogate'])) {
      foreach ($objects_to_process as $pid) {
        $fedora_object = islandora_object_load($pid);
        $temp = islandora_invoke_hook_list(ISLANDORA_UPDATE_RELATED_OBJECTS_PROPERTIES_HOOK, $fedora_object->models, [
          $fedora_object
          ]);
        if (!empty($temp)) {
          $pids_to_restore = array_merge_recursive($pids_to_restore, $temp);
        }
      }
    }
    $batch = [
      'title' => t('@descriptor selected objects', [
        '@descriptor' => $descriptor
        ]),
      'file' => drupal_get_path('module', 'islandora') . '/includes/manage_deleted_objects.inc',
      'operations' => [],
    ];

    foreach ($pids_to_restore as $pid) {
      $batch['operations'][] = [
        $action,
        [$pid],
      ];
    }
    batch_set($batch);
    batch_process("admin/islandora/restore/manage/$serialized_chosen");
  }

}
?>
