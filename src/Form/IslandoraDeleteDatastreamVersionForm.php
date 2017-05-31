<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraDeleteDatastreamVersionForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraDeleteDatastreamVersionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_delete_datastream_version_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractDatastream $datastream = NULL, $version = NULL) {
    if (!isset($datastream[$version]) || count($datastream) < 2) {
      return drupal_not_found();
    }

    $form_state->set(['datastream'], $datastream);
    $form_state->set(['version'], $version);
    return confirm_form($form, t('Are you sure you want to delete version @version of the @dsid datastream?', [
      '@dsid' => $datastream->id,
      '@version' => $version,
    ]), "islandora/object/{$datastream->parent->id}", t('This action cannot be undone.'), t('Delete'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $datastream = $form_state->get(['datastream']);
    $version = $form_state->get(['version']);

    $datastream_id = $datastream->id;
    $object = $datastream->parent;

    try {
      unset($datastream[$version]);
    }
    
      catch (Exception $e) {
      drupal_set_message(t('Error deleting version %v of %s datastream from object %o %e', [
        '%v' => $version,
        '%s' => $datastream_id,
        '%o' => $object->label,
        '%e' => $e->getMessage(),
      ]), 'error');
    }

    drupal_set_message(t('%d datastream version successfully deleted from Islandora object %o', [
      '%d' => $datastream_id,
      '%o' => $object->label,
    ]));

    $form_state->set(['redirect'], "islandora/object/{$object->id}/datastream/{$datastream->id}/version");
  }

}
?>
