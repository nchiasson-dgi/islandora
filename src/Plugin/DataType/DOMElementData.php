<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * DOMElement data wrapper.
 *
 * @DataType(
 *   id = "islandora_domelement",
 *   label = @Translation("Islandora DOMElement"),
 *   definition_class = "\Drupal\islandora\TypedData\DOMNodeDataDefinition"
 * )
 */
class DOMElementData extends Proxy {
  // Nothing to do...
}
