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

class UserWrapperCommands implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Need access to entities to test users.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Account switcher to do the switching.
   *
   * @var Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $switcher;

  /**
   * The user to which we will switch.
   *
   * Either some form of account object, or boolean FALSE.
   *
   * @var Drupal\Core\Session\AccountInterface|bool
   */
  protected $user = FALSE;

  public function __construct(AccountSwitcherInterface $account_switcher, EntityTypeManagerInterface $entity_type_manager) {
    $this->switcher = $account_switcher;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Add the option to the command.
   *
   * @hook option @islandora-user-wrap
   */
  public function userOption(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'user',
      'u',
      InputOption::VALUE_REQUIRED,
      'The Drupal user as whom to run the command.'
    );
  }

  /**
   * Ensure the user provided is valid.
   *
   * @hook validate @islandora-user-wrap
   */
  public function userExists(CommandData $commandData) {
    $input = $commandData->input();
    $user = $input->getOption('user');

    if (!isset($user)) {
      $this->logger->debug('"user" option does not appear to be set');
      return;
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    if (is_numeric($user)) {
      $this->logger->debug('"user" appears to be numeric; loading as-is');
      $this->user = $user_storage->load($user);
    }
    else {
      $this->logger->debug('"user" is non-numeric; assuming it is a name');
      $candidates = $user_storage->loadByProperties(['name' => $user]);
      if (count($candidates) > 1) {
        return new CommandError(\dt('Too many candidates for user name: @spec', [
          '@spec' => $user,
        ]));
      }
      $this->user = reset($candidates);
    }

    if (!$this->user) {
      return new CommandError(\dt('Failed to load the user: @spec', [
        '@spec' => $user,
      ]));
    }
  }

  /**
   * Perform the swap before running the command.
   *
   * @hook pre-command @islandora-user-wrap
   */
  public function switchUser(CommandData $commandData) {
    $this->logger->debug('pre-command');
    if ($this->user) {
      $this->logger->debug('switching user');
      $this->switcher->switchTo($this->user);
      $this->logger->debug('switched user');
    }
  }

  /**
   * Swap back after running the command.
   *
   * @hook post-command @islandora-user-wrap
   */
  public function unswitch($result, CommandData $commandData) {
    $this->logger->debug('post-command');
    if ($this->user) {
      $this->logger->debug('to switch back');
      $this->switcher->switchBack();
      $this->logger->debug('switched back');
    }

    return $result;
  }

}
