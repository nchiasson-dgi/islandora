<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use AbstractDatastream;

/**
 * Datastream version reversion form.
 *
 * @package \Drupal\islandora\Form
 */
class RevertDatastreamVersionForm extends ConfirmFormBase {
  /**
   * The datastream on which is being operated.
   *
   * @var \AbstractDatastream
   */
  protected $datastream;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_revert_datastream_version_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert the selected version of this datastream?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
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
    if (!isset($datastream[$version]) || count($datastream) < 2) {
      return drupal_not_found();
    }
    $this->datastream = $datastream;

    $form_state->set(['dsid'], $datastream->id);
    $form_state->set(['object_id'], $datastream->parent->id);
    $form_state->set(['version'], $version);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $islandora_object = islandora_object_load($form_state->get('object_id'));

    $datastream_to_revert = $islandora_object[$form_state->get('dsid')];
    $version = $form_state->get(['version']);

    // Create file holding specified datastream version, and set datastream to
    // it.
    $datastream_to_revert_to = $datastream_to_revert[$version];
    if (in_array($datastream_to_revert->controlGroup, ['R', 'E'])) {
      $datastream_to_revert->url = $datastream_to_revert_to->url;
    }
    else {
      // TODO: Use managed file.
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

    drupal_set_message($this->t('%d datastream successfully reverted to version %v for Islandora object %o', [
      '%d' => $datastream_to_revert->id,
      '%v' => $version,
      '%o' => $islandora_object->label,
    ]));

    $form_state->setRedirect('islandora.datastream_version_table', ['object' => $islandora_object->id, 'datastream' => $datastream_to_revert->id]);
  }

}
