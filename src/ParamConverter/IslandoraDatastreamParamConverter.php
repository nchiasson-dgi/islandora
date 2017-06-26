<?php

namespace Drupal\islandora\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;


class IslandoraDatastreamParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    return islandora_datastream_load($value, $defaults['object']->id);
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'datastream');
  }
}