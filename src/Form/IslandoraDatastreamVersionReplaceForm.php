<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraDatastreamVersionReplaceForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraDatastreamVersionReplaceForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_datastream_version_replace_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    module_load_include('inc', 'islandora', 'includes/content_model');
    module_load_include('inc', 'islandora', 'includes/utilities');
    module_load_include('inc', 'islandora', 'includes/mimetype.utils');

    $object = islandora_object_load($datastream->parent->id);
    $form_state->set(['object_id'], $object->id);
    $form_state->set(['dsid'], $datastream->id);
    $form_state->set(['object'], $object);

    $extensions = islandora_get_extensions_for_datastream($object, $datastream->id);
    $valid_extensions = implode(' ', $extensions);
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    return [
      'dsid_fieldset' => [
        '#type' => 'fieldset',
        '#title' => t("Update Datastream"),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'dsid' => [
          '#type' => 'markup',
          '#markup' => t("<div>DSID: <strong>@dsid</strong></div>", [
            '@dsid' => $datastream->id
            ]),
        ],
        'label' => [
          '#type' => 'markup',
          '#markup' => t("<div>Label: <strong>@label</strong></div>", [
            '@label' => $datastream->label
            ]),
        ],
        'file' => [
          '#type' => 'managed_file',
          '#required' => TRUE,
          '#title' => t('Upload Document'),
          '#size' => 64,
          '#description' => t('Select a file to upload.<br/>Files must be less than <strong>@size MB.</strong>', [
            '@size' => $upload_size
            ]),
          '#upload_location' => file_default_scheme() . '://',
          '#upload_validators' => [
            'file_validate_extensions' => [
              $valid_extensions
              ],
            // Assume its specified in MB.
          'file_validate_size' => [
              $upload_size * 1024 * 1024
              ],
          ],
        ],
        'submit' => [
          '#type' => 'submit',
          '#value' => t('Add Contents'),
        ],
      ]
      ];
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get(['object_id']));
    $form_state->set(['redirect'], "islandora/object/{$object->id}");
    $file = file_load($form_state->getValue(['file']));
    try {
      $ds = $object[$form_state['dsid']];
      if ($ds->mimetype != $file->filemime) {
        $ds->mimetype = $file->filemime;
      }
      $path = \Drupal::service("file_system")->realpath($file->uri);
      $ds->setContentFromFile($path);
      file_delete($file);
    }
    
      catch (exception $e) {
      drupal_set_message(t('An error occurred during datastream updates. See watchlog for more information.'), 'error');
      \Drupal::logger('islandora')->error('Failed to add new versionable datastream.<br/>code: @code<br/>message: @msg', [
        '@code' => $e->getCode(),
        '@msg' => $e->getMessage(),
      ]);
      file_delete($file);
      return;
    }
    drupal_set_message(t("Successfully Updated Datastream"));
  }

}
?>
