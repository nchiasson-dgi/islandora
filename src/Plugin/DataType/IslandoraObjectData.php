<?php

namespace Drupal\islandora\Plugin\DataType;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use IteratorAggregate;
use Exception;

/**
 * @DataType(
 *   id = "islandora_object",
 *   label = @Translation("Islandora Object"),
 *   definition_class = "\Drupal\islandora\TypedData\IslandoraObjectDataDefinition"
 * )
 */
class IslandoraObjectData extends Map implements IteratorAggregate, ComplexDataInterface {

  /**
   * {@inheritDoc}
   *
   * Largely copypasta from Map, except made to reference the object instead of
   * an arbitrary associative array.
   */
  public function get($property_name) {
    if (!isset($this->properties[$property_name])) {
      $value = $this->getValue()->{$property_name};

      // If the property is unknown, this will throw an exception.
      $this->properties[$property_name] = $this->getTypedDataManager()->getPropertyInstance($this, $property_name, $value);
    }
    return $this->properties[$property_name];
  }

  /**
   * {@inheritDoc}
   */
  public function getValue() {
    return $this->object;
  }

  /**
   * {@inheritDoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->object = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    $this->object->{$property_name} = $this->get($property_name);
  }

}
