<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for configuration forms in Islandora that require invoking hooks.
 */
class IslandoraMetadataDisplayForm extends IslandoraModuleHandlerAdminForm {

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
    $rows = [
      'none' => [
        'label' => t('None'),
        'description' => t("Don't show any metadata for displaying"),
      ],
    ];
    foreach ($defined_displays as $display_name => $values) {
      $configuration = FALSE;
      if (isset($values['configuration'])) {
        $configuration = [
          '#title' => $this->t('configure'),
          '#type' => 'link',
          '#url' => islandora_get_url_from_path_or_route($values['configuration']),
        ];
      }
      $rows[$display_name] = [
        'label' => $values['label'],
        'description' => $values['description'],
        'configuration' => $configuration ? ['data' => $configuration] : '',
      ];
    }
    $form['viewers'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Select a viewer'),
      '#caption' => $this->t('Preferred metadata display for Islandora. These may be provided by third-party modules.'),
      '#header' => [
        'label' => $this->t('Label'),
        'description' => $this->t('Description'),
        'configuration' => $this->t('Configuration'),
      ],
      '#options' => $rows,
      '#default_value' => $this->config('islandora.settings')->get('islandora_metadata_display'),
      '#empty' => $this->t('No viewers detected.'),
      '#multiple' => FALSE,
    ];
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
