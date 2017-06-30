<?php

/**
 * @file
 * Admin and callback functions for solution pack management.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraSolutionPackFormIslandora extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solution_pack_form_islandora';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $not_checked = array();
    $object_info = json_decode($form_state->getValue('tablevalue'));
    $table = $form_state->getValue('table');
    if (isset($table)) {
      foreach ($table as $key => $value) {
        if ($value === 0) {
          $not_checked[] = $object_info[$key]->pid;
        }
      }
    }
    $solution_pack_module = $form_state->getValue('solution_pack_module');

    // Use not_checked instead of checked. Remove not checked item from betch. so
    // that get batch function can get all object ingest batch if not checked list
    // is empty.
    $batch = islandora_solution_pack_get_batch($solution_pack_module, $not_checked);
    batch_set($batch);
    // Hook to let solution pack objects be modified.
    // Not using module_invoke so solution packs can be expanded by other modules.
    // @todo shouldn't we send the object list along as well?
    \Drupal::moduleHandler()->invokeAll('islandora_postprocess_solution_pack', [$solution_pack_module]);

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $solution_pack_module = NULL, $solution_pack_name = NULL, $objects = array()) {
    // The order is important in terms of severity of the status, where higher
    // index indicates the status is more serious, this will be used to determine
    // what messages get displayed to the user.
    $ok_image = [
      '#type' => 'image',
      '#theme' => 'image',
      '#uri' => '/core/misc/icons/73b355/check.svg',
      ];
    $warning_image = [
      '#type' => 'image',
      '#theme' => 'image',
      '#uri' => '/core/misc/icons/e29700/warning.svg',
      ];
    $status_info = array(
      'up_to_date' => array(
        'solution_pack' => t('All required objects are installed and up-to-date.'),
        'image' => $ok_image,
        'button' => t("Force reinstall objects"),
      ),
      'modified_datastream' => array(
        'solution_pack' => t('Some objects must be reinstalled. See objects list for details.'),
        'image' => $warning_image,
        'button' => t("Reinstall objects"),
      ),
      'out_of_date' => array(
        'solution_pack' => t('Some objects must be reinstalled. See objects list for details.'),
        'image' => $warning_image,
        'button' => t("Reinstall objects"),
      ),
      'missing_datastream' => array(
        'solution_pack' => t('Some objects must be reinstalled. See objects list for details.'),
        'image' => $warning_image,
        'button' => t("Reinstall objects"),
      ),
      'missing' => array(
        'solution_pack' => t('Some objects are missing and must be installed. See objects list for details.'),
        'image' => $warning_image,
        'button' => t("Install objects"),
      ),
    );
    $status_severities = array_keys($status_info);
    $solution_pack_status_severity = array_search('up_to_date', $status_severities);

    // Prepare for tableselect.
    $header = array(
      'label' => t('Label'),
      'pid' => t('PID'),
      'status' =>  t('Status'),
      );

    $object_info = array();
    foreach ($objects as $key => $value) {
      $object_status = islandora_check_object_status($value);
      $object_status_info = $status_info[$object_status['status']];
      $object_status_severity = array_search($object_status['status'], $status_severities);
      // The solution pack status severity will be the highest severity of
      // the objects.
      $solution_pack_status_severity = max($solution_pack_status_severity, $object_status_severity);
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $label = $exists ? l($object->label, "islandora/object/{$object->id}") : $object->label;

      // XXX: This is a kludge, probably want to apply css pseudo-selector
      // to this TD directly. Drupal 8 uses glyphs instead of images for things
      // like 'warning' and 'ok'.
      $object_info[] = [
        'label'=> [
          '#markup' => $value->label,
        ],
        'pid' => [
          '#markup' => $value->id,
        ],
        'status' => [
          '#markup' => t('<strong>Object status:</strong> @image @status', [
            '@image' => \Drupal::service("renderer")->renderRoot($object_status_info['image']),
            '@status' => $object_status['status_friendly'],
              ]
          )]
      ];

    }

    $solution_pack_status = $status_severities[$solution_pack_status_severity];
    $solution_pack_status_info = $status_info[$solution_pack_status];

    $form = array(

      'solution_pack' => array(
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => array('class' => array('islandora-solution-pack-fieldset')),
        'solution_pack_module' => array(
          '#type' => 'value',
          '#value' => $solution_pack_module,
        ),
        'solution_pack_name' => array(
          '#type' => 'value',
          '#value' => $solution_pack_name,
        ),
        'objects' => array(
          '#type' => 'value',
          '#value' => $objects,
        ),
        'solution_pack_label' => array(
          '#markup' => $solution_pack_name,
          '#prefix' => '<h3>',
          '#suffix' => '</h3>',
        ),
        'install_status' => array(
          '#markup' => '@image @status',
          '#attached' => ['placeholders' => [
            '@image' => [
              'image' => $solution_pack_status_info['image']
            ],
            '@status' => [
              '#markup' => $solution_pack_status_info['solution_pack']
            ]
          ]],
          '#prefix' => '<div class="islandora-solution-pack-install-status">',
          '#suffix' => '</div>',
        ),
        'table' => array(
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $object_info,
        ),
        'tablevalue' => array(
          '#type' => 'hidden',
          '#value' => json_encode(function () use ($object_info) {
            // XXX: Removed status from the returned $object_info as it
            // now contains HTML tags which are always rendered by the browser?
            if(($key = array_search('status', $object_info, TRUE)) !== FALSE) {
              unset($object_info[$key]);
            }
            return $object_info;
          }),
        ),
        'submit' => array(
          '#type' => 'submit',
          '#name' => $solution_pack_module,
          '#value' => $solution_pack_status_info['button'],
          '#attributes' => array('class' => array('islandora-solution-pack-submit')),
        ),
      ),
    );
    return $form;
  }
}
