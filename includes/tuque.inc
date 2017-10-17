<?php

/**
 * @file
 * This file contains a class to include the Tuque php library.
 */

require_once __DIR__ . '/../src/Tuque/Base.php';

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
   * @param \Drupal\user\Entity\User $user
   *   A Drupal user.
   * @param string $url
   *   The url to the fedora instance.
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
      $user_entity = \Drupal\user\Entity\User::load($user->id());
      $user_string = $user_entity->getAccountName();
      $pass_string = $user_entity->getPassword();
    }

    if (!isset($url)) {
      $url = \Drupal::config('islandora.settings')->get('islandora_base_url');
    }

    if (static::exists()) {
      module_load_include('inc', 'islandora', 'includes/tuque_wrapper');
      $this->connection = new \Drupal\islandora\Tuque\IslandoraRepositoryConnection($url, $user_string, $pass_string);
      $this->connection->reuseConnection = TRUE;
      $this->api = new \Drupal\islandora\Tuque\IslandoraFedoraApi($this->connection);
      $this->cache = new \Drupal\islandora\Tuque\IslandoraSimpleCache();
      $this->repository = new \Drupal\islandora\Tuque\IslandoraFedoraRepository($this->api, $this->cache);
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
    $islandora_doc_link = \Drupal::l(t('Islandora documentation'), \Drupal\Core\Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA/Islandora'));
    $tuque_link = \Drupal::l(t('Tuque Fedora API'), \Drupal\Core\Url::fromUri('http://github.com/islandora/tuque'));
    $message = t('Islandora requires the @tuque_url. Please install in /sites/all/libraries/tuque before continuing. See the @islandora_url.', ['@tuque_url' => $tuque_link, '@islandora_url' => $islandora_doc_link]);
    drupal_set_message(\Drupal\Component\Utility\Xss::filter($message), 'error', FALSE);
  }

}
