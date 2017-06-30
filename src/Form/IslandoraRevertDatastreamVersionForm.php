<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraRevertDatastreamVersionForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;


class IslandoraRevertDatastreamVersionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_revert_datastream_version_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractDatastream $datastream = NULL, $version = NULL) {
    if (!isset($datastream[$version]) || count($datastream) < 2) {
      return drupal_not_found();
    }

    $form_state->set(['dsid'], $datastream->id);
    $form_state->set(['object_id'], $datastream->parent->id);
    $form_state->set(['version'], $version);

    return confirm_form($form, t('Are you sure you want to revert to version @version of the @dsid datastream?', [
      '@dsid' => $datastream->id,
      '@version' => $version,
    ]), "islandora/object/{$datastream->parent->id}", "", t('Revert'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $islandora_object = islandora_object_load($form_state->get(['object_id']));

    $datastream_to_revert = $islandora_object[$form_state['dsid']];
    $version = $form_state->get(['version']);

    // Create file holding specified datastream version, and set datastream to it.
    $datastream_to_revert_to = $datastream_to_revert[$version];
    if (in_array($datastream_to_revert->controlGroup, ['R', 'E'])) {
      $datastream_to_revert->url = $datastream_to_revert_to->url;
    }
    else {
      $filename = file_create_filename('datastream_temp_file', 'temporary://');
      $datastream_to_revert_to->getContent($filename);
      $datastream_to_revert->setContentFromFile($filename);
      file_unmanaged_delete($filename);
    }

    if ($datastream_to_revert->mimeType != $datastream_to_revert_to->mimeType) {
      $datastream_to_revert->mimeType = $datastream_to_revert_to->mimeType;
    }
    if ($datastream_to_revert->label != $datastream_to_revert_to->label) {
      $datastream_to_revert->label = $datastream_to_revert_to->label;
    }

    drupal_set_message(t('%d datastream successfully reverted to version %v for Islandora object %o', [
      '%d' => $datastream_to_revert->id,
      '%v' => $version,
      '%o' => $islandora_object->label,
    ]));

    $form_state->set(['redirect'], "islandora/object/{$islandora_object->id}/datastream/{$datastream_to_revert->id}/version");
  }

}
?>
