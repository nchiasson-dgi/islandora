<?php



/**
 * @file 
 * Connection Helper Class
 */
module_load_include('inc', 'ConnectionHelper', '');

/**
 * Connection Helper Class ??
 */
class ConnectionHelper {

  /**
   * Constructor
   */
  function ConnectionHelper() {
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  }

  /**
   * fixURL ??
   * @param type $url
   * @param type $_name
   * @param type $_pass
   * @return string 
   */
  function _fixURL($url, $_name, $_pass) {
    if (empty($url)) {
      $url = variable_get('fedora_soap_url', 'http://localhost:8080/fedora/services/access?wsdl');
    }
    
    $parsed_url = @parse_url($url);  //Parse the URL into an associative array (PHP built-in; 
                                     // throws a warning on failure before 5.3.3)
    $scheme = $parsed_url['scheme'];  //Grab the scheme from the URL
    $prots = array('http', 'https');  //List of supported protocols...  is this really necessary?
    
    $auth = array( //Credentials to add into the url array (same format as parse_url)
      'user' => $_name,
      'pass' => $_pass
    );

    if ($parsed_url === FALSE) {  //ERROR: Failed parsing
      drupal_set_message(t('Bad URL "!url".', array(
        '%url' => $url
      )), 'error', FALSE);
      return NULL;
    }
    elseif (!in_array($scheme, $prots)) { //ERROR: unrecognized scheme
      drupal_set_message(t('Unrecognized scheme "%scheme" in URL "%url".  Must be one in: %allowed_schemes.', array(
        '%url' => $url,
        '%scheme' => $scheme,
        '%allowed_schemes' => implode(', ', $prots)
      )), 'error', FALSE);
      return NULL;
    }
    else {  //SUCCESS!
      if (is_callable('http_build_url')) { //Nice little function provided by PECL, if available...
        return http_build_url($parsed_url, $auth);
      }
      else {  //Or what http_build_url essentially does...  Could probably be structured a little better.
        $u = array_merge($parsed_url, $auth);
        return $u['scheme'] . '://' . urlencode($u['user']) . ':' . urlencode($u['pass']) . '@' . $u['host'] . 
          array_key_exists('port', $u) ? ':' . $u['port'] : '' .
          array_key_exists('path', $u) ? $u['path'] : '' .
          array_key_exists('query', $u) ? '?' . $u['query'] : '' .
          array_key_exists('fragment', $u) ? '#' . $u['fragment'] : '';
      }
    }
  }

  /**
   * getSoapClient
   * @global type $user
   * @param type $url
   * @param type $exceptions
   * @return SoapClient 
   */
  function getSoapClient($url = NULL, $exceptions = TRUE) {
    if (empty($url)) {
      $url = variable_get('fedora_soap_url', 'http://localhost:8080/fedora/services/access?wsdl');
    }

    global $user;
    if ($user->uid == 0) {
      //anonymous user.  We will need an entry in the fedora users.xml file
      //with the appropriate entry for a username of anonymous password of anonymous
      try {
        $client = new SoapClient($this->_fixURL($url, 'anonymous', 'anonymous'), array(
            'login' => 'anonymous',
            'password' => 'anonymous',
            'exceptions' => $exceptions,
          ));
      } catch (SoapFault $e) {
        drupal_set_message(t('@e', array('@e' => check_plain($e->getMessage()))));
        return NULL;
      }
    }
    else {
      try {
        $client = new SoapClient($this->_fixURL($url, $user->name, $user->pass), array(
            'login' => $user->name,
            'password' => $user->pass,
            'exceptions' => TRUE,
          ));
      } catch (SoapFault $e) {
        drupal_set_message(t('@e', array('@e' => check_plain($e->getMessage()))));
        return NULL;
      }
    }
    return $client;
  }

}

