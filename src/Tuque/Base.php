<?php

/**
 * @file
 * Something of a hack for Tuque autoloading...
 *
 * XXX: Tuque isn't autoloadable, so let's list everything here, and have it
 * where necessary.
 */

require_once 'sites/all/libraries/tuque/Datastream.php';
require_once 'sites/all/libraries/tuque/FedoraApi.php';
require_once 'sites/all/libraries/tuque/FedoraApiSerializer.php';
require_once 'sites/all/libraries/tuque/Object.php';
require_once 'sites/all/libraries/tuque/RepositoryConnection.php';
require_once 'sites/all/libraries/tuque/Cache.php';
require_once 'sites/all/libraries/tuque/RepositoryException.php';
require_once 'sites/all/libraries/tuque/Repository.php';
require_once 'sites/all/libraries/tuque/FedoraRelationships.php';
