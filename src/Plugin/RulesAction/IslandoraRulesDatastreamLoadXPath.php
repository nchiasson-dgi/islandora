<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\islandora\TypedData\IslandoraXPathTrait;

/**
 * Rules action; Load a DOMXPath instance from a datastream.
 *
 * @RulesAction(
 *   id = "islandora_rules_datastream_load_xpath",
 *   label = @Translation("Load a DOMXPath for a given datastream"),
 *   category = @Translation("Islandora DOMXPath"),
 *   context = {
 *     "datastream" = @ContextDefinition("islandora_datastream",
 *       label = @Translation("Datastream"),
 *       description = @Translation("A datastream containing the XML to load.")),
 *   },
 *   provides = {
 *     "islandora_domxpath" = @ContextDefinition("islandora_domxpath",
 *       label = @Translation("Loaded DOMXPath instance")),
 *   }
 * )
 */
class IslandoraRulesDatastreamLoadXPath extends RulesActionBase {
  use IslandoraXPathTrait;

  /**
   * {@inheritdoc}
   */
  protected function doExecute($datastream) {
    $this->setProvidedValue("islandora_domxpath", $this->loadXpathFromDatastream($datastream));
  }

}
