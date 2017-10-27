<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use DOMXPath;

/**
 * Rules action; Run a query against the given DOMXPath instance.
 *
 * @RulesAction(
 *   id = "islandora_rules_datastream_query_xpath",
 *   label = @Translation("Query nodes from DOMXPath instance."),
 *   category = @Translation("Islandora DOMXPath"),
 *   context = {
 *     "xpath" = @ContextDefinition("islandora_domxpath",
 *       label = @Translation("DOMXPath instance"),
 *       description = @Translation("The DOMXPath instance on which to perform the query."),
 *     ),
 *     "query" = @ContextDefinition("string",
 *       label = @Translation("XPath query"),
 *       description = @Translation("The XPath query to perform."),
 *     ),
 *     "context_node" = @ContextDefinition("islandora_domnode",
 *       label = @Translation("Context Node"),
 *       description = @Translation("If provided, the query will be performed relative to the provided node."),
 *       required = false,
 *     ),
 *   },
 *   provides = {
 *     "nodes" = @ContextDefinition("islandora_domnode",
 *       label = @Translation("Loaded datastream instance"),
 *       multiple = true,
 *     ),
 *   }
 * )
 */
class IslandoraRulesDatastreamQueryXPath extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(DOMXPath $xpath, $query) {
    $this->setProvidedValue("nodes", iterator_to_array($xpath->query($query)));
  }

}
