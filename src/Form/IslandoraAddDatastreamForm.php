<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraAddDatastreamForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraAddDatastreamForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_add_datastream_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractObject $object = NULL) {
    module_load_include('inc', 'islandora', 'includes/content_model');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $form_state->loadInclude('islandora', 'inc', 'includes/add_datastream.form');
    $form_state->set(['object_id'], $object->id);

    // @deprecated Storing objects in $form_state is asking for a bad time...
    // Causes issues with derivative generation when we try to use it.
    $form_state->set([
      'object'
      ], $object);
    $datastream_requirements = islandora_get_missing_datastreams_requirements($object);
    $unused_datastreams = array_keys($datastream_requirements);
    $unused_datastreams = "'" . implode("', '", $unused_datastreams) . "'";
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    return [
      '#redirect' => "islandora/object/{$object->id}",
      '#attributes' => [
        'enctype' => 'multipart/form-data'
        ],
      'dsid_fieldset' => [
        '#type' => 'fieldset',
        '#title' => t('Add a Datastream'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'dsid' => [
          '#title' => 'Datastream ID',
          '#description' => t("An ID for this stream that is unique to this object. Must start with a letter and contain only alphanumeric characters, dashes and underscores. The following datastreams are defined by this content model but don't currently exist: <strong>@unused_dsids</strong>.", [
            '@unused_dsids' => $unused_datastreams
            ]),
          '#type' => 'textfield',
          '#size' => 64,
          '#maxlength' => 64,
          '#required' => TRUE,
          '#element_validate' => [
            'islandora_add_datastream_form_field_is_not_an_existing_datastream_id',
            'islandora_add_datastream_form_field_starts_with_a_letter',
            'islandora_add_datastream_form_field_is_valid_dsid',
          ],
          '#autocomplete_path' => "islandora/object/{$object->id}/manage/datastreams/add/autocomplete",
        ],
        'label' => [
          '#title' => 'Datastream Label',
          '#required' => TRUE,
          '#size' => 64,
          '#maxlength' => 64,
          '#description' => t('A human-readable label.'),
          '#type' => 'textfield',
          '#element_validate' => [
            'islandora_add_datastream_form_field_does_not_contain_a_forward_slash'
            ],
        ],
        'file' => [
          '#type' => 'managed_file',
          '#required' => TRUE,
          '#title' => t('Upload Document'),
          '#size' => 48,
          '#description' => t('Select a file to upload.<br/>Files must be less than <strong>@size MB.</strong>', [
            '@size' => $upload_size
            ]),
          '#default_value' => !$form_state->getValue(['files']) ? $form_state->getValue([
            'files'
            ]) : NULL,
          '#upload_location' => file_default_scheme() . '://',
          '#upload_validators' => [
            // Disable default file_validate_extensions; we need direct control.
            'file_validate_extensions' => [
              NULL
              ],
            // Assume its specified in MB.
          'file_validate_size' => [
              $upload_size * 1024 * 1024
              ],
          ],
        ],
        'submit' => [
          '#type' => 'submit',
          '#value' => t('Add Datastream'),
        ],
      ],
    ];
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/mimetype.utils');
    $extensions = islandora_get_extensions_for_datastream($form_state->get([
      'object'
      ]), $form_state->getValue(['dsid']));
    $file = file_load($form_state->getValue(['file']));
    // Only validate extensions if mimes defined in ds-composite.
    if ($file && $extensions) {
      $errors = file_validate_extensions($file, implode(' ', $extensions));
      if (count($errors) > 0) {
        $form_state->setErrorByName('file', t("!error (for the set DSID)", [
          '!error' => $errors[0]
          ]));
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get(['object_id']));
    $form_state->set(['redirect'], "islandora/object/{$object->id}");
    $file = file_load($form_state->getValue(['file']));
    try {
      $ds = $object->constructDatastream($form_state->getValue(['dsid']), 'M');
      $ds->label = $form_state->getValue(['label']);
      $ds->mimetype = $file->filemime;
      $path = \Drupal::service("file_system")->realpath($file->uri);
      $ds->setContentFromFile($path);
      $object->ingestDatastream($ds);
      file_delete($file);
    }
    
      catch (exception $e) {
      drupal_set_message(t('@message', [
        '@message' => \Drupal\Component\Utility\Html::escape($e->getMessage())
        ]), 'error');
      // Make sure to delete anyways.
      file_delete($file);
      return;
    }
    drupal_set_message(t("Successfully Added Datastream!"));
  }

}
?>
