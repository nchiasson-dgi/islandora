<?php

namespace Drupal\islandora\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Drush\Utils\StringUtils;

class ValidatorCommands implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Ensure the given options are provided.
   *
   * @hook validate @islandora-require-option
   */
  public function optionExists(CommandData $commandData) {
    $names = StringUtils::csvToArray($commandData->annotationData()->get('islandora-require-option'));

    $options = $commandData->input()->getOptions();

    $missing = [];
    foreach ($names as $name) {
      if (!isset($options[$name])) {
        $missing[] = $name;
      }
    }

    if ($missing) {
      return new CommandError(\dt('Missing options: !options', [
        '!options' => implode(',', $missing),
      ]));
    }
  }

}
