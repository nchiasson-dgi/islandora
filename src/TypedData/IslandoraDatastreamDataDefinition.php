<?php

namespace Drupal\islandora\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Tuque Datastream TypedData wrapper definition.
 */
class IslandoraDatastreamDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions = [
        'id' => $this->typedDataManager->createDataDefinition('string'),
        'state' => $this->typedDataManager->createDataDefinition('string'),
        'label' => $this->typedDataManager->createDataDefinition('string'),
        'mimetype' => $this->typedDataManager->createDataDefinition('string'),
        'parent' => $this->typedDataManager->createDataDefinition('islandora_object'),
        'content' => $this->typedDataManager->createDataDefinition('string'),
      ];
    }

    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getMainPropertyName() {
    return 'id';
  }

}
