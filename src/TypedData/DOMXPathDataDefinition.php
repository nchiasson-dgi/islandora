<?php

namespace Drupal\islandora\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * DOMXPath TypedData wrapper definition.
 */
class DOMXPathDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions = [
        'content' => $this->typedDataManager->createDataDefinition('string')
          ->setComputed(TRUE),
      ];
    }

    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getMainPropertyName() {
    return 'content';
  }

}
