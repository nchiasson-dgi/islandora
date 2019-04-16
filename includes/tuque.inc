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
use Drupal\islandora\Form\RepositoryAdmin;
use Drupal\user\Entity\User;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * A class to include the Tuque library.
 *
 * @codingStandardsIgnoreStart
 * XXX: Poorly implemented sniff indicates class should begin with
 * "Islandora.info" instead of just "Islandora"... ideally, this should move
 * somewhere under the Drupal\islandora\Tuque namespace at somepoint...
 * Possibly as a service such that Dependency Injection can happen as
 * necessary.
 */
class IslandoraTuque implements RefinableCacheableDependencyInterface {
// @codingStandardsIgnoreEnd
  use RefinableCacheableDependencyTrait;

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
    else {
      // A user was explicitly passed... let's avoid caching.
      $this->mergeCacheMaxAge(0);
    }

    if ($user->isAnonymous()) {
      $user_string = 'anonymous';
      $pass_string = 'anonymous';

      $this->addCacheContexts(['user.roles:anonymous']);
    }
    else {
      $user_entity = User::load($user->id());

      $this->addCacheableDependency($user_entity);

      $user_string = $user_entity->getAccountName();
      $pass_string = $user_entity->getPassword();
    }

    if (!isset($url)) {
      $url = RepositoryAdmin::stateGet('islandora_base_url');
    }
    else {
      // A url was explicitly passed... let's avoid caching.
      $this->mergeCacheMaxAge(0);
    }

    if (static::exists()) {
      module_load_include('inc', 'islandora', 'includes/tuque_wrapper');
      $this->connection = new IslandoraRepositoryConnection($url, $user_string, $pass_string);
      $this->connection->addCacheableDependency($this);
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
