<?php

namespace Drupal\islandora\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Drush\Utils\StringUtils;

/**
 * Some misc validators.
 */
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
