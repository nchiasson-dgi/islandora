<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\islandora\Form\Abstracts\DatastreamVersionConfirmFormBase;

use AbstractDatastream;

/**
 * Datastream version deletion form.
 *
 * @package \Drupal\islandora\Form
 */
class DeleteDatastreamVersionForm extends DatastreamVersionConfirmFormBase {
  protected $datastream;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_delete_datastream_version_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // XXX: Original was more specific... Should we be more-so?
    return $this->t('Are you sure you want to delete the selected datastream version?');
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
  public function buildForm(array $form, FormStateInterface $form_state, AbstractDatastream $datastream = NULL, $version = NULL) {
    if (!isset($datastream[$version])) {
      throw new Exception($this->t('This indicated version could not be found.'));
    }
    elseif (count($datastream) < 2) {
      throw new Exception($this->t('There must be at least two versions in order to delete one.'));
    }

    $this->datastream = $datastream;

    $form_state->set('datastream', $datastream);
    $form_state->set('version', $version);

    return parent::buildForm($form, $form_state, $datastream, $version);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $datastream = $form_state->get(['datastream']);
    $version = $form_state->get(['version']);

    $datastream_id = $datastream->id;
    $object = $datastream->parent;

    try {
      unset($datastream[$version]);
    }
    catch (Exception $e) {
      drupal_set_message($this->t('Error deleting version %v of %s datastream from object %o %e', [
        '%v' => $version,
        '%s' => $datastream_id,
        '%o' => $object->label,
        '%e' => $e->getMessage(),
      ]), 'error');
    }

    drupal_set_message($this->t('%d datastream version successfully deleted from Islandora object %o', [
      '%d' => $datastream_id,
      '%o' => $object->label,
    ]));

    $form_state->setRedirect('islandora.datastream_version_table', ['object' => $object->id, 'datastream' => $datastream->id]);
  }

}
