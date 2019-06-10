<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for configuration forms in Islandora that require invoking hooks.
 */
class MetadataDisplayForm extends ModuleHandlerAdminForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_metadata_display_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/solution_packs.inc');
    module_load_include('inc', 'islandora', 'includes/utilities');

    $form = [
      '#tree' => TRUE,
    ];
    $defined_displays = $this->moduleHandler->invokeAll('islandora_metadata_display_info');
    $form['viewers'] = [
      '#type' => 'table',
      '#title' => $this->t('Select a viewer'),
      '#caption' => $this->t('Preferred metadata display for Islandora. These may be provided by third-party modules.'),
      '#header' => [
        'label' => $this->t('Label'),
        'description' => $this->t('Description'),
        'configuration' => $this->t('Configuration'),
      ],
      '#default_value' => $this->config('islandora.settings')->get('islandora_metadata_display'),
      '#empty' => $this->t('No viewers detected.'),
      '#multiple' => FALSE,
      '#tableselect' => TRUE,
    ];
    foreach ($defined_displays as $display_name => $values) {
      $form['viewers']['none'] = [
        'label' => [
          '#plain_text' => $this->t('None'),
        ],
        'description' => [
          '#plain_text' => $this->t("Don't show any metadata for displaying"),
        ],
        'configuration' => [],
      ];

      $form['viewers'][$display_name] = [
        'label' => [
          '#plain_text' => $values['label'],
        ],
        'description' => [
          '#plain_text' => $values['description'],
        ],
      ];
      if (isset($values['configuration'])) {
        $form['viewers'][$display_name]['configuration'] = [
          '#title' => $this->t('configure'),
          '#type' => 'link',
          '#url' => islandora_get_url_from_path_or_route($values['configuration']),
        ];
      }
      else {
        $form['viewers'][$display_name]['configuration'] = [];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('islandora.settings')->set('islandora_metadata_display', $form_state->getValue('viewers'))->save();
    parent::submitForm($form, $form_state);
  }

}
