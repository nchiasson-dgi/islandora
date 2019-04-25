<?php

namespace Drupal\islandora\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class IslandoraCommands extends DrushCommands {

  /**
   * Install Solution Pack objects.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option module
   *   The module for which to install the required objects.
   * @option force
   *   Force reinstallation of the objects.
   * @usage drush -u 1 ispiro --module=islandora
   *   Install missing solution pack objects for the "islandora" module.
   * @usage drush -u 1 ispiro --module=islandora --force
   *   Install all solution pack objects for the "islandora" module, purging any which currently exist.
   * @validate-module-enabled islandora
   *
   * @command islandora:solution-pack-install-required-objects
   * @aliases ispiro,islandora-solution-pack-install-required-objects
   */
  public function solutionPackInstallRequiredObjects(array $options = ['module' => null, 'force' => null]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
  }

  /**
   * Uninstall Solution Pack objects.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option module
   *   The module for which to uninstall the required objects.
   * @option force
   *   Force uninstallation of the objects.
   * @usage drush -u 1 ispuro --module=islandora
   *   Uninstall solution pack objects for the "islandora" module.
   * @usage drush -u 1 ispuro --module=islandora --force
   *   Force uninstallation of all solution pack objects for the "islandora" module.
   * @validate-module-enabled islandora
   *
   * @command islandora:solution-pack-uninstall-required-objects
   * @aliases ispuro,islandora-solution-pack-uninstall-required-objects
   */
  public function solutionPackUninstallRequiredObjects(array $options = ['module' => null, 'force' => null]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
  }

  /**
   * Get Solution Pack object status.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option module
   *   The module for which to get the status of the required objects.
   * @usage drush -u 1 ispros
   *   Get the status of all solution pack objects.
   * @usage drush -u 1 ispros --module=islandora
   *   Get the status of solution pack objects for the "islandora" module.
   * @validate-module-enabled islandora
   *
   * @command islandora:solution-pack-required-objects-status
   * @aliases ispros,islandora-solution-pack-required-objects-status
   */
  public function solutionPackRequiredObjectsStatus(array $options = ['module' => null]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
  }

  /**
   * Install Solution Pack content models.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option module
   *   The module for which to install the content models.
   * @usage drush -u 1 ispicm --module=islandora
   *   Install missing solution pack objects for the "islandora" module.
   * @validate-module-enabled islandora
   *
   * @command islandora:solution-pack-install-content_models
   * @aliases ispicm,islandora-solution-pack-install-content_models
   */
  public function solutionPackInstallContentModels(array $options = ['module' => null]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
  }

}
