<?php

/**
 * @file
 * A list of orphaned Islandora objects.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Submit handler for the workflow management confirm form.
 *
 * @param array $form
 *   An array representing a form within Drupal.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Drupal form state.
 */
function islandora_manage_orphaned_objects_confirm_submit(array $form, FormStateInterface $form_state) {
  $batch = islandora_delete_orphaned_objects_create_batch($form_state->get('pids_to_delete'));
  batch_set($batch);
}

/**
 * Query for orphaned objects.
 *
 * @return array
 *   An array containing the results of the orphaned objects queries.
 */
function islandora_get_orphaned_objects() {
  $connection = islandora_get_tuque_connection();
  // SPARQL: get orphaned objects, exclude any with a living parent.
  $object_query = <<<EOQ
!prefix
SELECT DISTINCT ?object ?title
WHERE {
  ?object <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> ;
               ?p ?otherobject .
   ?object <fedora-model:label> ?title;
  OPTIONAL {
    ?otherobject <fedora-model:hasModel> ?model .
  } .
  FILTER (!bound(?model))

  # Filter by "parent" relationships
  FILTER (!dead_parent_relationships)

  # Exclude objects with live parents
  OPTIONAL {
    !live_parent_relationships .
    ?liveparent <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> .
  }
  !optionals
  !filters
  FILTER (!bound(?liveparent))
} ORDER BY ?object
EOQ;
  $parent_relationships = \Drupal::moduleHandler()->invokeAll('islandora_solution_pack_child_relationships', ['all']);
  $parent_relationships['prefix'] = array_unique($parent_relationships['prefix']);
  $parent_relationships['predicate'] = array_unique($parent_relationships['predicate']);
  if (count($parent_relationships['predicate']) == 0) {
    // No predicates to search for. Exit early.
    return [];
  }
  $optionals = (array) \Drupal::moduleHandler()->invoke('islandora_xacml_api', 'islandora_basic_collection_get_query_optionals', ['view']);
  $filter_modules = [
    'islandora_xacml_api',
    'islandora',
  ];
  $filters = [];
  foreach ($filter_modules as $module) {
    $filters = array_merge_recursive($filters, (array) \Drupal::moduleHandler()->invoke($module, 'islandora_basic_collection_get_query_filters', ['view']));
  }
  $filter_map = function ($filter) {
    return "FILTER($filter)";
  };
  $parent_map = function ($parent) {
    return "?object $parent ?liveparent";
  };
  // Use separate queries for different object types.
  $sparql_query_objects = strtr($object_query, [
    '!optionals' => !empty($optionals) ? ('OPTIONAL {{' . implode('} UNION {', $optionals) . '}}') : '',
    '!filters' => !empty($filters) ? implode(' ', array_map($filter_map, $filters)) : '',
    '!dead_parent_relationships' => '?p = ' . implode(' || ?p = ', $parent_relationships['predicate']),
    '!live_parent_relationships' => '{' . implode(' } UNION { ', array_map($parent_map, $parent_relationships['predicate'])) . '}',
    '!prefix' => implode("\n", $parent_relationships['prefix']),
  ]);
  $results = $connection->repository->ri->sparqlQuery($sparql_query_objects);
  return $results;
}

/**
 * Constructs the batch that will go out and delete objects.
 *
 * @param array $pids
 *   The array of pids to be deleted.
 *
 * @return array
 *   An array detailing the batch that is about to be run.
 */
function islandora_delete_orphaned_objects_create_batch(array $pids) {
  // Set up a batch operation.
  $batch = [
    'operations' => [
      ['islandora_delete_orphaned_objects_batch_operation', [$pids]],
    ],
    'title' => t('Deleting the selected objects...'),
    'init_message' => t('Preparing to delete objects.'),
    'progress_message' => t('Time elapsed: @elapsed <br/>Estimated time remaining @estimate.'),
    'error_message' => t('An error has occurred.'),
    'finished' => 'islandora_delete_orphaned_objects_batch_finished',
    'file' => drupal_get_path('module', 'islandora') . '/includes/orphaned_objects.inc',
  ];
  return $batch;
}

/**
 * Constructs and performs the deleting batch operation.
 *
 * @param array $pids
 *   An array of pids to be deleted.
 * @param array $context
 *   The context of the Drupal batch.
 */
function islandora_delete_orphaned_objects_batch_operation(array $pids, array &$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox'] = [];
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['pids'] = $pids;
    $context['sandbox']['total'] = count($pids);
    $context['results']['success'] = [];
  }
  if (!empty($context['sandbox']['pids'])) {
    $target_pid = array_pop($context['sandbox']['pids']);
    $target_object = islandora_object_load($target_pid);
    $context['message'] = t('Deleting @label (@pid) (@current of @total)...', [
      '@label' => $target_object->label,
      '@pid' => $target_pid,
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
    islandora_delete_object($target_object);
    $object_check = islandora_object_load($target_pid);
    if ($object_check) {
      drupal_set_message(t('Could not delete %pid. You may not have permission to manage this object.',
        [
          '%pid' => $target_pid,
        ]), 'error');
    }
    else {
      $context['results']['success'][] = $target_pid;
    }
    $context['sandbox']['progress']++;
  }
  $context['finished'] = ($context['sandbox']['total'] == 0) ? 1 : ($context['sandbox']['progress'] / $context['sandbox']['total']);
}

/**
 * Finished function for the orphaned objects delete batch.
 *
 * @param bool $success
 *   Whether the batch was successful or not.
 * @param array $results
 *   An array containing the results of the batch operations.
 * @param array $operations
 *   The operations array that was used in the batch.
 */
function islandora_delete_orphaned_objects_batch_finished($success, array $results, array $operations) {
  if ($success) {
    $message = \Drupal::translation()->formatPlural(count($results['success']), 'One object deleted.', '@count objects deleted.');
  }
  else {
    $message = t('Finished with an error.');
  }
  drupal_set_message($message);
}
