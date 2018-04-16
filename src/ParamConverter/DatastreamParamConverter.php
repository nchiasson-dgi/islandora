<?php

namespace Drupal\islandora\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Datastream parameter converter class.
 */
class DatastreamParamConverter implements ParamConverterInterface {

  /**
   * Datastream parameter converter method.
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($defaults['object'] === FALSE) {
      // Return for 404.
      return NULL;
    }
    elseif ($defaults['object'] === NULL) {
      // Let the access layer take care of it.
      return FALSE;
    }
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    return islandora_datastream_load($value, $defaults['object']->id);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'datastream');
  }

}
