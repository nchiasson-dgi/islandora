<?php

namespace Drupal\islandora\TypedData;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use IteratorAggregate;
use Exception;

class Proxy extends Map implements IteratorAggregate, ComplexDataInterface {
  protected $object;

  /**
   * {@inheritDoc}
   *
   * Largely copypasta from Map, except made to reference the datastream instead of
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
