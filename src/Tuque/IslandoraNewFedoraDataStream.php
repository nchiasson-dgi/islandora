<?php

namespace Drupal\islandora\Tuque;

@include_once 'sites/all/libraries/tuque/Datastream.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Datastream.php";

class IslandoraNewFedoraDatastream extends \NewFedoraDatastream {
  protected $fedoraRelsIntClass = '\Drupal\islandora\Tuque\IslandoraFedoraRelsInt';
  protected $fedoraDatastreamVersionClass = '\Drupal\islandora\Tuque\IslandoraFedoraDatastreamVersion';
}