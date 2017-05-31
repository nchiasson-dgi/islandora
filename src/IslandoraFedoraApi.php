<?php
namespace Drupal\islandora;

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
