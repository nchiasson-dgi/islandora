<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/
module_load_include('inc', 'fedora_repository', 'plugins/ModsFormBuilder');
//override these methods as we don't implement the workflow stuff
//may have to pull other stuff into this class but for now we will mimic the parents functionality minus these methods.
class CoallianceModsFormBuilder extends ModsFormBuilder {
private $cm;
  private $item;
  private $pid; 
  
 function __construct($pid=null) 
 {
   parent::__construct();
   if ($pid !== null)
   {
     module_load_include('inc', 'fedora_repository', 'ContentModel');
     module_load_include('inc', 'fedora_repository', 'api/fedora_item');
     
     $this->pid=$pid;
     $this->cm = ContentModel::loadFromObject($pid);
     $this->item = new fedora_item($pid);
   }
 }
 //override so we don't try to modify the workflow stream that doesn't exist in our object
 function handleModsForm($form_values) {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'CollectionPolicy');

    $dom = new DomDocument("1.0", "UTF-8");
    $dom->formatOutput = TRUE;
    $pid=$form_values['pid'];
    $rootElement = $dom->createElement("foxml:digitalObject");
    $rootElement->setAttribute('VERSION', '1.1');
    $rootElement->setAttribute('PID', "$pid");
    $rootElement->setAttribute('xmlns:foxml', "info:fedora/fedora-system:def/foxml#");
    $rootElement->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
    $rootElement->setAttribute('xsi:schemaLocation', "info:fedora/fedora-system:def/foxml# http://www.fedora.info/definitions/1/0/foxml1-1.xsd");
    $dom->appendChild($rootElement);

    // Create standard fedora stuff
    $form_values['dc:title'] = $form_values['mods_title'];
    $this->createStandardFedoraStuff($form_values, $dom, $rootElement);

    // Create relationships
    $this->createRelationShips($form_values, $dom, $rootElement);
    $collectionPid = $form_values['collection_pid'];

    if (($cp = CollectionPolicy::LoadFromCollection($collectionPid)) !== FALSE) {
      $collectionName =trim($cp->getName());
      if (trim($collectionName)!='') {
        $form_values['dc_relation']=$collectionName;
      }
    }
    // Create MODS
    $this->createModsStream($form_values, $dom, $rootElement);
    //$this->createCollectionPolicy($form_values, $dom, $rootElement);
    //$this->createWorkflowStream($form_values, $dom, $rootElement);

    if (!empty($form_values['ingest-file-location'])) {
      $this->createFedoraDataStreams($form_values, $dom, $rootElement);
    }
    $this->createPolicy($collectionPid, &$dom, &$rootElement);

//      header('Content-type: application/xml');
//      echo $dom->saveXML(); exit();

    try {
        $object = Fedora_Item::ingest_from_FOXML($dom);
        //for some reason, ingest_from_FOXML does not generate a JMS message
        //I just modify the workflow DS and it sends a JMS message.
        //$item = new Fedora_Item($object->pid);
       // $item->modify_datastream_by_value( $item->get_datastream_dissemination('WORKFLOW'), 'WORKFLOW', "Workflow Record", 'text/xml');

        if (!empty($object->pid)) {
          drupal_set_message(t("Item !pid created successfully.", array('!pid' => l($object->pid, 'fedora/repository/'. $object->pid))), "status");
         }
         if (!empty( $_SESSION['fedora_ingest_files'])) {
           foreach ($_SESSION['fedora_ingest_files'] as $dsid => $createdFile) {
            file_delete($createdFile);
           }
         }
         file_delete($form_values['ingest-file-location']);
    }
    catch (exception $e) {
      drupal_set_message(t('Error ingesting object: !e', array('!e' => $e->getMessage())), 'error');
      watchdog(t("Fedora_Repository"), t("Error ingesting object: !e", array('!e' => $e->getMessage())), NULL, WATCHDOG_ERROR);
      return;
    }
  }

  


//override and do nothing for now
  function createCollectionPolicy($form_values, &$dom, &$rootElement) {
    return;
  }
//override and do nothing for now
  function createWorkflowStream($form_values, &$dom, &$rootElement) {
    return;
  }
}
?>
