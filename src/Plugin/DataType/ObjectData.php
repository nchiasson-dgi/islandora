<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * Object data wrapper.
 *
 * @DataType(
 *   id = "islandora_object",
 *   label = @Translation("Islandora Object"),
 *   definition_class = "\Drupal\islandora\TypedData\ObjectDataDefinition"
 * )
 */
class ObjectData extends Proxy {
  // Nothing to do...
}
