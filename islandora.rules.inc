<?php

/**
 * @file
 * Does rule type stuff,
 */

/**
 * Helper function; get default parameters for the XPath condition and action.
 */
function islandora_rules_base_xpath_parameters() {
  return array(
    'object' => array(
      'type' => 'islandora_object',
      'label' => t('Object'),
      'description' => t('The object containing the datastream to check.'),
    ),
    'datastream_id' => array(
      'type' => 'text',
      'label' => t('Datastream'),
      'description' => t('The identifier of the XML datastream to check.'),
    ),
    'xpath' => array(
      'type' => 'text',
      'label' => t('XPath'),
      'description' => t('An XPath to evaluate.'),
    ),
    'xpath_namespaces' => array(
      'type' => 'taxonomy_vocabulary',
      'label' => t('XPath Namespace Taxonomy'),
      'description' => t('A flat taxonomy of which the terms are namespace prefixes and the description contains the URI for the namespace.'),
    ),
  );
}


/**
 * Implements hook_rules_condition_info().
 */
function islandora_rules_condition_info() {
  $cond = array();

  $cond['islandora_rules_datastream_has_xpath'] = array(
    'label' => t('Check for an XPath match in an XML datastream'),
    'group' => t('Islandora'),
    'parameter' => islandora_rules_base_xpath_parameters(),
  );

  return $cond;
}

/**
 * Implements hook_rules_action_info().
 */
function islandora_rules_action_info() {
  $cond = array();

  $cond['islandora_rules_datastream_load_domxpath'] = array(
    'label' => t('Load a DOMXPath for a given XML.'),
    'group' => t('Islandora DOMXPath'),
    'parameter' => array(
      'datastream' => array(
        'type' => 'text',
        'label' => t('XML'),
        'description' => t('A string containing the XML to load.'),
      ),
    ),
    'provides' => array(
      'islandora_domxpath' => array(
        'type' => 'islandora_domxpath',
        'label' => t('Loaded DOMXPath instance'),
      ),
    ),
  );
  $cond['islandora_rules_datastream_load_xpath'] = array(
    'label' => t('Load a DOMXPath from a datastream.'),
    'group' => t('Islandora DOMXPath'),
    'parameter' => array(
      'datastream' => array(
        'type' => 'islandora_datastream',
        'label' => t('Datastream'),
        'description' => t('A datastream containing the XML to load.'),
      ),
    ),
    'provides' => array(
      'islandora_domxpath' => array(
        'type' => 'islandora_domxpath',
        'label' => t('Loaded DOMXPath instance'),
      ),
    ),
  );
  $cond['islandora_rules_datastream_load_namespace_vocab'] = array(
    'label' => t('Register namespaces on a DOMXPath instance.'),
    'group' => t('Islandora DOMXPath'),
    'parameter' => array(
      'value' => array(
        'type' => 'islandora_domxpath',
        'label' => t('DOMXPath instance'),
        'description' => t('The DOMXPath instance on which to register the namespaces.'),
      ),
      'xpath_namespaces' => array(
        'type' => 'taxonomy_vocabulary',
        'label' => t('XPath Namespace Taxonomy'),
        'description' => t('A flat taxonomy of which the terms are namespace prefixes and the description contains the URI for the namespace.'),
      ),
    ),
  );
  $cond['islandora_rules_datastream_query_xpath'] = array(
    'label' => t('Query nodes from DOMXPath instance.'),
    'group' => t('Islandora DOMXPath'),
    'parameter' => array(
      'xpath' => array(
        'type' => 'islandora_domxpath',
        'label' => t('DOMXPath instance'),
        'description' => t('The DOMXPath instance on which to perform the query.'),
      ),
      'query' => array(
        'type' => 'text',
        'label' => t('XPath query'),
        'description' => t('The XPath query to perform.'),
      ),
      'context_node' => array(
        'type' => 'islandora_domnode',
        'label' => t('Context Node'),
        'description' => t('If provided, the query will be performed relative to the provided node.'),
        'optional' => TRUE,
        'default value' => NULL,
        'allow null' => TRUE,
      ),
    ),
    'provides' => array(
      'nodes' => array(
        'type' => 'list<islandora_domnode>',
        'label' => t('Queried DOMNode elements'),
      ),
    ),
  );

  $cond['islandora_rules_datastream_set_xpath'] = array(
    'label' => t('Set value in elements matched by an XPath in an XML datastream'),
    'group' => t('Islandora'),
    'parameter' => islandora_rules_base_xpath_parameters() + array(
      'value' => array(
        'type' => 'text',
        'label' => t('Value'),
        'description' => t('The value to set in the XML on elements matched by the XPath.'),
      ),
    ),
  );

  return $cond;
}

/**
 * Rules Action callback; instantiate a DOMXPath with some XML.
 */
function islandora_rules_datastream_load_domxpath($string) {
  $doc = new DOMDocument();
  $doc->loadXML($string);
  $xpath = new DOMXPath($doc);
  return array('islandora_domxpath' => $xpath);
}

/**
 * Rules Action callback; load namespaces onto a DOMXPath instance.
 *
 * @param DOMXPath $xpath
 *   A DOMXPath instance.
 * @param object $xpath_vocab
 *   A loaded Drupal taxonomy vocabulary object, in which terms are understood
 *   to be namespace prefixes and descriptions are the namespace URIs.
 */
function islandora_rules_datastream_load_namespace_vocab($xpath, $xpath_vocab) {
  foreach (\Drupal::entityManager()->getStorage("taxonomy_term")->loadTree($xpath_vocab->vid, 0, 1, FALSE) as $term) {
    $xpath->registerNamespace($term->name, $term->description);
  }
}

/**
 * Rules XPath helper; grab the datastream content and build a DOMXPath.
 */
