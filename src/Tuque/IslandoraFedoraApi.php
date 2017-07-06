<?php
namespace Drupal\islandora\Tuque;

@include_once 'sites/all/libraries/tuque/FedoraApi.php';
@include_once 'sites/all/libraries/tuque/FedoraApiSerializer.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/RepositoryConnection.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraApiSerializer.php";

class IslandoraFedoraApi extends \FedoraApi {

  /**
   * Instantiate a IslandoraFedoraApi object.
   *
   * @see FedoraApi::__construct()
   */
  public function __construct(\Drupal\islandora\Tuque\IslandoraRepositoryConnection $connection, \FedoraApiSerializer $serializer = NULL) {
    if (!$serializer) {
      $serializer = new \FedoraApiSerializer();
    }
    $this->a = new \FedoraApiA($connection, $serializer);
    $this->m = new \Drupal\islandora\Tuque\IslandoraFedoraApiM($connection, $serializer);
    $this->connection = $connection;
  }
}
