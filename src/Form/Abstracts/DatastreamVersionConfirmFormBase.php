<?php

namespace Drupal\islandora\Form\Abstracts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;

use AbstractDatastream;

/**
 * Helper; add and apply the cacheable dependency.
 */
abstract class DatastreamVersionConfirmFormBase extends DatastreamConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractDatastream $datastream = NULL, $version = NULL) {
    if (isset($datastream[$version])) {
      $cache_meta = (new CacheableMetadata())
        ->addCacheableDependency($datastream[$version]);
      $cache_meta->applyTo($form);
    }

    return parent::buildForm($form, $form_state, $datastream);
  }

}
