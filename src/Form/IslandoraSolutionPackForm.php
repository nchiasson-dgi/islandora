<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Render\Renderer;

use Drupal\filter\Render\FilteredMarkup;

use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Class IslandoraSolutionPackForm.
 *
 * @package Drupal\islandora\Form
 */
class IslandoraSolutionPackForm extends FormBase {

  protected $moduleHandler;
  protected $renderer;

  /**
   * Constructor.
   */
  public function __construct(ModuleHandler $moduleHandler, Renderer $renderer) {
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
  }

  /**
   * Dependency Injection!
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Injected container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solution_pack_form_islandora';
  }

  /**
   * Get the batch definition to reinstall all the objects for a given module.
   *
   * @param string $module
   *   The name of the modules of which to grab the required objects to setup
   *   the batch.
   * @param array $not_checked
   *   The object that will bot be install.
   *
   * @return array
   *   An array defining a batch which can be passed on to batch_set().
   */
  public function islandoraSolutionPackGetBatch($module, array $not_checked = []) {
    $batch = [
      'title' => $this->t('Installing / Updating solution pack objects'),
      'file' => drupal_get_path('module', 'islandora') . '/src/Form/solution_packs.inc',
      'operations' => [],
    ];

    $info = islandora_solution_packs_get_required_objects($module);
    foreach ($info['objects'] as $key => $object) {
      foreach ($not_checked as $not) {
        if ($object->id == $not) {
          unset($info['objects'][$key]);
        }
      }
    }

    foreach ($info['objects'] as $key => $object) {
      $batch['operations'][] = [
        [
          __CLASS__,
          'islandoraSolutionPackBatchOperationReingestObject',
        ],
        [$object],
      ];
    }
    return $batch;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $not_checked = [];
    $object_info = json_decode($form_state->getValue('tablevalue'));
    $table = $form_state->getValue('table');
    if (isset($table)) {
      foreach ($table as $key => $value) {
        if ($value === 0) {
          $not_checked[] = $object_info->$key->pid;
        }
      }
    }
    $solution_pack_module = $form_state->getValue('solution_pack_module');
    // Use not_checked instead of checked. Remove not checked item from batch.
    // So that get batch function can get all object ingest batch if not checked
    // list is empty.
    $batch =
      $this->islandoraSolutionPackGetBatch($solution_pack_module, $not_checked);
    batch_set($batch);
    // Hook to let solution pack objects be modified. Not using module_invoke
    // so solution packs can be expanded by other modules.
    // @todo shouldn't we send the object list along as well?
    $this->moduleHandler->invokeAll('islandora_postprocess_solution_pack',
      [$solution_pack_module]);

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $solution_pack_module = NULL, $solution_pack_name = NULL, $objects = []) {
    // The order is important in terms of severity of the status, where higher
    // index indicates the status is more serious, it will be used to determine
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
    $status_info = [
      'up_to_date' => [
        'solution_pack' => $this->t('All required objects are installed and up-to-date.'),
        'image' => $ok_image,
        'button' => $this->t("Force reinstall objects"),
      ],
      'modified_datastream' => [
        'solution_pack' => $this->t('Some objects must be reinstalled. See objects list for details.'),
        'image' => $warning_image,
        'button' => $this->t("Reinstall objects"),
      ],
      'out_of_date' => [
        'solution_pack' => $this->t('Some objects must be reinstalled. See objects list for details.'),
        'image' => $warning_image,
        'button' => $this->t("Reinstall objects"),
      ],
      'missing_datastream' => [
        'solution_pack' => $this->t('Some objects must be reinstalled. See objects list for details.'),
        'image' => $warning_image,
        'button' => $this->t("Reinstall objects"),
      ],
      'missing' => [
        'solution_pack' => $this->t('Some objects are missing and must be installed. See objects list for details.'),
        'image' => $warning_image,
        'button' => $this->t("Install objects"),
      ],
    ];
    $status_severities = array_keys($status_info);
    $solution_pack_status_severity = array_search('up_to_date', $status_severities);

    // Prepare for tableselect.
    $header = [
      'label' => $this->t('Label'),
      'pid' => $this->t('PID'),
      'status' => $this->t('Status'),
    ];

    $object_info = [];
    foreach ($objects as $object) {
      $object_status = islandora_check_object_status($object);
      $object_status_info = $status_info[$object_status['status']];
      $object_status_severity = array_search($object_status['status'], $status_severities);
      // The solution pack status severity will be the highest severity of
      // the objects.
      $solution_pack_status_severity = max($solution_pack_status_severity, $object_status_severity);
      $exists = $object_status['status'] != 'missing';
      $label = $exists ? Link::fromTextAndUrl($object->label, Url::fromRoute('islandora.view_object', ['object' => $object->id])) : $object->label;

      // XXX: Probably want to apply css pseudo-selector
      // to this TD directly. Drupal 8 uses glyphs instead of images for things
      // like 'warning' and 'ok'.
      $object_info[$object->id] = [
        'label' => $label,
        'pid' => $object->id,
        'status' => [
          '#markup' => $this->t('@image @status', [
            '@image' => $this->renderer->renderRoot($object_status_info['image']),
            '@status' => $object_status['status_friendly'],
          ]),
        ],
      ];

    }

    $solution_pack_status = $status_severities[$solution_pack_status_severity];
    $solution_pack_status_info = $status_info[$solution_pack_status];

    $form = [
      'solution_pack' => [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => ['class' => ['islandora-solution-pack-fieldset']],
        'solution_pack_module' => [
          '#type' => 'value',
          '#value' => $solution_pack_module,
        ],
        'solution_pack_name' => [
          '#type' => 'value',
          '#value' => $solution_pack_name,
        ],
        'objects' => [
          '#type' => 'value',
          '#value' => $objects,
        ],
        'solution_pack_label' => [
          '#markup' => $solution_pack_name,
          '#prefix' => '<h3>',
          '#suffix' => '</h3>',
        ],
        'install_status' => [
          '#markup' => '<strong>Object status:</strong> @image @status',
          '#attached' => [
            'placeholders' => [
              '@image' => [
                'image' => $solution_pack_status_info['image'],
              ],
              '@status' => [
                '#markup' => $solution_pack_status_info['solution_pack'],
              ],
            ],
          ],
          '#prefix' => '<div class="islandora-solution-pack-install-status">',
          '#suffix' => '</div>',
        ],
        'table' => [
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $object_info,
        ],
        'tablevalue' => [
          '#type' => 'hidden',
          '#value' => json_encode($object_info),
        ],
        'submit' => [
          '#type' => 'submit',
          '#name' => $solution_pack_module,
          '#value' => $solution_pack_status_info['button'],
          '#attributes' => ['class' => ['islandora-solution-pack-submit']],
        ],
      ],
    ];
    $form['#attached']['library'][] = 'islandora/islandora-admin';
    return $form;
  }

