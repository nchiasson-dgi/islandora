<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use AbstractObject;

/**
 * Object deletion confirmation form.
 *
 * @package \Drupal\islandora\Form
 */
class IslandoraDeleteObjectForm extends ConfirmFormBase {
  /**
   * The object on which is being operated.
   *
   * @var \AbstractObject
   */
  protected $object;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_delete_object_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this object?');
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
    return Url::fromRoute('islandora.view_object', ['object' => $this->object->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $this->object = $object;
    $form_state->set(['object'], $object);
    return parent::buildForm($form, $form_state);;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/datastream');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $object = $form_state->get(['object']);
    $parents = islandora_get_parents_from_rels_ext($object);
    $parent = array_pop($parents);
    if (isset($parent)) {
      $form_state->setRedirect('islandora.view_object', ['object' => $parent->id]);
    }
    else {
      $form_state->setRedirect('islandora.view_default_object');
    }
    islandora_delete_object($object);
  }

}
