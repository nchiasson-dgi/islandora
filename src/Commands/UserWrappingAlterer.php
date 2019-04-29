<?php

namespace Drupal\islandora\Commands;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\CommandInfoAltererInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Re-create the global --user option.
 *
 * XXX: Drush 9 dropped it; however, we require this option for various
 * commands.
 */
class UserWrappingAlterer implements CommandInfoAltererInterface {

  /**
   * The annotation we use to handle user swapping.
   */
  const ANNO = 'islandora-user-wrap';

  /**
   * The set of commands we wish to alter.
   */
  const COMMANDS = [
    'batch:process',
    'pm:enable',
    'pm:uninstall',
    'updatedb',
  ];

  protected $logger;

  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('islandora');
  }

  /**
   * {@inheritdoc}
   */
  public function alterCommandInfo(CommandInfo $commandInfo, $commandFileInstance) {
    if (!$commandInfo->hasAnnotation(static::ANNO) && in_array($commandInfo->getName(), static::COMMANDS)) {
      $this->logger->debug('Adding annotation "@annotation" to @command.', [
        '@annotation' => static::ANNO,
        '@command' => $commandInfo->getName(),
      ]);
      $commandInfo->addAnnotation(static::ANNO, 'User swapping fun.');
    }
  }

}
