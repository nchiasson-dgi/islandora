<?php

/**
 * @file
 * This file contains a class to include the Tuque php library.
 */

require_once __DIR__ . '/../src/Tuque/Base.php';

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

use Drupal\islandora\Tuque\IslandoraFedoraRepository;
use Drupal\islandora\Tuque\IslandoraSimpleCache;
use Drupal\islandora\Tuque\IslandoraFedoraApi;
use Drupal\islandora\Tuque\IslandoraRepositoryConnection;
use Drupal\user\Entity\User;

/**
 * A class to include the Tuque library.
 */
class IslandoraTuque {

  /**
   * Connection to the repository.
   *
   * @var RepositoryConnection
   */
  public $connection = NULL;

  /**
   * The Fedora API we are using.
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
   * @param mixed $user
   *   A Drupal user or NULL.
   * @param string $url
   *   The URL to the Fedora instance or NULL.
   */
  public function __construct($user = NULL, $url = NULL) {
    if (!isset($user)) {
      $user = \Drupal::currentUser();
    }

    if ($user->isAnonymous()) {
      $user_string = 'anonymous';
      $pass_string = 'anonymous';
    }
    else {
      $user_entity = User::load($user->id());
      $user_string = $user_entity->getAccountName();
      $pass_string = $user_entity->getPassword();
    }

    if (!isset($url)) {
      $url = \Drupal::config('islandora.settings')->get('islandora_base_url');
    }

    if (static::exists()) {
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
   * @return bool
   *   TRUE if the Connection class exists FALSE otherwise.
   */
  public static function exists() {
    return class_exists('RepositoryConnection');
  }

  /**
   * Displays and error for use when Tuque is not installed.
   */
  public static function getError() {
    $islandora_doc_link = \Drupal::l(t('Islandora documentation'), Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA/Islandora'));
    $tuque_link = \Drupal::l(t('Tuque Fedora API'), Url::fromUri('http://github.com/islandora/tuque'));
    $message = t('Islandora requires the @tuque_url. Please install in /sites/all/libraries/tuque before continuing. See the @islandora_url.', ['@tuque_url' => $tuque_link, '@islandora_url' => $islandora_doc_link]);
    drupal_set_message(Xss::filter($message), 'error', FALSE);
  }

}
