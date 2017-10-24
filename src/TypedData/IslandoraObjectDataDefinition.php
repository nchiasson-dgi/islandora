<?php

namespace Drupal\islandora\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

class IslandoraObjectDataDefinition extends ComplexDataDefinitionBase {
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions = [
        'pid' => $this->typedDataManager->createDataDefinition('string'),
        'label' => $this->typedDataManager->createDataDefinition('string'),
        'owner' => $this->typedDataManager->createDataDefinition('string'),
        'state' => $this->typedDataManager->createDataDefinition('string'),
        'models' => $this->typedDataManager->createListDataDefinition('string'),
        'createdDate' => $this->typedDataManager->createDataDefinition('datetime_iso8601'),
        //'datastreams' => $this->typedDataManager->createListDataDefinition('islandora_datastream'),
      ];
    }

    return $this->propertyDefinitions;
  }

  public function getMainPropertyName() {
    return 'pid';
  }
}