function islandora_rules_datastream_load_xpath(AbstractDatastream $datastream, $xpath_vocab = NULL) {
  $result = islandora_rules_datastream_load_domxpath($datastream->content);
  if (is_object($xpath_vocab)) {
    islandora_rules_datastream_load_namespace_vocab($result['islandora_domxpath'], $xpath_vocab);
  }
  return $result;
}

/**
 * Rules Condition callback; test that an XPath returns a non-empty result set.
 */
function islandora_rules_datastream_has_xpath(AbstractObject $object, $datastream_id, $search_xpath, $xpath_vocab) {
  $datastream = $object[$datastream_id];
  $xpath = islandora_rules_datastream_load_xpath($datastream, $xpath_vocab);
  $result = $xpath['islandora_domxpath']->query($search_xpath);
  return $result->length > 0;
}

/**
 * Rules Action callback; set the value of all matched nodes.
 */
function islandora_rules_datastream_set_xpath(AbstractObject $object, $datastream_id, $search_xpath, $xpath_vocab, $value) {
  $datastream = $object[$datastream_id];
  $xpath = islandora_rules_datastream_load_xpath($datastream, $xpath_vocab);
  $result = $xpath['islandora_domxpath']->query($search_xpath);
  foreach ($result as $node) {
    $node->nodeValue = $value;
  }
  $datastream->content = $xpath['islandora_domxpath']->document->saveXML();
}

/**
 * Implements hook_rules_data_info().
 */
function islandora_rules_data_info() {
  return array(
    'islandora_domxpath' => array(
      'label' => t('DOMXPath instance'),
      'group' => t('Islandora'),
      'property info' => array(
        'content' => array(
          'type' => 'text',
          'label' => t('XML Content'),
          'computed' => TRUE,
          'getter callback' => 'islandora_rules_get_domxpath_document_content',
          'token type' => 'string',
        ),
      ),
      'wrap' => TRUE,
    ),
    'islandora_domnode' => array(
      'label' => t('DOMNode instance'),
      'group' => t('Islandora'),
      'property info' => array(
        'node_value' => array(
          'type' => 'text',
          'label' => t('Node value'),
          'computed' => TRUE,
          'getter callback' => 'islandora_rules_property_get',
          'setter callback' => 'islandora_rules_property_set',
          'property' => 'nodeValue',
          'token type' => 'string',
        ),
        'text_content' => array(
          'type' => 'text',
          'label' => t('Text content'),
          'computed' => TRUE,
          'getter callback' => 'islandora_rules_property_get',
          'property' => 'textContent',
          'token type' => 'string',
        ),
      ),
      'wrap' => TRUE,
    ),
    'islandora_domelement' => array(
      'label' => t('DOMElement instance'),
      'group' => t('Islandora'),
      'parent' => 'islandora_domnode',
      'wrap' => TRUE,
    ),
  );
}

/**
 * Property setter helper; set a property on an object.
 *
 * In Rules, properties can contain lowercase and numeric characters. Since
 * we want to refer to "nodeValue", we have to step around the Rules constraint.
 *
 * @param object $data
 *   The object on which to set the property, described by $info['property'].
 * @param array $options
 *   An array of options... Not sure how it's used? Not touched by us, in any
 *   case. :P
 * @param string $name
 *   The name of the property to set, as used by Rules.
 * @param string $type
 *   The type of object on which the property is being set, as used by Rules.
 * @param array $info
 *   An associative array describing this property. In addition to that
 *   required by Rules/the Entity API, should contain:
 *   - property: A string indicate the actual property on the $data we wish to
 *     set.
 */
function islandora_rules_property_get($data, array $options, $name, $type, $info) {
  return $data->$info['property'];
}

/**
 * Property setter helper; set a property on an object.
 *
 * In Rules, properties can contain lowercase and numeric characters. Since
 * we want to refer to "nodeValue", we have to step around the Rules constraint.
 *
 * @param object $data
 *   The object on which to set the property, described by $info['property'].
 * @param string $name
 *   The name of the property to set, as used by Rules.
 * @param mixed $value
 *   The value to set on the property.
 * @param string $langcode
 *   A string indicating the language being set, or NULL.
 * @param string $type
 *   The type of object on which the property is being set, as used by Rules.
 * @param array $info
 *   An associative array describing this property. In addition to that
 *   required by Rules/the Entity API, should contain:
 *   - property: A string indicate the actual property on the $data we wish to
 *     set.
 */
function islandora_rules_property_set(&$data, $name, $value, $langcode, $type, $info) {
  $data->$info['property'] = $value;
}

/**
 * Rules property "get" callback; get XML contained in a DOMXPath instance.
 *
 * @param DOMXPath $xpath
 *   A DOMXPath instance.
 *
 * @return string
 *   The XML contained inside of the DOMXPath instance.
 */
function islandora_rules_get_domxpath_document_content(DOMXPath $xpath) {
  return $xpath->document->saveXML();
}

/**
 * Rules action callback; perform a query on a DOMXPath instance.
 *
 * @param DOMXPath $xpath
 *   A DOMXPath instance.
 * @param string $query
 *   An XPath query.
 * @param DOMNode $context_node
 *   An optional DOMNode. If provided, the query will be performed relative to
 *   the given node.
 *
 * @return array
 *   An array containing:
 *   - nodes: An array containing the results of the query.
 */
function islandora_rules_datastream_query_xpath(DOMXPath $xpath, $query, DOMNode $context_node = NULL) {
  return array('nodes' => iterator_to_array($xpath->query($query, $context_node)));
}

/**
 * Rules condition callback; check for the datastream on an object.
 */
function islandora_object_has_datastream(AbstractObject $object, $datastream_id) {
  return isset($object[$datastream_id]);
}
