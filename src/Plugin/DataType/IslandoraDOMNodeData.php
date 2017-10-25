<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * DOMNode data wrapper.
 *
 * @DataType(
 *   id = "islandora_domnode",
 *   label = @Translation("Islandora DOMNode"),
 *   definition_class = "\Drupal\islandora\TypedData\IslandoraDOMNodeDataDefinition"
 * )
 */
class IslandoraDOMNodeData extends Proxy {
  // Nothing to do...
}
