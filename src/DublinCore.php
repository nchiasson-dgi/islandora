<?php

namespace Drupal\islandora;

use DomDocument;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;

/**
 * Class DublinCore.
 *
 * @package Drupal\islandora
 */
class DublinCore {

  public $dc = [
    'dc:title' => [],
    'dc:creator' => [],
    'dc:subject' => [],
    'dc:description' => [],
    'dc:publisher' => [],
    'dc:contributor' => [],
    'dc:date' => [],
    'dc:type' => [],
    'dc:format' => [],
    'dc:identifier' => [],
    'dc:source' => [],
    'dc:language' => [],
    'dc:relation' => [],
    'dc:coverage' => [],
    'dc:rights' => [],
  ];
  public $owner;

  /**
   * Constructor.
   *
   * @param string $dc_xml
   *   The Dublin Core XML.
   */
  public function __construct($dc_xml = NULL) {
    if (!empty($dc_xml)) {
      $this->dc = self::importFromXmlString($dc_xml);
    }
  }

  /**
   * Add an element.
   *
   * @param string $element_name
   *   The name of the element to add.
   * @param string $value
   *   The value of the element to add.
   */
  public function addElement($element_name, $value) {
    if (is_string($value) && is_array($this->dc[$element_name])) {
      $this->dc[$element_name][] = $value;
    }
  }

  /**
   * Replace the given DC element with the given values.
   *
   * @param string $element_name
   *   The name of the elements to set.
   * @param mixed $values
   *   The values of the set the elements too.
   */
  public function setElement($element_name, $values) {
    if (is_array($values)) {
      $this->dc[$element_name] = $values;
    }
    elseif (is_string($values)) {
      $this->dc[$element_name] = [$values];
    }
  }

  /**
   * Serialize this object as an XML string.
   *
   * @return string
   *   The serialized XML.
   */
  public function asXml() {
    $dc_xml = new DomDocument();
    $oai_dc = $dc_xml->createElementNS('http://www.openarchives.org/OAI/2.0/oai_dc/', 'oai_dc:dc');
    $oai_dc->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
    foreach ($this->dc as $dc_element => $values) {
      if (is_array($values) && !empty($values)) {
        foreach ($values as $value) {
          $new_item = $dc_xml->createElement($dc_element, $value);
          $oai_dc->appendChild($new_item);
        }
      }
      else {
        $new_item = $dc_xml->createElement($dc_element);
        $oai_dc->appendChild($new_item);
      }
    }
    $dc_xml->appendChild($oai_dc);
    return $dc_xml->saveXML();
  }

  /**
   * Serializes this object as an array.
   *
   * @return array
   *   The serialized object.
   */
  public function asArray() {
    $dc_array = [];
    foreach ($this as $element) {
      if (!empty($element)) {
        foreach ($element as $field => $values) {
          // Split value if the result value is an array.
          if (is_array($values)) {
            $value = '';
            $i = 0;
            foreach ($values as $piece) {
              if (!empty($piece)) {
                if ($i++) {
                  $value .= ", ";
                }
                $value .= $piece;
              }
            }
          }
          else {
            $value = $values;
          }
          $dc_label = explode(':', $field);
          $element_label = Unicode::ucfirst($dc_label[1]);
          $i18n_object_id = Unicode::strtolower($element_label);
          $dc_array[$field]['label'] = function_exists('i18n_string') ?
            i18n_string("islandora:dc:{$i18n_object_id}:label", $element_label) :
            $element_label;
          $dc_array[$field]['value'] = Xss::filter($value);
          $dc_array[$field]['class'] = Unicode::strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $field));
          $dc_array[$field]['dcterms'] = preg_replace('/^dc/', 'dcterms', $field);
        }
      }
    }
    return $dc_array;
  }

  /**
   * Creates a new instance of the class by parsing dc_xml.
   *
   * @param string $dc_xml
   *   Dublin Core XML.
   *
   * @return DublinCore
   *   The instantiated object.
   */
  public static function importFromXmlString($dc_xml) {
    $dc_doc = new DOMDocument();
    if (!empty($dc_xml) && $dc_doc->loadXML($dc_xml)) {
      $oai_dc = $dc_doc->getElementsByTagNameNS('http://purl.org/dc/elements/1.1/', '*');
      $new_dc = new DublinCore();
      foreach ($oai_dc as $child) {
        if (isset($new_dc->dc[$child->nodeName])) {
          array_push($new_dc->dc[$child->nodeName], $child->nodeValue);
        }
      }
      return $new_dc;
    }
    return NULL;
  }

}
