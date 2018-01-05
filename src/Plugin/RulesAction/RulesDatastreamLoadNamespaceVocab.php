<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\islandora\TypedData\XPathTrait;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Rules action; Register namespaces on a DOMXPath instance.
 *
 * @RulesAction(
 *   id = "islandora_rules_datastream_load_namespace_vocab",
 *   label = @Translation("Register namespaces on a DOMXPath instance"),
 *   category = @Translation("Islandora DOMXPath"),
 *   context = {
 *     "value" = @ContextDefinition("islandora_domxpath",
 *       label = @Translation("DOMXPath instance"),
 *       description = @Translation("The DOMXPath instance on which to register the namespaces.")),
 *     "xpath_namespaces" = @ContextDefinition("entity:taxonomy_vocabulary",
 *       label = @Translation("XPath Namespace Taxonomy"),
 *       description = @Translation("A flat taxonomy of which the terms are namespace prefixes and the description contains the URI for the namespace."))
 *   },
 * )
 */
class RulesDatastreamLoadNamespaceVocab extends RulesActionBase {
  use XPathTrait;

  /**
   * {@inheritdoc}
   */
  protected function doExecute($value, VocabularyInterface $xpath_namespaces) {
    $this->loadXpathVocab($value, $xpath_namespaces);
  }

}
