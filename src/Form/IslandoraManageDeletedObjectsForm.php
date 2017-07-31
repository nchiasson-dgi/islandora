<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the manage deleted object form.
 */
class IslandoraManageDeletedObjectsForm extends FormBase {

  protected $moduleHandler;

  /**
   * Creates the delete objects form and injects the ModuleHandler for use.
   */
  public function __construct(ModuleHandler $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service(s) required to construct this class.
    // Order should be the same as the order they are listed in the constructor.
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_manage_deleted_objects_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    module_load_include('inc', 'islandora', 'includes/manage_deleted_objects');
    $form += [
      '#tree' => TRUE,
      'actions' => [
        '#type' => 'actions',
      ],
    ];
    $persisted_cmodels = $this->getRequest()->get('models');
    $storage = $form_state->getStorage();
    $triggering_element = $form_state->getTriggeringElement();
    $page = pager_find_page();
    if ((isset($triggering_element['#array_parents']) && $triggering_element['#array_parents'] == ['actions', 'next']) || $persisted_cmodels) {
      $form['propogate'] = [
        '#title' => $this->t('Apply changes to related objects?'),
        '#default_value' => TRUE,
        '#description' => $this->t('Objects associated with selected objects will also be purged/restored. For example, page objects associated with a book object.'),
        '#type' => 'checkbox',
      ];
      $form['objects_to_process'] = [
        '#type' => 'table',
        '#header' => [
          'title' => $this->t('Name'),
          'pid' => $this->t('PID'),
          'content_model' => $this->t('Content Model'),
        ],
        '#tableselect' => TRUE,
      ];
      $filtered_cmodels = $persisted_cmodels ? $persisted_cmodels : array_values($storage['cmodel_selection']);

      $form['pager'] = [
        '#type' => 'pager',
        '#parameters' => ['models' => $filtered_cmodels],
      ];
      $number_of_deleted = $this->getCountOfDeletedObjects($filtered_cmodels);
      $limit = 25;
      $offset = $limit * $page;
      pager_default_initialize($number_of_deleted, $limit);
      // Get objects given the selected content models.
      $objects = $this->getObjectsThatAreDeleted($filtered_cmodels, $offset);
      foreach ($objects as $object_pid => $object_values) {
        $form['objects_to_process'][$object_pid] = [
          'title' => [
            '#plain_text' => $object_values['title'],
          ],
          'pid' => [
            '#plain_text' => $object_values['pid'],
          ],
          'content_model' => [
            '#plain_text' => $object_values['content_model_label'],
          ],
        ];
      }
      $form['actions']['restore'] = [
        '#type' => 'submit',
        '#value' => $this->t('Restore selected objects'),
        '#tableselect' => TRUE,
      ];
      if ($this->currentUser()->hasPermission(ISLANDORA_PURGE)) {
        $form['actions']['purge'] = [
          '#type' => 'submit',
          '#value' => $this->t('Irrevocably purge selected objects'),
          '#tableselect' => TRUE,
        ];
      }

    }
    else {
      $cmodels_of_deleted = $this->getContentModelsOfDeleted();
      $form['cmodel_selection'] = [
        '#type' => 'table',
        '#caption' => $this->t('Select content models of deleted objects to filter on.'),
        '#empty' => $this->t('There are no deleted objects in this repository.'),
        '#header' => [
          'pid' => $this->t('PID'),
          'content_model' => $this->t('Content Model'),
        ],
        '#tableselect' => TRUE,
        '#default_value' => isset($storage['cmodel_selection']) ? $storage['cmodel_selection'] : [],
      ];
      if (!empty($cmodels_of_deleted)) {
        foreach ($cmodels_of_deleted as $pid => $label) {
          $form['cmodel_selection'][$pid] = [
            'pid' => [
              '#plain_text' => $pid,
            ],
            'label' => [
              '#plain_text' => $label,
            ],
          ];
        }
        $form['actions']['next'] = [
          '#type' => 'submit',
          '#value' => $this->t('Next'),
          '#tableselect' => TRUE,
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#array_parents'] == ['actions', 'next']) {
      $persisted_cmodels = $this->getRequest()->get('models');
      if ($triggering_element['#array_parents'] == ['actions', 'next']) {
        $form_state->setStorage([
          'cmodel_selection' => $persisted_cmodels ? $persisted_cmodels : $form_state->getValue('cmodel_selection'),
        ]);
        $form_state->setRebuild();
      }
      elseif ($persisted_cmodels) {
        $form_state->setRedirect('islandora.deleted_objects_manage_form', [
          ['models' => $persisted_cmodels],
        ]);
      }
    }
    elseif ($triggering_element['#array_parents'] == ['actions', 'restore'] || $triggering_element['#array_parents'] == ['actions', 'purge']) {
      $pids_to_restore = $form_state->getValue('objects_to_process');
      $action = end($triggering_element['#array_parents']);
      $action_mapping = [
        'restore' => [
          'title' => $this->t('Restoring'),
          'callback' => [$this, 'restoreObjectByPid'],
        ],
        'purge' => [
          'title' => $this->t('Purging'),
          'callback' => [$this, 'purgeObjectByPid'],
        ],
      ];
      $batch = [
        'title' => $this->t('@descriptor selected objects', ['@descriptor' => $action_mapping[$action]['title']]),
        'file' => drupal_get_path('module', 'islandora') . '/includes/manage_deleted_objects.inc',
        'operations' => [],
      ];
      $propogate = $form_state->getValue('propogate');
      foreach ($pids_to_restore as $pid) {
        $batch['operations'][] = [
          $action_mapping[$action]['callback'],
         [$pid],
        ];
        if ($propogate) {
          $object = islandora_object_load($pid);
          $hooks = islandora_invoke_hook_list(ISLANDORA_UPDATE_RELATED_OBJECTS_PROPERTIES_HOOK, $object->models, [$object]);
          foreach ($hooks as $hooked_pid) {
            $batch['operations'][] = [
              $action_mapping[$action]['callback'],
              [$hooked_pid],
            ];
          }
        }
      }
      batch_set($batch);
    }

  }

  /**
   * Returns content models of deleted objects.
   *
   * @return array
   *   An array keyed by the PID of the content model where the value is the
   *   label of the content model object.
   */
  public function getContentModelsOfDeleted() {
    $tuque = islandora_get_tuque_connection();
    $query = <<<EOQ
SELECT DISTINCT ?cmodel ?label FROM <#ri> WHERE {
  ?object <fedora-model:state> <fedora-model:Deleted> ;
          <fedora-model:hasModel> ?cmodel .
  ?cmodel <fedora-model:label> ?label
FILTER(!sameTerm(?cmodel, <info:fedora/fedora-system:FedoraObject-3.0>))
}
EOQ;
    $results = $tuque->repository->ri->sparqlQuery($query);
    $content_models = [];
    foreach ($results as $result) {
      $content_models[$result['cmodel']['value']] = $result['label']['value'];
    }
    return $content_models;
  }

  /**
   * Helper to return the query for finding deleted objects.
   *
   * @param array $cmodels
   *   Content models to be filtered on.
   *
   * @return string
   *   The SPARQL query to be ran.
   */
  public function getDeletedObjectQuery(array $cmodels) {
    $query = <<<EOQ
SELECT DISTINCT ?object ?object_label ?model ?model_label FROM <#ri> WHERE {
  ?object <fedora-model:hasModel> ?model ;
          <fedora-model:state> <fedora-model:Deleted> ;
          <fedora-model:label> ?object_label .
  ?model  <fedora-model:label> ?model_label .
  FILTER(!filter)
}
EOQ;
    $pid_filters = array_map(function ($pid) {
      return "sameTerm(?model, <info:fedora/$pid>)";
    }, $cmodels);
    $filters = implode(' || ', $pid_filters);
    $sparql_query = strtr($query, [
      '!filter' => $filters,
    ]);
    return $sparql_query;
  }

