<?php

namespace Drupal\islandora\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use AbstractObject;
use AbstractDatastream;

/**
 * Access checking for datastreams on objects within Islandora.
 */
class IslandoraDatastreamAccess implements AccessInterface {

  /**
   * Whether the user has access to a datastream on an object.
   *
   * @param string|array $perms
   *   A singular permission or an array of permissions to be evalulated.
   * @param \AbstractObject $object
   *   A loaded Fedora object.
   * @param \AbstractDatastream $datastream
   *   The loaded datastream being accessed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User being validated against.
   * @param string $islandora_access_conjunction
   *   If an array of permissions is specified this will dictate how it's
   *   evaluated. To maintain 7's behavior these are ORed together by default
   *   but can be overridden on a per route basis.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   Whether the user has access in AccessResult object form.
   */
  public function access($perms, AbstractObject $object, AbstractDatastream $datastream, AccountInterface $account, $islandora_access_conjunction = 'OR') {
    module_load_include('inc', 'islandora', 'includes/utilities');
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    if (!$datastream && !islandora_describe_repository()) {
      islandora_display_repository_inaccessible_message();
      return AccessResult::forbidden();
    }
    if (is_array($perms)) {
      $result = AccessResult::neutral();
      foreach ($perms as $perm) {
        $result = $islandora_access_conjunction == 'AND' ? $result->andIf(AccessResult::allowedIf(islandora_datastream_access($perm, $datastream, $account))) : $result->orIf(AccessResult::allowedIf(islandora_datastream_access($perm, $datastream, $account)));
      }
      return $result;
    }
    else {
      return AccessResult::allowedIf(islandora_datastream_access($perms, $datastream, $account));
    }
  }

}