  /**
   * Batch operation to ingest/reingest required object(s).
   *
   * @param \AbstractObject $object
   *   The object to ingest/reingest.
   * @param array $context
   *   The context of this batch operation.
   */
  public static function islandoraSolutionPackBatchOperationReingestObject(AbstractObject $object, array &$context) {
    $existing_object = islandora_object_load($object->id);
    $deleted = FALSE;
    if ($existing_object) {
      $deleted = islandora_delete_object($existing_object);
      if (!$deleted) {
        drupal_set_message(t('Failed to purge existing object @object_link.', [
          '@object_link' => FilteredMarkup::create(Link::fromTextAndUrl(Xss::filter($object->label), Url::fromRoute('islandora.view_object', ['object' => $object->id]))->toString()),
        ]), 'error');
        // Failed to purge don't attempt to ingest.
        return;
      }
    }

    // Object was deleted or did not exist.
    $pid = $object->id;
    $label = $object->label;

    $object = islandora_add_object($object);
    $params = [
      '@pid' => $pid,
      '@label' => $label,
      '@object_link' => FilteredMarkup::create(Link::fromTextAndUrl(Xss::filter($label), Url::fromRoute('islandora.view_object', ['object' => $pid]))->toString()),
    ];
    dsm($params);

    if ($object) {
      if ($deleted) {
        $message = t('Successfully reinstalled @object_link.', $params);
      }
      else {
        $message = t('Successfully installed @object_link.', $params);
      }
    }
    elseif ($deleted) {
      $message = t('Failed to reinstall @label, identified by @pid.', $params);
    }
    else {
      $message = t('Failed to install @label, identified by @pid.', $params);
    }
    dsm($message);

    drupal_set_message($message,
      $object ? 'status' : 'error'
    );
  }

}
