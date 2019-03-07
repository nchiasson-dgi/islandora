<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use AbstractDatastream;
use Drupal\islandora\Form\Abstracts\DatastreamConfirmFormBase;

/**
 * Datastream derivative regeneration form.
 *
 * @package \Drupal\islandora\Form
 */
class RegenerateDatastreamDerivativeForm extends DatastreamConfirmFormBase {

  /**
   * The object in which we are operating.
   *
   * @var \AbstractObject
   */
  protected $object;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_regenerate_datastream_derivative_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to regenerate the given datastream?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will create a new version of the datastream. Please wait while this happens.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Regenerate');
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
    return Url::fromRoute('islandora.edit_object', ['object' => $this->object->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    $this->object = $datastream->parent;
    $form_state->set(['datastream_info'], [
      'object_id' => $datastream->parent->id,
      'dsid' => $datastream->id,
    ]);
    return parent::buildForm($form, $form_state, $datastream);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora', 'inc', 'includes/regenerate_derivatives.form');
    $object = islandora_object_load($form_state->get(['datastream_info', 'object_id']));
    $datastream = $object[$form_state->get(['datastream_info', 'dsid'])];
    $batch = islandora_regenerate_datastream_derivative_batch($datastream);
    $form_state->setRedirect('islandora.edit_object', ['object' => $datastream->parent->id]);
    batch_set($batch);
  }

}
