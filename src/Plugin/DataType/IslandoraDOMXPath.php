<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\islandora\TypedData\Proxy;

/**
 * DOMXPath data wrapper.
 *
 * @DataType(
 *   id = "islandora_domxpath",
 *   label = @Translation("DOMXPath Instance"),
 *   definition_class = "\Drupal\islandora\TypedData\IslandoraDOMXPathDataDefinition"
 * )
 */
class IslandoraDOMXPathData extends Proxy {
 public function get($property_name) {
   if ($property_name === 'content') {
     return $this->getTypedDataManager()->getPropertyInstance($this, $property_name, $this->getValue()->document->saveXML());
   }

   return parent::get($property_name);
 }
}
