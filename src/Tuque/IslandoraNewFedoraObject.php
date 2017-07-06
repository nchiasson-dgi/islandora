<?php

namespace Drupal\islandora\Tuque;

// XXX: Class from tuque do not autoload properly which causes problems
// for deserialization.
@include_once 'sites/all/libraries/tuque/Object.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Object.php";

use NewFedoraObject;

/**
 * Class IslandoraNewFedoraObject
 * @package Drupal\islandora\Tuque
 */
class IslandoraNewFedoraObject extends NewFedoraObject {
  protected $newFedoraDatastreamClass = IslandoraNewFedoraDatastream::class;
  protected $fedoraDatastreamClass = IslandoraFedoraDatastream::class;
  protected $fedoraRelsExtClass = IslandoraFedoraRelsExt::class;

}
