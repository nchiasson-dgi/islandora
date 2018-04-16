<?php

namespace Drupal\islandora\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

use Symfony\Component\Routing\Route;

/**
 * Object parameter converter class.
 */
class ObjectParamConverter implements ParamConverterInterface {

  private $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('islandora.settings');
  }

  /**
   * Object parameter converter method.
   */
  public function convert($value, $definition, $name, array $defaults) {
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    $value = $value === 'root' ? $this->config->get('islandora_repository_pid') : $value;
    $object = islandora_object_load($value);
    if ($object) {
      // Success.
      return $object;
    }
    elseif ($object === FALSE) {
      // Return for 404.
      return NULL;
    }
    else {
      // Let the access layer take care of it.
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'object');
  }

}
