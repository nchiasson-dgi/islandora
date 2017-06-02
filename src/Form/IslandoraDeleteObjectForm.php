<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraDeleteObjectForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraDeleteObjectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_delete_object_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->set(['object'], $object);
    return confirm_form($form, t('Are you sure you want to delete %title?', [
      '%title' => $object->label
      ]), "islandora/object/$object->id", t('This action cannot be undone.'), t('Delete'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/datastream');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $object = $form_state->get(['object']);
    $parents = islandora_get_parents_from_rels_ext($object);
    $parent = array_pop($parents);
    $form_state->set(['redirect'], isset($parent) ? "islandora/object/{$parent->id}" : 'islandora');
    islandora_delete_object($object);
  }

}
?>
