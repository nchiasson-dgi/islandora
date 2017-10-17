<?php

/**
 * @file
 * Contains the admin form and callback functions for datastream manipulations.
 */

/**
 * Get the human readable size of the given datastream.
 *
 * @param AbstractDatastream $datastream
 *   The datastream to check.
 *
 * @return string
 *   A human readable size of the given datastream, or '-' if the size could not
 *   be determined.
 */
function islandora_datastream_get_human_readable_size(AbstractDatastream $datastream) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  $size_is_calculatable = $datastream->controlGroup == 'M' || $datastream->controlGroup == 'X';
  return $size_is_calculatable ? islandora_convert_bytes_to_human_readable($datastream->size) : '-';
}

/**
 * Get either the 'view' or 'download' url for the given datastream if possible.
 *
 * @param AbstractDatastream $datastream
 *   The datastream to generated the url to.
 * @param string $type
 *   One of download or view.
 * @param int $version
 *   (Optional) The version of the datastream to get a URL for.
 *
 * @return string
 *   either the 'view' or 'download' url for the given datastream.
 */
function islandora_datastream_get_url(AbstractDatastream $datastream, $type = 'download', $version = NULL) {
  if ($version === NULL) {
    $link = "islandora/object/{$datastream->parent->id}/datastream/{$datastream->id}/$type";
  }
  else {
    $link = "islandora/object/{$datastream->parent->id}/datastream/{$datastream->id}/version/$version/$type";
    $datastream = $datastream[$version];
  }

  if ($datastream->controlGroup == 'R') {
    return $datastream->url;
  }
  else {
    return $link;
  }
}

/**
 * Creates/returns the file URI for the content of a datastream for chunking.
 *
 * File locks are used to ensure the datastream is completely downloaded before
 * attempting to serve up chunks from the file.
 *
 * @param AbstractDatastream $datastream
 *   An AbstractDatastream representing a datastream on a Fedora object.
 *
 * @return string
 *   The URI of the file.
 *
 * @throws RepositoryException|Exception
 *   Exceptions may be thrown if the file was unable to be reliably acquired.
 */
function islandora_view_datastream_retrieve_file_uri(AbstractDatastream $datastream) {
  module_load_include('inc', 'islandora', 'includes/mimetype.utils');
  module_load_include('inc', 'islandora', 'includes/utilities');

  $extension = islandora_get_extension_for_mimetype($datastream->mimetype);
  $file_uri = 'temporary://chunk_' . $datastream->parent->id . '_' . $datastream->id . '_' . $datastream->createdDate->getTimestamp() . '.' . $extension;
  touch(\Drupal::service("file_system")->realpath($file_uri));
  $fp = fopen($file_uri, 'r+b');
  if (flock($fp, LOCK_SH)) {
    try {
      fseek($fp, 0, SEEK_END);
      if (ftell($fp) === 0 && $datastream->size > 0) {
        // Just opened at beginning of file, if beginning == EOF, need to grab
        // it.
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
          // Hypothetically, two threads could have a "shared" lock with an
          // unpopulated file, so to avoid deadlock on the "exclusive" lock,
          // drop the "shared" lock before blocking to obtain the "exclusive"
          // lock.
          flock($fp, LOCK_UN);
        }
        if (flock($fp, LOCK_EX)) {
          // Get exclusive lock, write file.
          $file = islandora_temp_file_entry($file_uri, $datastream->mimeType);
          if ($file->getSize() == $datastream->size) {
            // Populated in another thread while we were waiting for the
            // "exclusive" lock; drop lock and return.
            flock($fp, LOCK_UN);
            fclose($fp);
            return $file_uri;
          }

          try {
            $datastream->getContent($file->getFileUri());
            clearstatcache($file->getFileUri());
            $file->setSize(filesize($file->getFileUri()));
            if ($file->getSize() != $datastream->size) {
              throw new RepositoryException(t('Size of file downloaded for chunking does not match: Got @apparent bytes when expecting @actual.', [
                '@apparent' => $file->getSize(),
                '@actual' => $datastream->size,
              ]));
            }
          }
          catch (RepositoryException $e) {
            $file->delete();
            throw $e;
          }
        }
        else {
          throw new Exception(t('Failed to acquire write lock when downloading @pid/@dsid for chunking.', [
            '@pid' => $datastream->parent->id,
            '@dsid' => $datastream->id,
          ]));
        }
      }
      flock($fp, LOCK_UN);
      fclose($fp);
      return Drupal::service("file_system")->realpath($file_uri);
    }
    catch (Exception $e) {
      flock($fp, LOCK_UN);
      fclose($fp);
      throw $e;
    }
  }
  throw new Exception(t('Failed to acquire shared lock when chunking @pid/@dsid.', [
    '@pid' => $datastream->parent->id,
    '@dsid' => $datastream->id,
  ]));
}
