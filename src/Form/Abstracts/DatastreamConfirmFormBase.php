<?php

namespace Drupal\islandora\Form\Abstracts;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;

use AbstractDatastream;

/**
 * Helper; add and apply the cacheable dependency.
 */
abstract class DatastreamConfirmFormBase extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    $cache_meta = (new CacheableMetadata())
      ->addCacheableDependency($datastream);
    $cache_meta->applyTo($form);

    return parent::buildForm($form, $form_state);
  }

}
