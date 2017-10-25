<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * Object data wrapper.
 *
 * @DataType(
 *   id = "islandora_object",
 *   label = @Translation("Islandora Object"),
 *   definition_class = "\Drupal\islandora\TypedData\IslandoraObjectDataDefinition"
 * )
 */
class IslandoraObjectData extends Proxy {
  // Nothing to do...
}
