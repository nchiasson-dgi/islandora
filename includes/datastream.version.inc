<?php

/**
 * @file
 * The form displayed that shows the datastream version history.
 */

use Drupal\Core\Link;
use Drupal\islandora\Utility\Links;

/**
 * Function that returns markup for the datastream versions page.
 */
function islandora_datastream_version_table($datastream) {
  module_load_include('inc', 'islandora', 'includes/datastream');
  module_load_include('inc', 'islandora', 'includes/utilities');

  $linker = new Links(
    \Drupal::config('islandora'),
    \Drupal::getContainer()->get('module_handler')
  );
  $parent = $datastream->parent;
  $table = [];
  $table['#title'] = t("@dsid Previous Versions", ['@dsid' => $datastream->id]);
  $audit_values = islandora_get_audit_trail($parent->id, $datastream->id);

  $header = [];
  $header[] = array('data' => t('Created Date'));
  $header[] = array('data' => t('Size'));
  $header[] = array('data' => t('Label'));
  $header[] = array('data' => t('Responsibility'));
  $header[] = array('data' => t('Mime type'));
  $header[] = array('data' => t('Operations'), 'colspan' => '2');
  $rows = [];

  foreach ($datastream as $version => $datastream_version) {
    $row = [];
    $reponsibility = $parent->owner;
    foreach ($audit_values as $audit_value) {
      $internal = $datastream_version->createdDate;
      if ($audit_value['date'] == $datastream_version->createdDate) {
        $reponsibility = $audit_value['responsibility'];
      }
    }
    $user = user_load_by_name($reponsibility);
    if ($user) {
      $user_id = $user->id();
      $user_val = Link::createFromRoute($reponsibility, 'entity.user.canonical', ['user' => $user_id]);
    }
    else {
      $user_val = $reponsibility;
    }

    $row[] = [
      'class' => 'datastream-date',
      'data' => $linker->view($datastream, $version, $datastream_version->createdDate->format(DATE_RFC850)),
    ];
    $row[] = array(
      'class' => 'datastream-size',
      'data' => islandora_datastream_get_human_readable_size($datastream_version),
    );
    $row[] = array(
      'class' => 'datastream-label',
      'data' => $datastream_version->label,
    );
    $row[] = array(
      'class' => 'datastream-responsibility',
      'data' => $user_val,
    );
    $row[] = array(
      'class' => 'datastream-mime',
      'data' => $datastream_version->mimeType,
    );
    $row[] = [
      'class' => 'datastream-delete',
      'data' => $linker->delete($datastream, $version),
    ];
    $row[] = [
      'class' => 'datastream-revert',
      'data' => $linker->revert($datastream, $version),
    ];

    $rows[] = $row;
  }

  $table['#type'] = 'table';
  $table['#header'] = $header;
  $table['#rows'] = $rows;

  return $table;
}

/**
 * Process available dsids, mime and extensions for a given object.
 *
 * @param AbstractObject $object
 *   The FedoraObject to process available extensions
 *
 * @return array()
 *   An associative array, merged from calls to
 *   islandora_get_datastreams_requirements_from_content_models()
 *   and an objects dsid's.
 */
function islandora_get_object_extensions(AbstractObject $object) {
  $extensions = islandora_get_datastreams_requirements_from_models($object->models);
  foreach ($object as $datastream) {
    // Could be a datastream not associated in a content model,
    // such as user added.
    if (!isset($extensions[$datastream->id])) {
      // Add the extensions manually.
      $extensions[$datastream->id] = array(
        'id' => $datastream->id,
        'optional' => TRUE,
        'mime' => array($datastream->mimeType),
      );
    }
  }
  return $extensions;
}

/**
 * Gets Audit datastream values from foxml.
 *
 * @param string $pid
 *   PID of parent object
 *
 * @return array
 *   Array of audit values
 */
function islandora_get_audit_trail($pid, $dsid) {
  $url = \Drupal::config('islandora.settings')->get('islandora_base_url');
  $connection = islandora_get_tuque_connection(NULL, $url);
  $xml = $connection->api->m->getObjectXml($pid);
  $simple_xml = simplexml_load_string($xml);
  $fox_ns = "info:fedora/fedora-system:def/foxml#";
  $audit_ns = 'info:fedora/fedora-system:def/audit#';
  $foxml_nodes = $simple_xml->children($fox_ns);
  foreach ($foxml_nodes as $node) {
    if ($node->attributes()->ID == "AUDIT") {
      $content = $node->datastreamVersion->xmlContent;
      $audit_nodes = $content->children($audit_ns);
    }
  }
  $audit_values = array();
  if (isset($audit_nodes)) {
    foreach ($audit_nodes->auditTrail->record as $record) {
      if ($dsid == $record->componentID) {
        $values['responsibility'] = $record->responsibility;
        $values['date'] = $record->date;
        $audit_values[] = $values;
      }
    }
  }
  return $audit_values;
}
