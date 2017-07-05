<?php
namespace Drupal\islandora\Tuque;

@include_once 'sites/all/libraries/tuque/RepositoryConnection.php';

$islandora_module_path = drupal_get_path('module', 'islandora');
@include_once "$islandora_module_path/libraries/tuque/RepositoryConnection.php";

class IslandoraRepositoryConnection extends \RepositoryConnection {
  /**
   * Constructor.
   *
   * Invokes parent, but additionally invokes an alter to allow modules to
   * effect the configuration of the connection.
   */
  public function __construct($url = NULL, $username = NULL, $password = NULL) {
    if ($url === NULL) {
      $url = static::FEDORA_URL;
    }
    parent::__construct($url, $username, $password);
    \Drupal::moduleHandler()->alter('islandora_repository_connection_construction', $this);
  }
}