  /**
   * Helper to return the query for finding deleted objects.
   *
   * @param array $cmodels
   *   Content models to be filtered on.
   *
   * @return int
   *   The number of deleted objects.
   */
  public function getCountOfDeletedObjects(array $cmodels) {
    $tuque = islandora_get_tuque_connection();
    $sparql_query = $this->getDeletedObjectQuery($cmodels);
    return $tuque->repository->ri->countQuery($sparql_query, 'sparql');
  }

  /**
   * Gets deleted objects given content models to filter on.
   *
   * @param array $cmodels
   *   An array of content model PIDs to use as filters within our SPARQL to
   *   get objects.
   * @param int $offset
   *   The offset to begin the slice at.
   *
   * @return array
   *   An array of values to be used in a tableselect.
   */
  public function getObjectsThatAreDeleted(array $cmodels, $offset) {
    $tuque = islandora_get_tuque_connection();
    $sparql_query = $this->getDeletedObjectQuery($cmodels) . <<<EOQ
LIMIT 25
OFFSET $offset
EOQ;
    $results = $tuque->repository->ri->sparqlQuery($sparql_query);
    $deleted_objects = [];
    foreach ($results as $result) {
      $deleted_objects[$result['object']['value']] = [
        'title' => $result['object_label']['value'],
        'pid' => $result['object']['value'],
        'content_model' => $result['model']['value'],
        'content_model_label' => $result['model_label']['value'],
      ];
    }
    return $deleted_objects;

  }

  /**
   * Restores the deleted object.
   *
   * @param string $pid
   *   The pid of the object being purged.
   * @param array $context
   *   Context of the batch.
   */
  public function restoreObjectByPid($pid, array &$context) {
    $object = islandora_object_load($pid);
    if ($object) {
      $object->state = 'A';
      $context['message'] = $this->t('Restoring @pid.', ['@pid' => $pid]);
    }
    else {
      $context['message'] = $this->t('Unable to restore @pid, could not load it.', ['@pid' => $pid]);
    }
  }

  /**
   * Purges the deleted object.
   *
   * @param string $pid
   *   The pid of the object being purged.
   * @param array $context
   *   Context of the batch.
   */
  public function purgeObjectByPid($pid, array &$context) {
    $object = islandora_object_load($pid);
    if ($object) {
      islandora_delete_object($object);
      $context['message'] = $this->t('Purging @pid.', ['@pid' => $pid]);
    }
    else {
      $context['message'] = $this->t('Unable to purge @pid, could not load it.', ['@pid' => $pid]);
    }
  }

}
