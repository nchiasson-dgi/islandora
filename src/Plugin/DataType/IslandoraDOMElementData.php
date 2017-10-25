<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * DOMElement data wrapper.
 *
 * @DataType(
 *   id = "islandora_domelement",
 *   label = @Translation("Islandora DOMElement"),
 *   definition_class = "\Drupal\islandora\TypedData\IslandoraDOMNodeDataDefinition"
 * )
 */
class IslandoraDOMElementData extends Proxy {
  // Nothing to do...
}
