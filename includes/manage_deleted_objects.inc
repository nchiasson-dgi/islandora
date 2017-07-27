<?php

/**
 * @file
 * Handles the management of deleted objects.
 */

/**
 * Gets PIDS of all deleted objects.
 *
 * @return array
 *   PIDS of deleted objects
 */
function islandora_get_deleted_objects($content_models, $limit, $offset) {
  $tuque = islandora_get_tuque_connection();
  $repository = $tuque->repository;
  $query = islandora_get_deleted_query($content_models, $offset);
  $objects = $repository->ri->sparqlQuery($query, $limit);
  $deleted_objects = array();
  foreach ($objects as $object) {
    if ($object['object']['value'] != "fedora-system:FedoraObject-3.0") {
      $pid = $object['subject']['value'];
      $cm_pid = $object['object']['value'];
      $title = $object['label']['value'];
      $deleted_objects[$pid] = array(
        'title' => $title, 'pid' => $pid,
        'content_model' => $content_models[$cm_pid],
      );
    }
  }
  return $deleted_objects;
}

/**
 * Gets PIDS of all content models associated with deleted objects.
 *
 * @return array
 *   array of content model pids
 */
function islandora_get_contentmodels_with_deleted_members() {
  $tuque = islandora_get_tuque_connection();
  $repository = $tuque->repository;
  $query = "PREFIX fm: <info:fedora/fedora-system:def/model#>
    SELECT DISTINCT ?object ?label FROM <#ri>
            WHERE {
                   {?subject fm:state fm:Deleted;
                             fm:hasModel ?object;
                   }
            OPTIONAL{
                        ?object fm:label  ?label
                     }
                  }";

  $objects = $repository->ri->sparqlQuery($query, -1);
  $content_models = array();
  foreach ($objects as $object) {
    if ($object['object']['value'] != "fedora-system:FedoraObject-3.0") {
      $content_models[$object['object']['value']] = $object['label']['value'];
    }
  }
  return $content_models;
}

/**
 * Restores deleted object.
 *
 * @param string $pid
 *   PID of object to be restored
 */
function islandora_restore_object_by_pid($pid) {
  $fedora_object = islandora_object_load($pid);
  $fedora_object->state = 'A';
}

/**
 * Purges deleted object.
 *
 * @param string $pid
 *   PID of object to be restored
 */
function islandora_purge_object_by_pid($pid) {
  $fedora_object = islandora_object_load($pid);
  $fedora_object->repository->purgeObject($pid);
}

/**
 * Get query to find all deleted objects by content type.
 *
 * @param array $content_models
 *   Content models to restrict search
 * @param int $offset
 *   offset to be added to search
 *
 * @return string
 *   Sparql query
 */
function islandora_get_deleted_query($content_models, $offset = 0) {
  $candidates = array_keys($content_models);
  $first_contentmodel = array_shift($candidates);
  $prefix = "PREFIX fm: <" . FEDORA_MODEL_URI . "> ";
  $select = "SELECT DISTINCT ?subject ?label ?object FROM <#ri> WHERE { ";
  $where_clause = "{?subject fm:hasModel <info:fedora/$first_contentmodel>;
                            fm:state fm:Deleted;
                            fm:hasModel ?object;
                   }";
  $suffix = "} ORDER BY ?subject OFFSET $offset";
  $unions = '';
  foreach ($candidates as $contentmodel) {
    $unions .= "UNION {?subject fm:hasModel <info:fedora/$contentmodel>;
                            fm:state fm:Deleted;
                            fm:hasModel ?object;
                   }
                ";
  }
  $optional = "OPTIONAL{?subject fm:label ?label}";
  return "$prefix $select $where_clause $unions $optional $suffix";
}
