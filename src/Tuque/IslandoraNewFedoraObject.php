<?php

namespace Drupal\islandora\Tuque;

require_once __DIR__ . '/Base.php';

use NewFedoraObject;

/**
 * Class IslandoraNewFedoraObject.
 *
 * @package Drupal\islandora\Tuque
 */
class IslandoraNewFedoraObject extends NewFedoraObject {
  protected $newFedoraDatastreamClass = IslandoraNewFedoraDatastream::class;
  protected $fedoraDatastreamClass = IslandoraFedoraDatastream::class;
  protected $fedoraRelsExtClass = IslandoraFedoraRelsExt::class;

}
