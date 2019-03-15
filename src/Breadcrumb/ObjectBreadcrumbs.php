<?php

namespace Drupal\islandora\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\islandora\Controller\DefaultController;

/**
 * Provides breadcrumbs for Islandora objects.
 */
class ObjectBreadcrumbs implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $path = $route_match->getRouteObject()->getPath();
    if (strpos($path, '/islandora/object/') === 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    module_load_include('inc', 'islandora', 'includes/breadcrumb');
    $object = $route_match->getParameter('object');
    $object_breadcrumbs = islandora_get_breadcrumbs($object);
    $breadcrumb = new Breadcrumb();

    foreach ($object_breadcrumbs as $object_breadcrumb) {
      $breadcrumb->addLink($object_breadcrumb);
    }

    $breadcrumb
      ->addCacheableDependency($object)
      ->addCacheContexts([
        'route',
      ])
      ->addCacheTags([
        DefaultController::LISTING_TAG,
      ]);
    return $breadcrumb;
  }

}
