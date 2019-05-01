<?php

namespace Drupal\islandora\Commands;

use Drush\Commands\DrushCommands;
use Drush\SiteAlias\SiteAliasManagerAwareInterface;
use Drush\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
abstract class AbstractPluginAcquisition extends DrushCommands implements SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  /**
   * Drupal's filesystem.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Archive plugin manager.
   *
   * @var Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiveManager;

  /**
   * Constructor.
   */
  public function __construct(FileSystemInterface $file_system, PluginManagerInterface $archive_manager) {
    $this->fileSystem = $file_system;
    $this->archiveManager = $archive_manager;
  }

  /**
   * Get the URI from which to acquire the plugin.
   *
   * @return string
   *   The URI from which to acquire the plugin.
   */
  abstract protected function getDownloadUri();

  /**
   * Get the path into which the plugin should be installed.
   *
   * @param string $path
   *   The containing directory where it should be installed.
   *
   * @return string
   *   The directory inside of $path into which to install the plugin.
   */
  abstract protected function getInstallDir($path);

  /**
   * Get a descriptive string for this plugin.
   *
   * @return string
   *   A descriptive string for this plugin.
   */
  abstract protected function getDescriptor();

  /**
   * Get the directory into which to unpack the plugin.
   *
   * @return string
   *   The directory into which the plugin will initially be unpacked.
   */
  protected function getUnpackDir($path) {
    return $this->getInstallDir($path);
  }

  /**
   * Get the original installation dir; possibly inside of the "unpack" dir.
   *
   * @param string $path
   *   The containing directory where it might have been installed.
   *
   * @return string
   *   The directory inside of $path into which the plugin may have been
   *   installed.
   */
  protected function getOriginalDir($path) {
    return $this->getUnpackDir($path);
  }

  /**
   * Download and install the plugin.
   */
  protected function plugin($path = NULL) {
    if (!$path) {
      $this->logger()->debug('Acquiring default installation path.');
      $path = implode('/', [
        $this->siteAliasManager()->getSelf()->root(),
        'sites',
        'all',
        'libraries',
      ]);
      $this->logger()->info('Installing to {0}.', [$path]);
    }

    $filesystem = new Filesystem();

    // Create the path if it does not exist.
    if (!is_dir($path)) {
      $this->fileSystem->mkdir($path);
      $this->logger()->notice('Directory {0} was created', [$path]);
    }

    $original_dir = $this->getOriginalDir($path);
    $install_dir = $this->getInstallDir($path);

    // Download the archive.
    if ($filepath = system_retrieve_file($this->getDownloadUri(), $path, FALSE, FILE_EXISTS_REPLACE)) {

      // Remove any existing plugin directory.
      if (is_dir($original_dir) || is_dir($install_dir)) {
        $filesystem->remove([$original_dir, $install_dir]);
        $this->logger()->notice('A existing {1} was deleted from {0}', [$path, $this->getDescriptor()]);
      }

      // Decompress the archive.
      $this->archiveManager
        ->getInstance(['filepath' => $filepath])
        ->extract($this->getUnpackDir($path));

      // Change the directory name if needed.
      if ($original_dir != $install_dir) {
        $filesystem->rename(
          $original_dir,
          $install_dir
        );
      }
    }

    if (is_dir($install_dir)) {
      $this->logger()->success('{1} has been installed in {0}', [$install_dir, $this->getDescriptor()]);
    }
    else {
      $this->logger()->error('Drush was unable to install the {1} to {0}', [$install_dir, $this->getDescriptor()]);
    }

  }

}
