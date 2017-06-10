<?php

/**
 * @file
 * This file contains a class to include the Tuque php library.
 */

$islandora_module_path = drupal_get_path('module', 'islandora');

// Do this until we expose these in a module or library.
@include_once 'sites/all/libraries/tuque/Datastream.php';
@include_once 'sites/all/libraries/tuque/FedoraApi.php';
@include_once 'sites/all/libraries/tuque/FedoraApiSerializer.php';
@include_once 'sites/all/libraries/tuque/Object.php';
@include_once 'sites/all/libraries/tuque/RepositoryConnection.php';
@include_once 'sites/all/libraries/tuque/Cache.php';
@include_once 'sites/all/libraries/tuque/RepositoryException.php';
@include_once 'sites/all/libraries/tuque/Repository.php';
@include_once 'sites/all/libraries/tuque/FedoraRelationships.php';

@include_once "$islandora_module_path/libraries/tuque/Datastream.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraApi.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraApiSerializer.php";
@include_once "$islandora_module_path/libraries/tuque/Object.php";
@include_once "$islandora_module_path/libraries/tuque/RepositoryConnection.php";
@include_once "$islandora_module_path/libraries/tuque/Cache.php";
@include_once "$islandora_module_path/libraries/tuque/RepositoryException.php";
@include_once "$islandora_module_path/libraries/tuque/Repository.php";
@include_once "$islandora_module_path/libraries/tuque/FedoraRelationships.php";

class IslandoraTuque {

  /**
   * Connection to the repository
   *
   * @var RepositoryConnection
   */
  public $connection = NULL;

  /**
   * The Fedora API we are using
   *
   * @var FedoraAPI
   */
  public $api = NULL;

  /**
   * The cache we use to connect.
   *
   * @var SimpleCache
   */
  public $cache = NULL;

  /**
   * The repository object.
   *
   * @var FedoraRepository
   */
  public $repository = NULL;

  /**
   * Constructor.
   *
   * @param array $user
   *   A Drupal user.
   * @param string $url
   *   The url to the fedora instance.
   */
  public function __construct($user = NULL, $url = NULL) {
    if (!isset($user)) {
      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    }

    if ($user->id() == 0) {
      $user_string = 'anonymous';
      $pass_string = 'anonymous';
    }
    else {
      $user_string = $user->getAccountName();
      $pass_string = $user->getPassword();
    }

    if (!isset($url)) {
      $url = \Drupal::config('islandora.settings')->get('islandora_base_url');
    }

    if (self::exists()) {
      module_load_include('inc', 'islandora', 'includes/tuque_wrapper');
      $this->connection = new IslandoraRepositoryConnection($url, $user_string, $pass_string);
      $this->connection->reuseConnection = TRUE;
      $this->api = new IslandoraFedoraApi($this->connection);
      $this->cache = new IslandoraSimpleCache();
      $this->repository = new IslandoraFedoraRepository($this->api, $this->cache);
    }
  }

  /**
   * Checks if the Connection class exists.
   *
   * @returns bool
   *   TRUE if the Connection class exists FALSE otherwise.
   */
  public static function exists() {
    return class_exists('RepositoryConnection');
  }

  /**
   * Displays and error for use when Tuque is not installed.
   */
  public static function getError() {
    $islandora_doc_link = \Drupal::l(t('Islandora documentation'), \Drupal\Core\Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA/Islandora'));
    $tuque_link = \Drupal::l(t('Tuque Fedora API'), \Drupal\Core\Url::fromUri('http://github.com/islandora/tuque'));
    $message = t('Islandora requires the !tuque_url. Please install in /sites/all/libraries/tuque before continuing. See the !islandora_url.', array('!tuque_url' => $tuque_link, '!islandora_url' => $islandora_doc_link));
    drupal_set_message(\Drupal\Component\Utility\Xss::filter($message), 'error', FALSE);
  }
}
