<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraMetadataDisplayForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraMetadataDisplayForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_metadata_display_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/solution_packs.inc');
    $form = [];
    $defined_displays = \Drupal::moduleHandler()->invokeAll('islandora_metadata_display_info');
    if (!empty($defined_displays)) {
      $no_viewer = [];
      $no_viewer['none'] = [
        'label' => t('None'),
        'description' => t("Don't show any metadata for displaying"),
      ];
      $viewers = array_merge_recursive($no_viewer, $defined_displays);

      $form['viewers'] = [
        '#type' => 'item',
        '#title' => t('Select a viewer'),
        '#description' => t('Preferred metadata display for Islandora. These may be provided by third-party modules.'),
        '#tree' => TRUE,
        '#theme' => 'islandora_viewers_table',
      ];

      foreach ($viewers as $name => $profile) {
        $options[$name] = '';
        $form['viewers']['name'][$name] = [
          '#type' => 'hidden',
          '#value' => $name,
        ];
        $form['viewers']['label'][$name] = [
          '#type' => 'item',
          '#markup' => $profile['label'],
        ];
        $form['viewers']['description'][$name] = [
          '#type' => 'item',
          '#markup' => $profile['description'],
        ];
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // $form['viewers']['configuration'][$name] = array(
        //         '#type' => 'item',
        //         '#markup' => (isset($profile['configuration']) AND $profile['configuration'] != '') ? l(t('configure'), $profile['configuration']) : '',
        //       );

      }
      $form['viewers']['default'] = [
        '#type' => 'radios',
        '#options' => isset($options) ? $options : [],
        '#default_value' => \Drupal::config('islandora.settings')->get('islandora_metadata_display'),
      ];
    }
    else {
      $form['viewers']['no_viewers'] = ['#markup' => t('No viewers detected.')];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('islandora.settings')->set('islandora_metadata_display', $form_state->getValue(['viewers', 'default']))->save();
    drupal_set_message(t('The configuration options have been saved.'));
  }

}
?>
