<?php

namespace Drupal\islandora\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

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
    $object_breadcrumbs = islandora_get_breadcrumbs($route_match->getParameter('object'));
    $breadcrumb = new Breadcrumb();

    foreach ($object_breadcrumbs as $object_breadcrumb) {
      $breadcrumb->addLink($object_breadcrumb);
    }

    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }

}
