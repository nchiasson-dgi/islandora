<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\islandora\TypedData\IslandoraXPathTrait;
use Drupal\taxonomy\VocabularyInterface;
use AbstractObject;

/**
 * Rules condition; check that an object has a relationship.
 *
 * @Condition(
 *   id = "islandora_rules_datastream_has_xpath",
 *   label = @Translation("Check an object's for the presence of an XPath"),
 *   category = @Translation("Islandora"),
 *   context = {
 *     "object" = @ContextDefinition("islandora_object",
 *       label = @Translation("Subject"),
 *       description = @Translation("The object to check for the datastream.")),
 *     "datastream_id" = @ContextDefinition("string",
 *       label = @Translation("Datastream ID",
 *       description = @Translation("A string containing the identity of the datastream to look for on the object."))),
 *     "xpath" = @ContextDefinition("string",
 *       label = @Translation("XPath"),
 *       description = @Translation("The XPath to test.")),
 *     "xpath_namespaces" = @ContextDefinition("taxonomy_vocabulary",
 *       label = @Translation("XPath Namespace Taxonomy"),
 *       description = @Translation("A flat taxonomy of which the terms are namespace prefixes and the description contains the URI for the namespace."))
 *       required = false,
 *   }
 * )
 */
class IslandoraRulesDatastreamHasXpath extends RulesConditionBase {
  use IslandoraXPathTrait;

  /**
   * {@inheritdoc}
   */
  protected function doEvaluate(AbstractObject $object, $datastream_id, $xpath, VocabularyInterface $xpath_namespaces = NULL) {
    $dom_xpath = $this->loadXpathFromObject($object, $datastream_id, $xpath_namespaces);
    $results = $dom_xpath->query($xpath);
    return $results->length > 0;
  }

}
