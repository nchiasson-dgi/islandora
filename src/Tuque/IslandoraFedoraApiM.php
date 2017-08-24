<?php

namespace Drupal\islandora\Tuque;

require_once __DIR__ . '/Base.php';

use FedoraApiM;

/**
 * Class IslandoraFedoraApiM
 * @package Drupal\islandora\Tuque
 */
class IslandoraFedoraApiM extends FedoraApiM {

  /**
   * Update a datastream.
   *
   * Either changing its metadata, updaing the datastream contents or both.
   *
   * @throws Exception
   *   If the modify datastream request was block by some module.
   *
   * @see FedoraApiM::modifyDatastream
   */
  public function modifyDatastream($pid, $dsid, $params = array()) {
    $object = islandora_object_load($pid);
    $datastream = $object[$dsid];
    $context = array(
      'action' => 'modify',
      'block' => FALSE,
      'params' => $params,
    );
    islandora_alter_datastream($object, $datastream, $context);
    $params = $context['params'];
    // Anything may be altered during the alter_datastream hook invocation so
    // we need to update our time to the change we know about.
    if (isset($params['lastModifiedDate']) && $params['lastModifiedDate'] < (string) $object->lastModifiedDate) {
      $params['lastModifiedDate'] = (string) $object->lastModifiedDate;
    }
    if ($context['block']) {
      throw new Exception('Modify Datastream was blocked.');
    }
    return $this->callParentWithLocking('modifyDatastream', $pid, $pid, $dsid, $params);
  }

  /**
   * Update Fedora Object parameters.
   *
   * @see FedoraApiM::modifyObject
   */
  public function modifyObject($pid, $params = NULL) {
    $object = islandora_object_load($pid);
    $context = array(
      'action' => 'modify',
      'block' => FALSE,
      'params' => $params,
    );
    islandora_alter_object($object, $context);
    $params = $context['params'];
    if ($context['block']) {
      throw new Exception('Modify Object was blocked.');
    }
    return $this->callParentWithLocking('modifyObject', $pid, $pid, $params);
  }

  /**
   * Purge an object.
   *
   * @see FedoraApiM::purgeObject
   */
  public function purgeObject($pid, $log_message = NULL) {
    $object = islandora_object_load($pid);
    $context = array(
      'action' => 'purge',
      'purge' => TRUE,
      'delete' => FALSE,
      'block' => FALSE,
    );
    islandora_alter_object($object, $context);
    try {
      $action = $context['block'] ? 'block' : FALSE;
      $action = (!$action && $context['delete']) ? 'delete' : $action;
      $action = !$action ? 'purge' : $action;
      $models = $object->models;
      switch ($action) {
        case 'block':
          throw new Exception('Purge object was blocked.');

        case 'delete':
          $object->state = 'D';
          return '';

        default:
          $ret = $this->callParentWithLocking('purgeObject', $pid, $pid, $log_message);
          islandora_invoke_object_hooks(ISLANDORA_OBJECT_PURGED_HOOK, $models, $pid);
          return $ret;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('islandora')->error('Failed to purge object @pid</br>code: @code<br/>message: @msg', array(
          '@pid' => $pid,
          '@code' => $e->getCode(),
          '@msg' => $e->getMessage()));
      throw $e;
    }
  }

  /**
   * Wraps purgeDatastream for semaphore locking.
   *
   * @see FedoraApiM::purgeDatastream
   */
  public function purgeDatastream($pid, $dsid, $params = array()) {
    return $this->callParentWithLocking('purgeDatastream', $pid, $pid, $dsid, $params);
  }

  /**
   * Wraps ingest for semaphore locking.
   *
   * @see FedoraApiM::ingest
   */
  public function ingest($params = array()) {
    if (isset($params['pid'])) {
      return $this->callParentWithLocking('ingest', $params['pid'], $params);
    }
    else {
      return parent::ingest($params);
    }
  }

  /**
   * Wraps addDatastream for semaphore locking.
   *
   * @see FedoraApiM::addDatastream
   */
  public function addDatastream($pid, $dsid, $type, $file, $params) {
    return $this->callParentWithLocking('addDatastream', $pid, $pid, $dsid, $type, $file, $params);
  }

  /**
   * Wraps addRelationship for semaphore locking.
   *
   * @see FedoraApiM::addRelationship
   */
  public function addRelationship($pid, $relationship, $is_literal, $datatype = NULL) {
    return $this->callParentWithLocking('addRelationship', $pid, $pid, $relationship, $is_literal, $datatype);
  }

  /**
   * Call a parent function while using semaphores as configured.
   *
   * All extra arguments are passed along to the callback.
   *
   * @param callable $callback
   *   The method we are wrapping.
   * @param string $pid
   *   The PID to create a semaphore for.
   */
  protected function callParentWithLocking($callback, $pid) {
    $args = array_slice(func_get_args(), 2);
    $locked = FALSE;

    if (\Drupal::config('islandora.settings')->get('islandora_use_object_semaphores')) {
      $lock_period = \Drupal::config('islandora.settings')->get('islandora_semaphore_period');
      while (!\Drupal::lock()->acquire($pid, $lock_period)) {
        // Wait for the lock to be free. In the worst case forever.
        while (\Drupal::lock()->wait($pid)) {
        }
      }
      $locked = TRUE;
    }

    if ($locked) {
      try {
        $to_return = call_user_func_array(array($this, "parent::$callback"), $args);
      }
      catch (Exception $e) {
        // Release the lock in event of exception.
        \Drupal::lock()->release($pid);
        throw $e;
      }
      \Drupal::lock()->release($pid);
      return $to_return;
    }
    else {
      return call_user_func_array(array($this, "parent::$callback"), $args);
    }
  }

}
