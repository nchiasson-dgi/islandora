<?php

namespace Drupal\islandora\TypedData;

use DOMDocument;
use AbstractObject;
use AbstractDatastream;
use DOMXPath;
use Drupal\taxonomy\VocabularyInterface;

/**
 * DOMXPath helper methods.
 */
trait IslandoraXPathTrait {

  /**
   * Load XML into a DOMXPath and optionally register namespaces.
   *
   * @param string $xml
   *   Some XML to parse into a DOMDocument, with which to create a DOMXPath.
   * @param VocabularyInterface $vocab
   *   An optional taxonomy vocabulary mapping terms and URIs for XML
   *   namespaces.
   *
   * @return DOMXPath
   *   A DOMXPath instance with the loaded XML.
   */
  protected function loadXpathFromString($xml, VocabularyInterface $vocab = NULL) {
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    $xpath = new DOMXPath($doc);

    if (isset($vocab)) {
      $this->loadXpathVocab($xpath, $vocab);
    }

    return $xpath;
  }

  /**
   * Load datastream XML into a DOMXPath with optional namespace registration.
   *
   * @param AbstractDatastream $datastream
   *   The datastream from which to grab content.
   * @param VocabularyInterface $vocab
   *   An optional taxonomy vocabulary mapping terms and URIs for XML
   *   namespaces.
   *
   * @return DOMXPath
   *   A DOMXPath instance with the loaded XML.
   */
  protected function loadXpathFromDatastream(AbstractDatastream $datastream, VocabularyInterface $vocab = NULL) {
    return $this->loadXpathFromString($datastream->content);
  }

  /**
   * Load datastream XML into a DOMXPath with optional namespace registration.
   *
   * @param AbstractObject $object
   *   The object from which to grab content.
   * @param string $datastream_id
   *   The ID of the datastream on the object from which to grab content.
   * @param VocabularyInterface $vocab
   *   An optional taxonomy vocabulary mapping terms and URIs for XML
   *   namespaces.
   *
   * @return DOMXPath
   *   A DOMXPath instance with the loaded XML.
   */
  protected function loadXpathFromObject(AbstractObject $object, $datastream_id, VocabularyInterface $vocab = NULL) {
    return $this->loadXpathFromDatastream($object[$datastream_id]);
  }

  /**
   * Register namespaces described in a vocab on a DOMXPath instance.
   *
   * @param DOMXPath $xpath
   *   The DOMXPath instance on which to register namespaces.
   * @param VocabularyInterface $vocab
   *   An optional taxonomy vocabulary mapping terms and URIs for XML
   *   namespaces.
   */
  protected function loadXpathVocab(DOMXPath $xpath, VocabularyInterface $vocab) {
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocab->id());

    foreach ($tree as $term) {
      $xpath->registerNamespace($term->getName(), $term->getDescription());
    }
  }

}
