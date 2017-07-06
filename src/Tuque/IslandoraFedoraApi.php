<?php

namespace Drupal\islandora\Tuque;

// XXX: Class from tuque do not autoload properly which causes problems
// for deserialization.
@include_once 'sites/all/libraries/tuque/FedoraApi.php';
@include_once 'sites/all/libraries/tuque/FedoraApiSerializer.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/RepositoryConnection.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraApiSerializer.php";

use FedoraApi;
use FedoraApiA;
use FedoraApiSerializer;

/**
 * Class IslandoraFedoraApi
 * @package Drupal\islandora\Tuque
 */
class IslandoraFedoraApi extends FedoraApi {

  /**
   * Instantiate a IslandoraFedoraApi object.
   *
   * @see FedoraApi::__construct()
   */
  public function __construct(IslandoraRepositoryConnection $connection, FedoraApiSerializer $serializer = NULL) {
    if (!$serializer) {
      $serializer = new FedoraApiSerializer();
    }
    $this->a = new FedoraApiA($connection, $serializer);
    $this->m = new IslandoraFedoraApiM($connection, $serializer);
    $this->connection = $connection;
  }

}
