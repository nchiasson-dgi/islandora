<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraRegenerateObjectDerivativesForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraRegenerateObjectDerivativesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_regenerate_object_derivatives_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->set(['object'], $object);
    return confirm_form($form, t('Are you sure you want to regenerate all the derivatives for %title?', [
      '%title' => $object->label
      ]), "islandora/object/{$object->id}/manage/properties", t('This will create a new version for every datastream on the object. Please wait while this happens.'), t('Regenerate'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $object = $form_state->get(['object']);
    $batch = islandora_regenerate_object_derivatives_batch($object);
    batch_set($batch);
    $form_state->set(['redirect'], "islandora/object/{$object->id}/manage/properties");
  }

}
?>
