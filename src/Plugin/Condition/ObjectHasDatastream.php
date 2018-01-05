<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use AbstractObject;

/**
 * Rules condition; Check if a datastream exists on an object.
 *
 * @Condition(
 *   id = "islandora_object_has_datastream",
 *   label = @Translation("Check if the object has the given datastream."),
 *   category = @Translation("Islandora"),
 *   context = {
 *     "object" = @ContextDefinition("islandora_object",
 *       label = @Translation("Subject"),
 *       description = @Translation("The object to check for the datastream.")),
 *     "datastream_id" = @ContextDefinition("string",
 *       label = @Translation("Datastream ID",
 *       description = @Translation("A string containing the identity of the datastream to look for on the object."))),
 *   }
 * )
 */
class ObjectHasDatastream extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  protected function doEvaluate(AbstractObject $object, $datastream_id) {
    return isset($object[$datastream_id]);
  }

}
