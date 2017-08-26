<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use AbstractObject;

/**
 * Derivative regeneration form.
 *
 * @package \Drupal\islandora\Form
 */
class IslandoraRegenerateObjectDerivativesForm extends ConfirmFormBase {

  /**
   * The object on which we are operating.
   *
   * @var \AbstractObject
   */
  protected $object;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_regenerate_object_derivatives_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to regenerate all derivatives on this object?');
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
    return Url::fromRoute('islandora.object_properties_form', ['object' => $this->object->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $this->object = $object;
    $form_state->set(['object'], $object->id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get(['object']));
    module_load_include('inc', 'islandora', 'includes/regenerate_derivatives.form');
    $batch = islandora_regenerate_object_derivatives_batch($object);
    batch_set($batch);
    $form_state->setRedirect('islandora.object_properties_form', ['object' => $object->id]);
  }

}
