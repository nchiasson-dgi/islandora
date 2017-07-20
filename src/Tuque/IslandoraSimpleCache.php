<?php

namespace Drupal\islandora\Tuque;

// XXX: Class from tuque do not autoload properly which causes problems
// for deserialization.
@include_once 'sites/all/libraries/tuque/Cache.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/Cache.php";

use SimpleCache;

/**
 * Class IslandoraSimpleCache
 * @package Drupal\islandora\Tuque
 */
class IslandoraSimpleCache extends SimpleCache {}
