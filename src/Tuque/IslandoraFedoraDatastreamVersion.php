<?php

namespace Drupal\islandora\Tuque;

require_once __DIR__ . '/Base.php';

use FedoraDatastreamVersion;

/**
 * Class IslandoraFedoraDatastreamVersion
 * @package Drupal\islandora\Tuque
 */
class IslandoraFedoraDatastreamVersion extends FedoraDatastreamVersion {
  protected $fedoraRelsIntClass = IslandoraFedoraRelsInt::class;
  protected $fedoraDatastreamVersionClass = IslandoraFedoraDatastreamVersion::class;
}
