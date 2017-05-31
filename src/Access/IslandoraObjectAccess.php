<?php

namespace Drupal\islandora\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;


class IslandoraObjectAccess implements AccessInterface {

  public function access($perms, $object, AccountInterface $account) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    $object = $object === 'root' ? islandora_object_load(\Drupal::config('islandora.settings')->get('islandora_repository_pid')) : islandora_object_load($object);
    if (!$object && !islandora_describe_repository()) {
      islandora_display_repository_inaccessible_message();
      return FALSE;
    }
    return AccessResult::allowedIf(islandora_object_access($perms, $object, $account));
  }

}