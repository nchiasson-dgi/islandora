<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Object ingest form.
 *
 * @package \Drupal\islandora\Form
 */
class IslandoraIngestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_ingest_form';
  }

  /**
   * Ingest form build function.
   *
   * Initializes the form state, and builds the initial list of steps, excutes
   * the current step.
   *
   * @param array $form
   *   The Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param array $configuration
   *   An associative array of configuration values that are used to build the
   *   list of steps to be executed, including:
   *   - id: The PID with which the object should be created.
   *   - namespace: The PID namespace in which the object should be created.
   *     (id is used first, if it is given).
   *   - label: The initial label for the object. Defaults to "New Object".
   *   - collections: An array of collection PIDs, to which the new object should
   *     be related.
   *   - models: An array of content model PIDs, to which the new object might
   *     subscribe
   *   - parent: The parent of the child to be ingested. This is needed for XACML
   *     to correctly apply the parent's POLICY to children.
   *
   * @return array
   *   The form definition of the current step.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $configuration = NULL) {
    module_load_include('inc', 'islandora', 'includes/ingest.form');
    try {
      islandora_ingest_form_init_form_state_storage($form_state, $configuration);
      return islandora_ingest_form_execute_step($form, $form_state);
    }
    catch (Exception $e) {
      \Drupal::logger('islandora')->error('Exception during ingest form processing with Message: "@exception",  and Trace: @trace', array('@exception' => $e->getMessage(), '@trace' => $e->getTraceAsString()));
      drupal_set_message($e->getMessage(), 'error');
      return array(array(
          '#markup' => \Drupal::l(t('Back'), \Drupal\Core\Url::fromUri('javascript:window.history.back();'))));
    }
  }

  /**
   * The submit handler for the ingest form.
   *
   * Attempts to ingest every object built by the previous steps.
   *
   * @param array $form
   *   The Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/ingest.form');
    // Execute any remaining callbacks.
    islandora_ingest_form_increment_step($form_state);
    $step = islandora_ingest_form_get_step($form_state);
    if (isset($step) && $step['type'] == 'callback') {
      islandora_ingest_form_execute_consecutive_callback_steps($form, $form_state, $step);
    }
    // Ingest the objects.
    $set_redirect = $form_state->getRedirect() ? FALSE : TRUE;
    foreach ($form_state->get(['islandora', 'objects']) as &$object) {
      try {
        islandora_add_object($object);
        // We want to redirect to the first object as it's considered to be the
        // primary object.
        if ($set_redirect) {
          $form_state->setRedirect("islandora/object/{$object->id}");
          $set_redirect = FALSE;
        }
        drupal_set_message(
        $this->t('"@label" (ID: @pid) has been ingested.', array('@label' => $object->label, '@pid' => $object->id)),
        'status');
      }
      catch (Exception $e) {
        // If post hooks throws it may already exist at this point but may be
        // invalid, so don't say failed.
        \Drupal::logger('islandora')->error('Exception during ingest with Message: "@exception",  and Trace: @trace', array('@exception' => $e->getMessage(), '@trace' => $e->getTraceAsString()));
        drupal_set_message(
          $this->t('A problem occured while ingesting "@label" (ID: @pid), please notify the administrator.',
          array('@label' => $object->label, '@pid' => $object->id)),
          'error'
        );
      }
    }
    // XXX: Foreaching with references can be weird... The reference exists in
    // the scope outside.
    unset($object);
  }

}
