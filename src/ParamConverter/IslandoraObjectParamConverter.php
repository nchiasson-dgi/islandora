<?php

namespace Drupal\islandora\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class IslandoraObjectParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    $value = $value === 'root' ? \Drupal::config('islandora.settings')->get('islandora_repository_pid') : $value;
    return islandora_object_load($value);
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'object');
  }
}
