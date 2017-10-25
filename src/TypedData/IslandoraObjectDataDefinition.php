<?php

namespace Drupal\islandora\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Tuque Object TypedData wrapper definition.
 */
class IslandoraObjectDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions = [
        'pid' => $this->typedDataManager->createDataDefinition('string'),
        'label' => $this->typedDataManager->createDataDefinition('string'),
        'owner' => $this->typedDataManager->createDataDefinition('string'),
        'state' => $this->typedDataManager->createDataDefinition('string'),
        'models' => $this->typedDataManager->createListDataDefinition('string'),
        'createdDate' => $this->typedDataManager->createDataDefinition('datetime_iso8601'),
      ];
    }

    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getMainPropertyName() {
    return 'pid';
  }

}
