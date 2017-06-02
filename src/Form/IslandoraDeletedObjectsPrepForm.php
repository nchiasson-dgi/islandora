<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraDeletedObjectsPrepForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraDeletedObjectsPrepForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_deleted_objects_prep_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $serialized_chosen = NULL) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $chosen_contentmodels = [];
    if ($serialized_chosen) {
      $chosen_contentmodels = unserialize($serialized_chosen);
    }
    $contentmodels_with_deleted_members = islandora_get_contentmodels_with_deleted_members();
    $elegible_contentmodels = array_keys($contentmodels_with_deleted_members);
    if (empty($contentmodels_with_deleted_members)) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => t("There are no deleted objects in this repository."),
      ];
      return $form;
    }
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => t("Select content models of deleted objects."),
    ];
    $form['mapped_contentmodels'] = [
      '#type' => 'hidden',
      '#value' => $contentmodels_with_deleted_members,
    ];
    $table_element = islandora_content_model_select_table_form_element(NULL);

    foreach ($table_element['#options'] as $option) {
      if (!in_array($option['pid'], $elegible_contentmodels)) {
        unset($table_element['#options'][$option['pid']]);
      }
      if (array_key_exists($option['pid'], $chosen_contentmodels)) {
        $table_element['#default_value'][$option['pid']] = TRUE;
      }
    }

    $form['contentmodels'] = $table_element;
    $form['next'] = [
      '#type' => 'submit',
      '#value' => t('Next'),
    ];

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $content_models = $form_state->getValue(['contentmodels']);
    $chosen = function($element) {
      return $element;
    };
    $serialized_contentmodels = serialize(array_filter($content_models, $chosen));
    drupal_goto("admin/islandora/restore/manage/$serialized_contentmodels");
  }

}
?>
