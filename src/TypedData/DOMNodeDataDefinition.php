<?php

namespace Drupal\islandora\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * DOMNode TypedData wrapper definition.
 */
class DOMNodeDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions = [
        'nodeValue' => $this->typedDataManager->createDataDefinition('string'),
        'textContent' => $this->typedDataManager->createDataDefinition('string')
          ->setComputed(TRUE)
          ->setReadOnly(TRUE),
      ];
    }

    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getMainPropertyName() {
    return 'nodeValue';
  }

}
