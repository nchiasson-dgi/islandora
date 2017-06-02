<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraDeleteDatastreamForm.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraDeleteDatastreamForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_delete_datastream_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    // XXX: Stashed version of datastream is deprecated... Use object and
  // datastream IDs from 'datastream_info' to acquire.
    $form_state->set([
      'datastream'
      ], $datastream);
    $form_state->set(['datastream_info'], [
      'object_id' => $datastream->parent->id,
      'datastream_id' => $datastream->id,
    ]);
    $object = $datastream->parent;
    $dsid = $datastream->id;
    $dsids = array_merge([$dsid], islandora_datastream_to_purge($object, $dsid));
    $dsids = array_unique($dsids);
    $form['delete_derivatives'] = [
      '#title' => t('Delete Derivatives'),
      '#type' => 'checkbox',
      '#default_value' => 0,
      '#description' => t('Derivatives can be regenerated at a later time.'),
    ];
    $form['base_info'] = [
      '#type' => 'item',
      '#title' => t('Datastream to be purged'),
      '#markup' => $dsid,
      '#states' => [
        'invisible' => [
          ':input[name="delete_derivatives"]' => [
            'checked' => TRUE
            ]
          ]
        ],
    ];
    $form['derivative_info'] = [
      '#type' => 'item',
      '#title' => t('Datastream(s) to be purged'),
      '#description' => t('Including detectable derivatives.'),
      '#markup' => implode(', ', $dsids),
      '#states' => [
        'visible' => [
          ':input[name="delete_derivatives"]' => [
            'checked' => TRUE
            ]
          ]
        ],
    ];
    return confirm_form($form, t('Are you sure you want to delete the %dsid datastream?', [
      '%dsid' => $datastream->id
      ]), "islandora/object/{$datastream->parent->id}", t('This action cannot be undone.'), t('Delete'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get(['datastream_info', 'object_id']));
    $datastream_id = $form_state->get(['datastream_info', 'datastream_id']);
    $datastream = $object[$datastream_id];
    $deleted = FALSE;
    if ($form_state->getValue(['delete_derivatives'])) {
      islandora_datastream_derivatives_purged($object, $datastream_id);
    }
    try {
      $deleted = islandora_delete_datastream($datastream);
    }
    
      catch (Exception $e) {
      drupal_set_message(t('Error deleting %s datastream from object %o %e', [
        '%s' => $datastream_id,
        '%o' => $object->label,
        '%e' => $e->getMessage(),
      ]), 'error');
    }
    if ($deleted) {
      drupal_set_message(t('%d datastream sucessfully deleted from Islandora object %o', [
        '%d' => $datastream_id,
        '%o' => $object->label,
      ]));
    }
    else {
      drupal_set_message(t('Error deleting %s datastream from object %o', [
        '%s' => $datastream_id,
        '%o' => $object->label,
      ]), 'error');
    }
    $form_state->set(['redirect'], "islandora/object/{$object->id}");
  }

}
?>
