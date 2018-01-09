<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Rules action; Load a datastream from an object.
 *
 * @RulesAction(
 *   id = "islandora_rules_datastream_load",
 *   label = @Translation("Load a datastream from an object."),
 *   category = @Translation("Islandora"),
 *   context = {
 *     "object" = @ContextDefinition("islandora_object",
 *       label = @Translation("Subject"),
 *       description = @Translation("The object from which to load the datastream.")),
 *     "datastream_id" = @ContextDefinition("string",
 *       label = @Translation("Datastream ID",
 *       description = @Translation("A string containing the identity of the datastream to load from the object."))),
 *   },
 *   provides = {
 *     "datastream" = @ContextDefinition("islandora_datastream",
 *       label = @Translation("Loaded datastream instance")),
 *   }
 * )
 */
class RulesDatastreamLoad extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute($object, $datastream_id) {
    $this->setProvidedValue("datastream", $object[$datastream_id]);
  }

}
