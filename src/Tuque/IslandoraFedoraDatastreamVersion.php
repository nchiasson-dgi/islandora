<?php

namespace Drupal\islandora\Tuque;
// XXX: Class from tuque do not autoload properly which causes problems
// for deserialization.
@include_once 'sites/all/libraries/tuque/Datastream.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Datastream.php";

use FedoraDatastreamVersion;

/**
 * Class IslandoraFedoraDatastreamVersion
 * @package Drupal\islandora\Tuque
 */
class IslandoraFedoraDatastreamVersion extends FedoraDatastreamVersion {
  protected $fedoraRelsIntClass = IslandoraFedoraRelsInt::class;
  protected $fedoraDatastreamVersionClass = IslandoraFedoraDatastreamVersion::class;
}
