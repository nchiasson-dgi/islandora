<?php

namespace Drupal\islandora\Form\Abstracts;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;

use AbstractObject;

/**
 * Helper; add and apply the cacheable dependency.
 */
abstract class ObjectConfirmFormBase extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $cache_meta = (new CacheableMetadata())
      ->addCacheableDependency($object);
    $cache_meta->applyTo($form);

    return parent::buildForm($form, $form_state);
  }

}
