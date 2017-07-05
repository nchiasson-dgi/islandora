<?php

namespace Drupal\islandora\Tuque;

@include_once 'sites/all/libraries/tuque/Datastream.php';
@include_once 'sites/all/libraries/tuque/FedoraRelationships.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Datastream.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraRelationships.php";

class IslandoraFedoraRelsInt extends \FedoraRelsInt {}