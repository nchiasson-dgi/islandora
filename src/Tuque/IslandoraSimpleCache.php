<?php
namespace Drupal\islandora\Tuque;

@include_once 'sites/all/libraries/tuque/Cache.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Cache.php";

class IslandoraSimpleCache extends \SimpleCache {}
