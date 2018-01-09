<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * Datastream data wrapper.
 *
 * @DataType(
 *   id = "islandora_datastream",
 *   label = @Translation("Islandora Datastream"),
 *   definition_class = "\Drupal\islandora\TypedData\DatastreamDataDefinition"
 * )
 */
class DatastreamData extends Proxy {
  // Nothing new...
}
