<?php

namespace Drupal\islandora\Authentication\Provider;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Authentication\AuthenticationProviderFilterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the generation and validation of authentication tokens.
 *
 * These are to be used when dealing with applications such as Djatoka that do
 * not pass through credentials.
 */
class IslandoraTokenAuth implements AuthenticationProviderFilterInterface, AuthenticationProviderInterface {

  // Token lifespan(seconds): after this duration the token expires.
  // 5 minutes.
  const ISLANDORA_AUTHTOKEN_TOKEN_TIMEOUT = 300;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a token authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // XXX: The get property on the Request object is advised against usage and
    // to use the property directly on the ParameterBag.
    return $request->query->get('token') ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesToRoutedRequest(Request $request, $authenticated) {
    module_load_include('inc', 'islandora', 'includes/authtokens');
    $route = RouteMatch::createFromRequest($request)->getRouteObject();
    // Only apply to routes that explicitly defined themselves.
    if (!$route || !$route->hasOption('_islandora_token_route')) {
      return FALSE;
    }
    $object = $request->attributes->get('object');
    $datastream = $request->attributes->get('datastream');
    $token = $request->query->get('token');
    return $this->validateObjectToken($object->id, $datastream->id, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Find the user corresponding to the token.
    $uid = $this->authenticateUserByToken($request->query->get('token'));
    return $uid ? $this->entityTypeManager->getStorage('user')->load($uid) : NULL;
  }

  /**
   * Request Islandora to construct an object/datastream authentication token.
   *
   * This token can later be turned in for access to the requested object or
   * datastream.
   *
   * @param string $pid
   *   The Fedora PID to generate the token for.
   * @param string $dsid
   *   The Fedora datastream ID to generate the token for.
   * @param int $uses
   *   Defaults to 1.
   *   The number of uses the token should be used for.  There are
   *   times when this should be greater than 1: ie. Djatoka needs
   *   to make two calls. This is the number of times it can be called
   *   from different php sessions, not in the run of a program. (it is
   *   statically cached).
   *
   * @return string
   *   The generated authentication token.
   */
  public static function getObjectToken($pid, $dsid, $uses = 1) {
    $user = \Drupal::currentUser();
    $time = time();
    $token = bin2hex(Crypt::randomBytes(32));
    $connection = Database::getConnection();
    $connection->insert("islandora_authtokens")->fields(
      [
        'token' => $token,
        'uid' => $user->id(),
        'pid' => $pid,
        'dsid' => $dsid,
        'time' => $time,
        'remaining_uses' => $uses,
      ])->execute();

    return $token;
  }

  /**
   * Submit a token to islandora for authentication.
   *
   * Supply islandora with the token and the object/datastream it is for and you
   * will receive access if authentication passes. Tokens can only be redeemed
   * in a short window after their creation.
   *
   * @param string $pid
   *   The PID of the object to retrieve.
   * @param string $dsid
   *   The datastream id to retrieve.
   * @param string $token
   *   The registered token that allows access to this object.
   *
   * @return bool
   *   TRUE if a valid token, FALSE otherwise.
   */
  public static function validateObjectToken($pid, $dsid, $token) {
    static $accounts = [];

    if (!empty($accounts[$pid][$dsid][$token])) {
      return $accounts[$pid][$dsid][$token];
    }

    // Check for database token.
    $time = time();
    $connection = Database::getConnection();
    // The results will look like user objects.
    $result = $connection->select('islandora_authtokens', 'tokens')
      ->fields('tokens', ['remaining_uses'])
      ->condition('token', $token, '=')
      ->condition('pid', $pid, '=')
      ->condition('dsid', $dsid, '=')
      ->condition('time', $time, '<=')
      ->condition('time', $time - self::ISLANDORA_AUTHTOKEN_TOKEN_TIMEOUT, '>')
      ->execute()
      ->fetchAll();
    if ($result) {
      $remaining_uses = $result[0]->remaining_uses;
      $remaining_uses--;
      // Remove the authentication token so it can't be used again.
      if ($remaining_uses == 0) {
        $connection->delete("islandora_authtokens")
          ->condition('token', $token, '=')
          ->condition('pid', $pid, '=')
          ->condition('dsid', $dsid, '=')
          ->execute();
      }
      // Decrement authentication token uses.
      else {
        $connection->update("islandora_authtokens")
          ->fields(['remaining_uses' => $remaining_uses])
          ->condition('token', $token, '=')
          ->condition('pid', $pid, '=')
          ->condition('dsid', $dsid, '=')
          ->execute();
      }
      unset($result[0]->remaining_uses);
      $accounts[$pid][$dsid][$token] = TRUE;
    }
    else {
      $accounts[$pid][$dsid][$token] = FALSE;
    }
    return $accounts[$pid][$dsid][$token];
  }

  /**
   * Will remove any expired authentication tokens.
   */
  public static function removeExpiredTokens() {
    $time = time();
    $connection = Database::getConnection();
    $connection->delete("islandora_authtokens")
      ->condition('time', $time - self::ISLANDORA_AUTHTOKEN_TOKEN_TIMEOUT, '<')
      ->execute();
  }

  /**
   * Grab the user for a token.
   */
  protected function authenticateUserByToken($token) {
    $connection = Database::getConnection();
    $result = $connection->select('islandora_authtokens', 'i')
      ->fields('i', ['uid'])
      ->condition('i.token', $token)
      ->execute()
      ->fetchField();
    return $result;
  }

}
