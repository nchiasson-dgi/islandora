<?php

namespace Drupal\islandora\Tuque;

// XXX: Class from tuque do not autoload properly which causes problems
// for deserialization.
@include_once 'sites/all/libraries/tuque/Datastream.php';
@include_once 'sites/all/libraries/tuque/FedoraRelationships.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Datastream.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraRelationships.php";

use FedoraRelsExt;

/**
 * Class IslandoraFedoraRelsExt
 * @package Drupal\islandora\Tuque
 */
class IslandoraFedoraRelsExt extends FedoraRelsExt {}
