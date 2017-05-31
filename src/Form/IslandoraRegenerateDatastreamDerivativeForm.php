<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraRegenerateDatastreamDerivativeForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraRegenerateDatastreamDerivativeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_regenerate_datastream_derivative_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    $form_state->set(['datastream'], $datastream);
    return confirm_form($form, t('Are you sure you want to regenerate the derivative for the %dsid datastream?', [
      '%dsid' => $datastream->id
      ]), "islandora/object/{$datastream->parent->id}/manage/datastreams", t('This will create a new version of the datastream. Please wait while this happens.'), t('Regenerate'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/derivatives');
    $datastream = $form_state->get(['datastream']);
    $batch = islandora_regenerate_datastream_derivative_batch($datastream);
    batch_set($batch);
    $form_state->set(['redirect'], "islandora/object/{$datastream->parent->id}/manage/datastreams");
  }

}
?>
