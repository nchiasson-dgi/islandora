<?php

namespace Drupal\islandora\Commands;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Drush commandfile for Islandora.
 */
class IslandoraCommands extends DrushCommands {

  /**
   * Module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Install Solution Pack objects.
   *
   * @option module
   *   The module for which to install the required objects.
   * @option force
   *   Force reinstallation of the objects.
   * @usage drush -u1 ispiro --module=islandora
   *   Install missing solution pack objects for the "islandora" module.
   * @usage drush -u1 ispiro --module=islandora --force
   *   Install all solution pack objects for the "islandora" module, purging
   *   any which currently exist.
   * @validate-module-enabled islandora
   *
   * @islandora-user-wrap
   * @islandora-require-option module
   *
   * @command islandora:solution-pack-install-required-objects
   * @aliases ispiro,islandora-solution-pack-install-required-objects
   */
  public function solutionPackInstallRequiredObjects(array $options = [
    'module' => self::REQ,
    'force' => self::OPT,
  ]) {
    module_load_include('inc', 'islandora', 'includes/solution_packs');

    $module = $options['module'];
    if ($this->moduleHandler->moduleExists($module)) {
      islandora_install_solution_pack(
          $module, 'install', $options['force']
      );
    }
    else {
      $this->logger()->warning('"{@module}" is not installed/enabled?...', [
        '@module' => $module,
      ]);
    }
  }

  /**
   * Uninstall Solution Pack objects.
   *
   * @option module
   *   The module for which to uninstall the required objects.
   * @option force
   *   Force uninstallation of the objects.
   * @usage drush -u1 ispuro --module=islandora
   *   Uninstall solution pack objects for the "islandora" module.
   * @usage drush -u1 ispuro --module=islandora --force
   *   Force uninstallation of all solution pack objects for the "islandora"
   *   module.
   * @validate-module-enabled islandora
   *
   * @islandora-user-wrap
   * @islandora-require-option module
   *
   * @command islandora:solution-pack-uninstall-required-objects
   * @aliases ispuro,islandora-solution-pack-uninstall-required-objects
   */
  public function solutionPackUninstallRequiredObjects(array $options = [
    'module' => self::REQ,
    'force' => self::OPT,
  ]) {
    module_load_include('inc', 'islandora', 'includes/solution_packs');

    $module = $options['module'];
    if ($this->moduleHandler->moduleExists($module)) {
      islandora_uninstall_solution_pack(
          $module, $options['force']
      );
    }
    else {
      $this->logger()->warning('"{@module}" is not installed/enabled?...', [
        '@module' => $module,
      ]);
    }
  }

  /**
   * Get Solution Pack object status.
   *
   * @option module
   *   The module for which to get the status of the required objects.
   * @usage drush -u1 ispros
   *   Get the status of all solution pack objects.
   * @usage drush -u1 ispros --module=islandora
   *   Get the status of solution pack objects for the "islandora" module.
   * @validate-module-enabled islandora
   * @table-style default
   * @field-labels
   *   module: Module
   *   pid: PID
   *   status: Machine Status
   *   status_label: Readable Status
   * @default-fields module,pid,status
   *
   * @islandora-user-wrap
   *
   * @command islandora:solution-pack-required-objects-status
   * @aliases ispros,islandora-solution-pack-required-objects-status
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Rows of required object state info.
   */
  public function solutionPackRequiredObjectsStatus(array $options = [
    'module' => self::REQ,
  ]) {
    module_load_include('inc', 'islandora', 'includes/solution_packs');

    $module = $options['module'];
    $required_objects = [];
    if ($module && $this->moduleHandler->moduleExists($module)) {
      $required_objects[$module] = islandora_solution_packs_get_required_objects($module);
    }
    elseif ($module === NULL) {
      $required_objects = islandora_solution_packs_get_required_objects();
    }
    else {
      throw new \Exception(strtr('"@module" is not installed/enabled?...', [
        '@module' => $module,
      ]));
    }

    $rows = [];
    foreach ($required_objects as $module => $info) {
      foreach ($info['objects'] as $object) {
        $status = islandora_check_object_status($object);
        $rows[] = [
          'module' => $module,
          'pid' => $object->id,
          'status' => $status['status'],
          'status_label' => $status['status_friendly'],
        ];
      }
    }

    return new RowsOfFields($rows);
  }

  /**
   * Install Solution Pack content models.
   *
   * @option module
   *   The module for which to install the content models.
   * @usage drush -u1 ispicm --module=islandora
   *   Install missing solution pack objects for the "islandora" module.
   * @validate-module-enabled islandora
   *
   * @islandora-user-wrap
   * @islandora-require-option module
   *
   * @command islandora:solution-pack-install-content_models
   * @aliases ispicm,islandora-solution-pack-install-content_models
   */
  public function solutionPackInstallContentModels(array $options = [
    'module' => self::REQ,
  ]) {
    module_load_include('inc', 'islandora', 'includes/solution_packs');
    $module = $options['module'];

    if ($this->moduleHandler->moduleExists($module)) {
      $info = islandora_solution_packs_get_required_objects($module);
      $objects_to_add = [];
      foreach ($info['objects'] as $candidate) {
        if (in_array('fedora-system:ContentModel-3.0', $candidate->models)) {
          $objects_to_add[] = $candidate;
        }
      }
      if (count($objects_to_add) > 0) {
        foreach ($objects_to_add as $object_to_add) {
          $old_object = islandora_object_load($object_to_add->id);
          if ($old_object) {
            $deleted = islandora_delete_object($old_object);
            if (!$deleted) {
              $this->logger()->error('{@object} did not delete.', [
                '@object' => $old_object->id,
              ]);
              continue;
            }
          }
          $new_object = islandora_add_object($object_to_add);
          $verb = $deleted ? dt("Replaced") : dt("Added");
          if ($new_object) {
            $this->logger()->notice("{0} {1} - {2}", [
              $verb,
              $object_to_add->id,
              $object_to_add->label,
            ]);
          }
        }
      }
      else {
        $this->logger()->notice('{0} had nothing to change.', [$module]);
      }
    }
    else {
      throw new \Exception("$module is not enabled...");
    }
  }

}
