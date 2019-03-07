<?php

namespace Drupal\islandora\Form\Abstracts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration for the Islandora module.
 */
abstract class ModuleHandlerAdminForm extends ConfigFormBase {

  protected $moduleHandler;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cache_meta = new CacheableMetadata();
    foreach ($this->getEditableConfigNames() as $name) {
      $cache_meta->addCacheableDependency($this->config($name));
    }
    $cache_meta->applyTo($form);

    return parent::buildForm($form, $form_state);
  }

}
