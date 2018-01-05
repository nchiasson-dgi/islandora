<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\islandora\TypedData\XPathTrait;

/**
 * Rules action; Load a DOMXPath instance from some XML.
 *
 * @RulesAction(
 *   id = "islandora_rules_datastream_load_domxpath",
 *   label = @Translation("Load a DOMXPath for a given XML"),
 *   category = @Translation("Islandora DOMXPath"),
 *   context = {
 *     "datastream" = @ContextDefinition("string",
 *       label = @Translation("XML"),
 *       description = @Translation("A string containing the XML to load.")),
 *   },
 *   provides = {
 *     "islandora_domxpath" = @ContextDefinition("islandora_domxpath",
 *       label = @Translation("Loaded DOMXPath instance")),
 *   }
 * )
 */
class RulesDatastreamLoadDomXPath extends RulesActionBase {
  use XPathTrait;

  /**
   * {@inheritdoc}
   */
  protected function doExecute($string) {
    $this->setProvidedValue("islandora_domxpath", $this->loadXpathFromString($string));
  }

}
