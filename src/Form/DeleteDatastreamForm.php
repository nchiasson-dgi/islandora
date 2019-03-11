<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use AbstractDatastream;
use AbstractObject;

/**
 * Datastream deletion form.
 *
 * @package \Drupal\islandora\Form
 */
class DeleteDatastreamForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_delete_datastream_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the indicated datastreams?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('islandora.view_object', ['object' => $this->datastream->parent->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    $this->datastream = $datastream;

    $form_state->set(['datastream_info'], [
      'object_id' => $datastream->parent->id,
      'datastream_id' => $datastream->id,
    ]);
    $object = $datastream->parent;
    $dsid = $datastream->id;
    $dsids = array_merge([$dsid], $this->associatedDatastreams($object, $dsid));
    $dsids = array_unique($dsids);
    $form['delete_derivatives'] = [
      '#title' => $this->t('Delete Derivatives'),
      '#type' => 'checkbox',
      '#default_value' => 0,
      '#description' => $this->t('Derivatives can be regenerated at a later time.'),
    ];
    $form['base_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Datastream to be purged'),
      '#markup' => $dsid,
      '#states' => [
        'invisible' => [
          ':input[name="delete_derivatives"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['derivative_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Datastream(s) to be purged'),
      '#description' => $this->t('Including detectable derivatives.'),
      '#markup' => implode(', ', $dsids),
      '#states' => [
        'visible' => [
          ':input[name="delete_derivatives"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get(['datastream_info', 'object_id']));
    $datastream_id = $form_state->get(['datastream_info', 'datastream_id']);
    $datastream = $object[$datastream_id];
    $deleted = FALSE;
    if ($form_state->getValue(['delete_derivatives'])) {
      $this->purgeAssociated($object, $datastream_id);;
    }
    try {
      $deleted = islandora_delete_datastream($datastream);
    }
    catch (Exception $e) {
      drupal_set_message($this->t('Error deleting %s datastream from object %o %e', [
        '%s' => $datastream_id,
        '%o' => $object->label,
        '%e' => $e->getMessage(),
      ]), 'error');
    }
    if ($deleted) {
      drupal_set_message($this->t('%d datastream sucessfully deleted from Islandora object %o', [
        '%d' => $datastream_id,
        '%o' => $object->label,
      ]));
    }
    else {
      drupal_set_message($this->t('Error deleting %s datastream from object %o', [
        '%s' => $datastream_id,
        '%o' => $object->label,
      ]), 'error');
    }
    $form_state->setRedirect('islandora.view_object', ['object' => $object->id]);
  }

  /**
   * Helper; identify the associated derivative datastreams.
   *
   * @param \AbstractObject $object
   *   The object on which to look for derivatives.
   * @param string $dsid
   *   The datastream of which the we want to identify derivatives.
   *
   * @return array
   *   The array of datastream identifiers identified.
   */
  protected function associatedDatastreams(AbstractObject $object, $dsid) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $hooks = islandora_invoke_hook_list(ISLANDORA_DERIVATIVE_CREATION_HOOK, $object->models, [$object]);
    $hook_filter = function ($hook_def) use ($dsid) {
      return isset($hook_def['source_dsid']) && isset($hook_def['destination_dsid']) ?
        ($hook_def['source_dsid'] == $dsid && $hook_def['destination_dsid'] != $dsid) :
        FALSE;
    };
    $hooks = array_filter($hooks, $hook_filter);
    $dsid_map = function ($hook_definition) {
      return $hook_definition['destination_dsid'];
    };
    $dsids = [];
    $derived_dsids = array_map($dsid_map, $hooks);
    while ($current = array_pop($derived_dsids)) {
      $dsids[] = $current;
      $current_derived = $this->associatedDatastreams($object, $current);
      $current_diff = array_diff($current_derived, $derived_dsids, $dsids);
      $derived_dsids = array_merge($derived_dsids, $current_diff);
    }
    return $dsids;
  }

  /**
   * Helper; purge the associated derivative datastreams.
   *
   * @param \AbstractObject $object
   *   The object from which to purge associated datastreams.
   * @param string $dsid
   *   The datastream of which we will purge the associated datastreams.
   *
   * @see ::associatedDatastreams()
   */
  protected function purgeAssociated(AbstractObject $object, $dsid) {
    $dsids = $this->associatedDatastreams($object, $dsid);
    array_map([$object, 'purgeDatastream'], $dsids);
  }

}
