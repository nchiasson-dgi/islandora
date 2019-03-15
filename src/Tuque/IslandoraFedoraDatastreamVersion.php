<?php

namespace Drupal\islandora\Tuque;

require_once __DIR__ . '/Base.php';

use FedoraDatastream;
use FedoraObject;
use FedoraRepository;
use FedoraDatastreamVersion;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Class IslandoraFedoraDatastreamVersion.
 *
 * @package Drupal\islandora\Tuque
 */
class IslandoraFedoraDatastreamVersion extends FedoraDatastreamVersion implements RefinableCacheableDependencyInterface {
  use RefinableCacheableDependencyTrait;

  protected $fedoraRelsIntClass = IslandoraFedoraRelsInt::class;
  protected $fedoraDatastreamVersionClass = IslandoraFedoraDatastreamVersion::class;

  /**
   * {@inheritdoc}
   */
  public function __construct($id, array $datastream_info, FedoraDatastream $datastream, FedoraObject $object, FedoraRepository $repository) {
    parent::__construct($id, $datastream_info, $datastream, $object, $repository);

    $this->addCacheableDependency($datastream);
    $this->addCacheableDependency($object);
    $this->addCacheableDependency($repository);
  }

}
