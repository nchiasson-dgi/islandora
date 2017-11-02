<?php

namespace Drupal\islandora\Tuque;

require_once __DIR__ . '/Base.php';

use NewFedoraDatastream;

/**
 * Class IslandoraNewFedoraDatastream.
 *
 * @package Drupal\islandora\Tuque
 */
class IslandoraNewFedoraDatastream extends NewFedoraDatastream {
  protected $fedoraRelsIntClass = IslandoraFedoraRelsInt::class;
  protected $fedoraDatastreamVersionClass = IslandoraFedoraDatastreamVersion::class;

}
