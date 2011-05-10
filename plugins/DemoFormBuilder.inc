<?php
// $Id$

module_load_include('inc', 'fedora_repository', 'plugins/FormBuilder');
/*
 * Created on 19-Feb-08
 *
 *
 * implements methods from content model ingest form xml
 * builds a dc metadata form
 */
class DemoFormBuilder extends FormBuilder {
  function DemoFormBuilder() {
    module_load_include('inc', 'fedora_repository', 'plugins/FormBuilder');
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  }

  //Override this so we can specify the datastream id of FULL_SIZE  should make this easier
  function createFedoraDataStreams($form_values, &$dom, &$rootElement) {
    module_load_include('inc', 'fedora_repository', 'MimeClass');
    global $base_url;
    $mimetype = new MimeClass();
    $server = NULL;
    $file=$form_values['ingest-file-location'];

    if (!empty($file)) {
      $dformat = $mimetype->getType($file);
      $fileUrl = file_create_url($file);

      $beginIndex = strrpos($file, '/');
      $dtitle = substr($file, $beginIndex + 1);

      //		$dtitle =  substr($dtitle, 0, strpos($dtitle, "."));
      $ds1 = $dom->createElement("foxml:datastream");
      $ds1->setAttribute("ID", "FULL_SIZE");
      $ds1->setAttribute("STATE", "A");
      $ds1->setAttribute("CONTROL_GROUP", "M");
      $ds1v = $dom->createElement("foxml:datastreamVersion");
      $rootElement->appendChild($ds1);

      $ds1v->setAttribute("ID", "FULL_SIZE.0");
      $ds1v->setAttribute("MIMETYPE", "$dformat");
      $ds1v->setAttribute("LABEL", "$dtitle");
      $ds1content = $dom->createElement('foxml:contentLocation');
      $ds1content->setAttribute("REF", "$fileUrl");
      $ds1content->setAttribute("TYPE", "URL");
      $ds1->appendChild($ds1v);
      $ds1v->appendChild($ds1content);
    }
    if (!empty($_SESSION['fedora_ingest_files'])) {
      foreach ($_SESSION['fedora_ingest_files'] as $dsid => $createdFile) {
        $createdFile = strstr($createdFile, $file);
        $dformat = $mimetype->getType($createdFile);

        $fileUrl = file_create_url($createdFile);

        $beginIndex = strrpos($createdFile, '/');
        $dtitle = substr($createdFile, $beginIndex + 1);

        $ds1 = $dom->createElement("foxml:datastream");
        $ds1->setAttribute("ID", "$dsid");
        $ds1->setAttribute("STATE", "A");
        $ds1->setAttribute("CONTROL_GROUP", "M");
        $ds1v= $dom->createElement("foxml:datastreamVersion");
        $ds1v->setAttribute("ID", "$dsid.0");
        $ds1v->setAttribute("MIMETYPE", "$dformat");
        $ds1v->setAttribute("LABEL", "$dtitle");
        $ds1content = $dom->createElement('foxml:contentLocation');
        $ds1content->setAttribute("REF", "$fileUrl");
        $ds1content->setAttribute("TYPE", "URL");
        $ds1->appendChild($ds1v);
        $ds1v->appendChild($ds1content);
        $rootElement->appendChild($ds1);

        if(variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PRIVATE)
        {
          $files = variable_get('fedora_repository_uploads', array());
          $files[$fileUrl] = $dformat;
          variable_set('fedora_repository_uploads', $files);
        }
      }
    }
  }
}

