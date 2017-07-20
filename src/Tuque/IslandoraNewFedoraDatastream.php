<?php

namespace Drupal\islandora\Tuque;

// XXX: Class from tuque do not autoload properly which causes problems
// for deserialization.
@include_once 'sites/all/libraries/tuque/Datastream.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Datastream.php";

use NewFedoraDatastream;
/**
 * Class IslandoraNewFedoraDatastream
 * @package Drupal\islandora\Tuque
 */
class IslandoraNewFedoraDatastream extends NewFedoraDatastream {
  protected $fedoraRelsIntClass = IslandoraFedoraRelsInt::class;
  protected $fedoraDatastreamVersionClass = IslandoraFedoraDatastreamVersion::class;

}
