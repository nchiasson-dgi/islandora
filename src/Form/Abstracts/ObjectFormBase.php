<?php

namespace Drupal\islandora\Form\Abstracts;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;

use AbstractObject;

/**
 * Helper; add and apply the cacheable dependency.
 */
abstract class ObjectFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $cache_meta = (new CacheableMetadata())
      ->addCacheableDependency($object);
    $cache_meta->applyTo($form);

    return $form;
  }

}
