<?php

/**
 * @file
 * Wrapper around the tuque library, allows for autoloading of Islandora Tuque
 * Objects.
 *
 * @todo Overload functions and apply pre/post hooks.
 */

namespace Drupal\islandora\Tuque;

@include_once 'sites/all/libraries/tuque/Object.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Object.php";

class IslandoraNewFedoraObject extends \NewFedoraObject {
  protected $newFedoraDatastreamClass = '\Drupal\islandora\Tuque\IslandoraNewFedoraDatastream';
  protected $fedoraDatastreamClass = '\Drupal\islandora\Tuque\IslandoraFedoraDatastream';
  protected $fedoraRelsExtClass = '\Drupal\islandora\Tuque\IslandoraFedoraRelsExt';

}
