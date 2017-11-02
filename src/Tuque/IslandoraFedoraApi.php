<?php

namespace Drupal\islandora\Tuque;

require_once __DIR__ . '/Base.php';

use FedoraApi;
use FedoraApiA;
use FedoraApiSerializer;

/**
 * Class IslandoraFedoraApi.
 *
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
