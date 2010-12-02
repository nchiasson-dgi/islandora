<?php

// $Id$

/* File: ModsFormBuild.inc
 * Author: 
 *  Mark Roy (Creator) 
 *  Nigel Banks (Re-factoring, Documentation, Integration for IslandImagined.ca)
 * 
 * Description: 
 *  Generates a drupal form for editing an existing MODS data-streams as well as
 *  a form for ingesting an object with a MODS data-stream.
 */
module_load_include('inc', 'fedora_repository', 'plugins/FormBuilder');


//-------------------------------------------------------------------------------
// say
// Params: 
//  a - Anything
// Returns: none.
//
// Debug function prints an object surrounded by div tags using print_r,
//-------------------------------------------------------------------------------
function say($a)
{
  echo '<div>'. print_r($a, true) .'</div>';
}

/******************************************************************************** 
 * Class: FormElement
 *
 * Description: 
 *  A class that when interpreted as an array repersents a drupal form element.
 *  contains all the required information to place and build a form element.
 *******************************************************************************/
class FormElement extends ArrayObject
{
  private $includedInEditMetadataForm = TRUE;
  private $contentModelFormElementPlacement = NULL; // String in the form of name][name2 
  //-----------------------------------------------------------------------------
  // Constructor
  // Params: 
  //  contentModelFormElement - form element as described in the content model 
  //  modsFileToEditXPath - XPath for the existing MODS data-stream used to 
  //    auto-populate the form with the existing values.
  //-----------------------------------------------------------------------------
  function __construct($contentModelFormElement, $modsFileToEditXPath)
  {
    $this['#title'] = $contentModelFormElement['label'];
    $this['#required'] = ($contentModelFormElement['required'] ? 1 : 0);
    $this['#description'] = $contentModelFormElement['description'];
    $this['#type'] = $contentModelFormElement['type'];
    $this->contentModelFormElementPlacement = $contentModelFormElement['name'];
    $this->processContentModelFormElementParameters($contentModelFormElement['parameters'], $modsFileToEditXPath);
    $this->processSpecialTypes($contentModelFormElement, $modsFileToEditXPath);
  }
  //-----------------------------------------------------------------------------
  // insertIntoEditForm
  // Params: 
  //  form - Drupal form to insert self into.
  // Returns: None
  //
  // Inserts this FormElement into the drupal form. This is only used for the
  // edit form, it is not used for the ingest form.
  //-----------------------------------------------------------------------------
  function insertIntoEditForm(&$form)
  {
    $name = explode('][', $this->contentModelFormElementPlacement);
    $formElementLocation = &$form['indicator2'];
    while (isset($formElementLocation[$name[0]]) && ($partial = array_shift($name)) != NULL) {
      $formElementLocation = &$formElementLocation[$partial];
    }
    if ($this->includedInEditMetadataForm) {
      $formElementLocation[join('][', $name)] = (array)($this);
    }
  }
  //-----------------------------------------------------------------------------
  // processContentModelFormElementParameters
  // Params: 
  //  contentModelFormElementParameters - Form element parameters as specified 
  //   in the content model.
  //  modsFileToEditXPath - XPath for the existing MODS data-stream used to 
  //    auto-populate the form with the existing values.
  // Returns: None
  //  
  // Form elements in the content model have special parameter tag that is used to
  // set the default values of some form elements as well change the default 
  // behavior of the form element. This function processes these parameters and
  // carries out their defined actions.
  //-----------------------------------------------------------------------------
  private function processContentModelFormElementParameters($contentModelFormElementParameters, $modsFileToEditXPath)
  {
    foreach ($contentModelFormElementParameters as $key => $val) {
      switch ($key) {
        case '#autocomplete_path':
          $val .= '/'. $form_values['storage']['collection_pid']; // This does not make sense...
          break;
        case '#exclude_from_edit_metadata':
          $this->includedInEditMetadataForm = FALSE;
          break;
        case '#edit_metadata_xpath':
          $nodeList = $modsFileToEditXPath->evaluate($val);
          if (is_string($nodeList)) {
            $this['#default_value']=$nodeList;
          } 
          else if ($nodeList->length > 1) {
            $this['#default_value'] = array();
            foreach ($nodeList as $node) {
              $this['#default_value'][] = $node->nodeValue;
            }
          } 
          else if ($nodeList->length > 0) {
            $this['#default_value'] = $nodeList->item(0)->nodeValue;
          }
          break;
      }
      if ($key != '#sticky') {
        $this[$key]=$val;
      }
    }
  }
  //-----------------------------------------------------------------------------
  // processSpecialTypes
  // Params: 
  //  contentModelFormElementParameters - Form element parameters as specified 
  //   in the content model.
  //  modsFileToEditXPath - XPath for the existing MODS data-stream used to 
  //    auto-populate the form with the existing values.
  // Returns: None
  //  
  // Some form element are defined as 'special'. In these cases a simple XPath
  // query result isn't enough to pre-populate the form element. We handle these
  // types of form elements here.
  //-----------------------------------------------------------------------------
  private function processSpecialTypes($contentModelFormElement, $modsFileToEditXPath)
  {
    switch($this['#type']) {
      case 'people':
        $this->processPeopleType($modsFileToEditXPath);
        break;
      case 'select':
      case 'other_select':
        $this['#options'] = isset($contentModelFormElement['authoritative_list']) ? $contentModelFormElement['authoritative_list'] : array();
        break;
    }
  }
  //-----------------------------------------------------------------------------
  // processPeopleType
  // Params:
  //  modsFileToEditXPath - XPath for the existing MODS data-stream used to 
  //    auto-populate the form with the existing values.
  // Returns: None
  //  
  // Auto-populates the person type form element, if the element exists, within
  // the MODS datastream.
  //-----------------------------------------------------------------------------
  private function processPeopleType($modsFileToEditXPath)
  {
    $names = $modsFileToEditXPath->evaluate('/mods:mods/mods:name');
    if(!$names)
      return;
    $people = array();
    foreach ($names as $mname) {
      $type = $mname->getAttribute('type');
      $role = $mname->getElementsByTagName('roleTerm')->item(0)->nodeValue;              
      $nameParts = $mname->getElementsByTagName('namePart');          
      foreach ($nameParts as $namePart) {
        switch ($namePart->getAttribute('type')) { 
          case 'given': 
            $given = $namePart->nodeValue; 
            break;
          case 'family': 
            $family = $namePart->nodeValue; 
            break;
          case 'termsOfAddress': 
            $title = $namePart->nodeValue; 
            break;
          case 'date': 
            $date = $namePart->nodeValue;
            break;
          default: 
            $orgName = $namePart->nodeValue; 
            break; 
        }
      }
      $person=array('role'=>$role);
      switch ($type) {
        case 'personal':
          $person['fname']=$given;
          $person['lname']=$family;
          $person['title']=$title;
          $person['date']=$date;
          break;
        case 'organization':
          $person['organization'] = $orgName;
          break;
        case 'conference':
          $person['conference']=$orgName;
          $person['date']=$date;
          break;
      }
      $people[]=$person;
    }
    $this['#default_value'] = $people;
  }
}
/******************************************************************************** 
 * Class: ModsFormBuilder
 *
 * Description: 
 *  Handles content model callbacks to build and handle submitted forms. 
 *  Ultimately this class is resposible for ingesting/editing datastreams via
 *  its MODS edit/ingest forms.
 *******************************************************************************/
class ModsFormBuilder extends FormBuilder 
{
  static $MODS_NS = 'http://www.loc.gov/mods/v3';
  private $contentModel;
  private $fedoraItem;
  private $pid; 
  
  //-----------------------------------------------------------------------------
  // Constructor
  // Params: 
  //  pid - Object ID
  //-----------------------------------------------------------------------------
  function __construct($pid=null) 
  {
    parent::__construct();
    if ($pid !== null) {
      module_load_include('inc', 'fedora_repository', 'ContentModel');
      module_load_include('inc', 'fedora_repository', 'api/fedora_item');        
      $this->pid = $pid;
      $this->contentModel = ContentModel::loadFromObject($pid);
      $this->fedoraItem = new fedora_item($pid);
    }
  }
  //-----------------------------------------------------------------------------
  // handleEditMetadataForm
  // Params: 
  //  form_id - Form element id. Not used.
  //  form_values - Submitted form values.
  //  soap_client - Not Used
  // Returns: None
  //
  // This function receives the submitted form, converts it into a MODS.xml file
  // and overwrites the MODS data-stream with the MODS.xml file. It also transforms
  // the MODS.xml into a Dublin Core data-stream.
  //-----------------------------------------------------------------------------
  function handlEditMetadataForm(&$form_id, &$form_values, &$soap_client) 
  {
    // MODS 
    $dom = new DomDocument("1.0", "UTF-8");
    $dom->formatOutput = TRUE;
    $modsNode = $this->modsFromForm($form_values,$dom);
    $dom->appendChild($modsNode);
    if ($this->fedoraItem->modify_datastream_by_value($dom->saveXML(), 'MODS', "MODS Record", 'text/xml') !== NULL) {
      drupal_set_message(t('Successfully updated MODS datastream for object %pid', array('%pid'=>$this->pid)));
    }
    // Dublin Core
    $modsXML = $dom->saveXML($modsNode);
    $dom = new DomDocument("1.0", "UTF-8");
    $dom->formatOutput = TRUE;
    $dublinCore = $this->transformModsToDublinCore($modsXML, $dom);
    $dom->appendChild($dublinCore);
    if ($this->fedoraItem->modify_datastream_by_value($dom->saveXML(), 'DC', "Dublin Core Record", 'text/xml') !== NULL) {
      drupal_set_message(t('Successfully updated DC datastream for object %pid', array('%pid'=>$this->pid)));
    }
    // Show Created Object
    drupal_goto('/fedora/repository/'.$this->pid);
  }
  //-----------------------------------------------------------------------------
  // initEditForm
  // Params: None 
  // Returns: Prepared form.
  // 
  // Just a clean up function, returns a form object to be used as the edit form.
  //-----------------------------------------------------------------------------
  function initEditForm()
  {
    $form['#multistep'] = TRUE; // used so that it triggers a form rebuild every time.
    $form['indicator2'] = array(
      '#type' => 'fieldset',
      '#title' => t('Edit metadata'));
    $form['pid'] = array('#type'=>'hidden','#value'=>$this->pid);      
    return $form;
  }
  //-----------------------------------------------------------------------------
  // buildEditMetadataForm
  // Params: None 
  // Returns: None
  //
  // Generates a form that repersents MODS datastream, populating the forms values
  // with info stored in the MODS datastream for this object.
  //-----------------------------------------------------------------------------
  function buildEditMetadataForm()
  {
    $shouldBuildEditForm = $this->contentModel !== FALSE && $this->fedoraItem != NULL;
    if(!$shouldBuildEditForm) 
      return;

    $form = $this->initEditForm();
    $contentModelFormElements = $this->contentModel->getIngestFormElements(); // Form elements to construct
    $content = $this->fedoraItem->get_datastream_dissemination('MODS'); // Actual Mods.xml data to edit    
    $dataStreamExists = trim($content) != '';
    if(!$dataStreamExists) // Can only edit the datastream if it exists.
      return;
    
    $dom = DOMDocument::loadXML($content);
    $xpath = new DOMXPath($dom); // MODS.xml data access
    $xpath->registerNamespace("php", "http://php.net/xpath"); // Register the php: namespace (required)
    $xpath->registerNamespace("mods", $MODS_NS); // Register the mods: namespace (required)

    foreach ($contentModelFormElements as $contentModelFormElement) {
      $formElement = new FormElement($contentModelFormElement, $xpath);
      $formElement->insertIntoEditForm($form);
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#submit' => array('fedora_repository_edit_qdc_form_submit'),
      '#value' => 'Save Metadata');
    return $form;	
  }
  //-----------------------------------------------------------------------------
  // createFoxmlDocument
  // Params:
  //  form_values - Submitted form values.
  // Returns: FOXML document
  // 
  // Creates foxml document to be ingested. Creates and populates datastreams
  // based on the values submitted in the form.
  //-----------------------------------------------------------------------------
  private function createFoxmlDocument(&$form_values)
  {
    $dom = new DomDocument("1.0", "UTF-8");
    $dom->formatOutput = TRUE;
    $pid = $form_values['pid'];
    $rootElement = $dom->createElement("foxml:digitalObject");
    $attributes = array(
      'VERSION' => '1.1',
      'PID' => "$pid",
      'xmlns:foxml' => 'info:fedora/fedora-system:def/foxml#',
      'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
      'xsi:schemaLocation' => 'info:fedora/fedora-system:def/foxml# http://www.fedora.info/definitions/1/0/foxml1-1.xsd');
    foreach($attributes as $key => $val) {
      $rootElement->setAttribute($key, $val);
    }
    $dom->appendChild($rootElement);

    // Add default values as specified by FormBuilder.inc
    $form_values['dc:title'] = $form_values['mods_title'];
    $this->createStandardFedoraStuff($form_values, $dom, $rootElement);
    $this->createRelationShips($form_values, $dom, $rootElement);
    $collectionPid = $form_values['collection_pid'];
    if (($cp = CollectionPolicy::LoadFromCollection($collectionPid)) !== FALSE) {
      $collectionName = trim($cp->getName()); 
      if (trim($collectionName) != '') {
        $form_values['dc_relation'] = $collectionName;
      }
    } 
    if (!empty($form_values['ingest-file-location'])) {
      $this->createFedoraDataStreams($form_values, $dom, $rootElement);
    }
    // Done within this class.
    $this->createPolicy($collectionPid, $dom, $rootElement);
    $this->createModsStream($form_values, $dom, $rootElement);
    $this->createCollectionPolicy($form_values, $dom, $rootElement);    
    $this->createWorkflowStream($form_values, $dom, $rootElement);
    return $dom;
  }
  //-----------------------------------------------------------------------------
  // handleIngestForm
  // Params:
  //  form_values - Submitted Form Values
  // Returns: none
  // 
  // Handle submission of ingest form. Creates FOXML document representing the
  // submitted form values, and ingests the FOXML document. 
  //-----------------------------------------------------------------------------
  function handleIngestForm($form_values)
  {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'CollectionPolicy');
   
    $dom = $this->createFoxmlDocument($form_values);
    try {
      $object = Fedora_Item::ingest_from_FOXML($dom);
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
  //-----------------------------------------------------------------------------
  // createDatastream
  // Params:
  //  dom - FOXML document to be ingested
  //  attributes - FOXML attributes for the datastream
  //  versionAttributes - FOXML attributes for the version element of the datastream
  // Returns: An array containing the newly formed datastream which has not been 
  //  appended to the FOXML document, and the content node of that datastream.
  //
  // Creates a datastream with the specified attributes.
  //-----------------------------------------------------------------------------
  private function createDatastream(&$dom, $attributes, $versionAttributes)
  {
    $datastream = $dom->createElement("foxml:datastream");
    foreach($attributes as $key => $val) {
      $datastream->setAttribute($key, $val);
    }
    $version = $dom->createElement('foxml:datastreamVersion');
    foreach($versionAttributes as $key => $val) {
      $version->setAttribute($key, $val);
    }
    $datastream->appendChild($version);
    $content = $dom->createElement("foxml:xmlContent");
    $version->appendChild($content);
    return array(&$datastream, &$content);
  }
  //-----------------------------------------------------------------------------
  // createCollectionPolicy
  // Params:
  //  form_values - Submitted Form Values
  //  dom - FOXML document to be ingested
  //  rootElement - Root element of FOXML Document
  // Returns: None.
  //
  // Creates Collection policy datastream from a template stored within the 
  // Content Model, and appends it to the FOXML documents root element.
  //-----------------------------------------------------------------------------
  private function createCollectionPolicy($form_values, &$dom, &$rootElement) 
  {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $contentModelFedoraItem = new fedora_item($form_values['content_model_pid']);
    $datastreamList = $contentModelFedoraItem->get_datastreams_list_as_array();
    if (isset($datastreamList['COLLECTION_POLICY_TMPL'])) {
      $collectionPolicyTemplate = $contentModelFedoraItem->get_datastream_dissemination('COLLECTION_POLICY_TMPL');
      $collectionPolicyTemplateDom = DOMDocument::loadXML($collectionPolicyTemplate);
      $collectionPolicyTemplateRootElement = $collectionPolicyTemplateDom->getElementsByTagName('collection_policy');
      if ($collectionPolicyTemplateRootElement->length > 0 ) {
        $collectionPolicyTemplateRootElement=$collectionPolicyTemplateRootElement->item(0);
        $newNode = $dom->importNode($collectionPolicyTemplateRootElement, TRUE);
        $attributes = array('ID' => 'COLLECTION_POLICY', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
        $versionAttributes = array('ID' => 'COLLECTION_POLICY.0', 'LABEL' => 'Collection Policy', 'MIMETYPE' => 'text/xml');
        list($datastream, $content) = createDatastream($dom, $attributes, $versionAttributes);
        $content->appendChild($newNode);
        $rootElement->appendChild($datastream);
      }
    }
  }
  //-----------------------------------------------------------------------------
  // createWorkflowStream
  // Params:
  //  form_values - Submitted Form Values
  //  dom - FOXML document to be ingested
  //  rootElement - Root element of FOXML Document
  // Returns: None.
  //
  // Creates WorkFlow datastream from a template stored within the Content Model,
  // and appends it to the FOXML documents root element.
  //----------------------------------------------------------------------------- 
  private function createWorkflowStream($form_values, &$dom, &$rootElement) 
  {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $contentModelFedoraItem = new fedora_item($form_values['content_model_pid']);
    $datastreamList = $contentModelFedoraItem->get_datastreams_list_as_array();
    if (isset($datastreamList['WORKFLOW_TMPL'])) {
      $workflowTemplate = $contentModelFedoraItem->get_datastream_dissemination('WORKFLOW_TMPL');
      $workflowTemplateDom = DOMDocument::loadXML($workflowTemplate);
      $workflowTemplateRootElement = $workflowTemplateDom->getElementsByTagName('workflow');
      if ($workflowTemplateRootElement->length > 0) {
        $workflowTemplateRootElement = $workflowTemplateRootElement->item(0);
        $newNode = $dom->importNode($workflowTemplateRootElement, TRUE);
        $attributes = array('ID' => 'WORKFLOW', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
        $versionAttributes = array('ID' => 'MODS.0', 'LABEL' => 'MODS Record', 'MIMETYPE' => 'text/xml');
        list($datastream, $content) = createDatastream($dom, $attributes, $versionAttributes);
        $content->appendChild($newNode);
        $rootElement->appendChild($datastream);
      }
    }
  }
  //-----------------------------------------------------------------------------
  // createModsStream
  // Params:
  //  form_values - Submitted Form Values
  //  dom - FOXML document to be ingested
  //  rootElement - Root element of FOXML Document
  // Returns: None.
  //
  // Creates MODS datastream from the values in the submitted form, and appends
  // it to the FOXML document. Also creates a Dublin Core datastream based on 
  // the MODS datastream, and appends it to the FOXML Documents root element.
  //-----------------------------------------------------------------------------  
  private function createModsStream($form_values, &$dom, &$rootElement) 
  {
    $attributes = array('ID' => 'MODS', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
    $versionAttributes = array('ID' => 'MODS.0', 'LABEL' => 'MODS Record', 'MIMETYPE' => 'text/xml');
    list($datastream, $content) = $this->createDatastream($dom, $attributes, $versionAttributes);
    $mods = $this->modsFromForm($form_values, $dom);
    $content->appendChild($mods);
    $rootElement->appendChild($datastream);
    $this->createDublinCoreDatastream($mods, $dom, $rootElement);
  }
  //----------------------------------------------------------------------------
  // createDublinCoreDatastream
  // Params:
  //  modsNode - MODS document. Stored as a node within the dom.
  //  dom - FOXML document to be ingested
  //  rootElement - Root element in the FOXML document.
  // Returns: None.
  //
  // Transforms a MODS document into a Dublin Core document, and appends it
  // to the FOXML documents root element.
  //----------------------------------------------------------------------------
  private function createDublinCoreDatastream($modsNode, &$dom, &$rootElement)
  {
    $attributes = array('ID' => 'DC', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
    $versionAttributes = array('ID' => 'DC.0', 'LABEL' => 'Dublin Core Record', 'MIMETYPE' => 'text/xml');
    list($datastream, $content) = $this->createDatastream($dom, $attributes, $versionAttributes);
    $dublinCoreNode = $this->transformModsToDublinCore($dom->saveXML($modsNode), $dom);
    $content->appendChild($dublinCoreNode);
    $rootElement->appendChild($datastream);
  }
  //----------------------------------------------------------------------------
  // transformModsToDublinCore
  // Params:
  //  modsXML - MODS document, in the form of XML string.
  //  dom - FOXML document to be ingested, or a temp document to store the Dublin 
  //   Core document before editing an existing DC data-stream.
  // Returns: A DOMDocumentFragment that contains the transformed Dublin Core
  //  Document.
  //
  // Transforms a MODS document into a Dublin Core Document via XSLT, and return
  // the Dublin Core Document.
  //----------------------------------------------------------------------------
  private function transformModsToDublinCore(&$modsXML, &$dom)
  {
    $doc = new DOMDocument();
    $doc->loadXML($modsXML);
    $xsl = new DOMDocument();
    $xsl->load(drupal_get_path('module', 'fedora_repository').'/workflow_client/xsl/mods_to_dc.xsl');
    $proc = new XSLTProcessor();
    $proc->importStyleSheet($xsl);
    $xml = $proc->transformToXML($doc);
    $doc->loadXML($xml);
    $dublinCoreFragment = $dom->createDocumentFragment();
    $test = $doc->saveXML($doc->firstChild);
    $dublinCoreFragment->appendXML($doc->saveXML($doc->firstChild));
    return $dublinCoreFragment;
  }
  //----------------------------------------------------------------------------
  // modsFromForm
  // Params:
  //  form_values - Submitted Form Values
  //  dom - FOXML document to be ingested, or a temp document to store
  //   the MODS document before editing an existing MODS data-stream.
  // Returns: MODS document.
  //
  // Creates MODS document from the submitted form values.
  //----------------------------------------------------------------------------
  private function modsFromForm(&$form_values, &$dom)
  {
    $mods = $dom->createElement("mods:mods");
    $mods->setAttribute('version', '3.4');
    $mods->setAttribute('xmlns:xlink', "http://www.w3.org/1999/xlink");
    $mods->setAttribute('xmlns:mods', "http://www.loc.gov/mods/v3");
    $mods->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
    $mods->setAttribute('xsi:schemaLocation', "http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd");
    
    if (isset($form_values['mods_title']) && trim($form_values['mods_title']) != '') {
      $titleinfo = $dom->createElement('mods:titleInfo');
      $title  = $dom->createElement('mods:title', htmlspecialchars($form_values['mods_title']));
      $titleinfo->appendChild($title);
      $mods->appendChild($titleinfo);
    }
    
    if (isset($form_values['mods_description']) && trim($form_values['mods_description']) != '') {
     $abstract = $dom->createElement('mods:abstract', htmlspecialchars(trim($form_values['mods_description'])));
     $mods->appendChild($abstract);
    }
    
    if (isset($form_values['pid']) && trim($form_values['pid']) != '') {
      $identifier = $dom->createElement('mods:identifier', htmlspecialchars(trim(preg_replace('/\:/','\/',$form_values['pid']))));
      $identifier->setAttribute('type', 'hdl');
      $mods->appendChild($identifier);
    }
    
    if (isset($form_values['collection_pid']) && trim($form_values['collection_pid']) != '') {
      $relatedItem = $dom->createElement('mods:relatedItem');
      $relatedItem->setAttribute('type', 'isMemberOfCollection');
      $identifier = $dom->createElement('mods:identifier', htmlspecialchars(trim($form_values['collection_pid'])));
      $relatedItem->appendChild($identifier);
      $mods->appendChild($relatedItem);
    }
    
    if (isset($form_values['mods_identifier']) && trim($form_values['mods_identifier']) != '') {
      $identifier = $dom->createElement('mods:identifier', htmlspecialchars(trim($form_values['mods_identifier'])));
      $identifier->setAttribute('type', 'local');
      $mods->appendChild($identifier);
    }
    
    if (isset($form_values['mods_physicalLocation']) && trim($form_values['mods_physicalLocation']) != '') {
      $location = $dom->createElement('mods:location');
      $physLocation = $dom->createElement('mods:physicalLocation', htmlspecialchars(trim($form_values['mods_physicalLocation'])));
      $location->appendChild($physLocation);
      if (isset($form_values['mods_shelfLocator']) && trim($form_values['mods_shelfLocator']) != '') {
        $shelfLocator = $dom->createElement('mods:shelfLocator', htmlspecialchars(trim($form_values['mods_shelfLocator'])));
        $location->appendChild($shelfLocator);
      }
      $mods->appendChild($location);
    }
     
    $originInfo = $dom->createElement('mods:originInfo');
    $addOriginInfo = FALSE;
    if (isset($form_values['mods_pubinfo_place']) && trim($form_values['mods_pubinfo_place']) != '') {
      $place = $dom->createElement('mods:place');
      $placeTerm=$dom->createElement('mods:placeTerm', htmlspecialchars(trim($form_values['mods_pubinfo_place'])));
      $placeTerm->setAttribute('type', 'text');
      $place->appendChild($placeTerm);
      $originInfo->appendChild($place);
      $addOriginInfo = TRUE;
    }
    
    if (isset($form_values['mods_pubinfo_publisher']) && trim($form_values['mods_pubinfo_publisher']) != '') {
      $publisher = $dom->createElement('mods:publisher', htmlspecialchars(trim($form_values['mods_pubinfo_publisher'])));
      $originInfo->appendChild($publisher);
      $addOriginInfo = TRUE;
    }
    
    if (isset($form_values['mods_pubinfo_edition']) && trim($form_values['mods_pubinfo_edition']) != '') {
      $edition = $dom->createElement('mods:edition', htmlspecialchars(trim($form_values['mods_pubinfo_edition'])));
      $originInfo->appendChild($edition);
      $addOriginInfo = TRUE;
    }
    
    if (isset($form_values['mods_pubinfo_date']) && trim($form_values['mods_pubinfo_date']) != '' &&
      isset($form_values['mods_pubinfo_dateType']) && trim($form_values['mods_pubinfo_dateType']) != '') {
      if (in_array($form_values['mods_pubinfo_dateType'], array('issued', 'created', 'copyright', 'captured'))) {
        $date = $dom->createElement('mods:'. trim($form_values['mods_pubinfo_dateType']) .'Date', htmlspecialchars(trim($form_values['mods_pubinfo_date'])));
      } 
      else {
        //how to handle other types?  otherDate? 
        $date= $dom->createElement('mods:otherDate', htmlspecialchars(trim($form_values['mods_pubinfo_date'])));
        $date->setAttribute('type', htmlspecialchars(trim($form_values['mods_pubinfo_dateType'])));
      }
      $originInfo->appendChild($date);
      $addOriginInfo = TRUE;
    }
    
    if (isset($form_values['mods_pubinfo_journalFreq']) && trim($form_values['mods_pubinfo_journalFreq']) != '') {
      $frequency = $dom->createElement('mods:frequency', htmlspecialchars(trim($form_values['mods_pubinfo_journalFreq'])));
      $originInfo->appendChild($frequency);
      $issuance= $dom->createElement('mods:issuance', 'journal');
      $addOriginInfo = TRUE;
    } 
    else {
      $issuance= $dom->createElement('mods:issuance', 'monographic');
    }
    $originInfo->appendChild($issuance);
    
    if ($addOriginInfo) {
      $mods->appendChild($originInfo);
    }
    
    if (isset($form_values['mods_note']) && trim($form_values['mods_note']) != '') {
      $note = $dom->createElement('mods:note', htmlspecialchars(trim($form_values['mods_note'])));
      $mods->appendChild($note);
    }
    
    if (isset($form_values['mods_format']) && trim($form_values['mods_format']) != '') {
      $typeOfResource = $dom->createElement('mods:typeOfResource', htmlspecialchars($form_values['mods_format']));
      $mods->appendChild($typeOfResource);
    }    
    
    if (isset($form_values['mods_language'])  && trim($form_values['mods_language']) != '') {
      $languageList = explode(';', htmlspecialchars($form_values['mods_language']));
      foreach ($languageList as $lang) {
        $language = $dom->createElement('mods:language'); 
        $langTerm = $dom->createElement('mods:languageTerm',htmlspecialchars($lang)); 
        $langTerm->setAttribute('type','text');
        $language->appendChild($langTerm);
        $mods->appendChild($language);
      }
    }
    
    $hasSubject = FALSE;
    $subject = $dom->createElement('mods:subject');
    
    // Hierarchical Geographic Subject
    if (isset($form_values['mods_country']) && trim($form_values['mods_country']) != '') {
      $hasSubject = TRUE;
      $geographic = $dom->createElement('mods:hierarchicalGeographic');
      
      $country=$dom->createElement('mods:country', htmlspecialchars($form_values['mods_country']));
      $geographic->appendChild($country);
      
      if (isset($form_values['mods_province']) && trim($form_values['mods_province']) != '') {
        $province = $dom->createElement('mods:province', htmlspecialchars($form_values['mods_province'])); 
        $geographic->appendChild($province);
      }
      
      if (isset($form_values['mods_state']) && trim($form_values['mods_state']) != '') {
        $state = $dom->createElement('mods:state', htmlspecialchars($form_values['mods_state'])); 
        $geographic->appendChild($state);
      }
      
      if (isset($form_values['mods_city']) && trim($form_values['mods_city']) != '') {
        $city = $dom->createElement('mods:city', htmlspecialchars($form_values['mods_city'])); 
        $geographic->appendChild($city);
      }
      $subject->appendChild($geographic);
    }
    
    if (isset($form_values['mods_date']) && trim($form_values['mods_date']) != '') {
      $hasSubject = TRUE;
      $temporal = $dom->createElement('mods:temporal', htmlspecialchars($form_values['mods_date']));
      $subject->appendChild($temporal);
    }

    if (isset($form_values['mods_subjtitle']) && trim($form_values['mods_subjtitle']) != '') {
      $hasSubject = TRUE;
      $titleInfo= $dom->createElement('mods:titleInfo');
      $title = $dom->createElement('mods:title', htmlspecialchars($form_values['mods_subjtitle']));
      $titleInfo->appendChild($title);
      $subject->appendChild($titleInfo);
    }    
    
    if (isset($form_values['mods_lname']) && trim($form_values['mods_lname']) != '') {
      $hasSubject = TRUE;
      $name = $dom->createElement('mods:name');
      $name->setAttribute('type', 'personal');
      $namePart = $dom->createElement('mods:namePart', htmlspecialchars($form_values['mods_lname']));
      $namePart->setAttribute('type', 'family');
      $name->appendChild($namePart);  
      if (isset($form_values['mods_fname']) && trim($form_values['mods_fname']) != '') {
        $namePart = $dom->createElement('mods:namePart', htmlspecialchars($form_values['mods_fname']));
        $namePart->setAttribute('type', 'given');
        $name->appendChild($namePart);
      }
      $subject->appendChild($name);
    }
    
    if (isset($form_values['mods_topics']) && trim($form_values['mods_topics']) != '') {
      $hasSubject = TRUE;
      $topicList = explode(';', htmlspecialchars($form_values['mods_topics']));
      $authority = 'unknown';
      if (isset($form_values['mods_topicAuthority']) && trim($form_values['mods_topicAuthority']) != '') {
        $authority = htmlspecialchars($form_values['mods_topicAuthority']);
      }
      foreach ($topicList as $t) {
        $topic = $dom->createElement('mods:topic', $t);
        $topic->setAttribute('authority', $authority);
        $subject->appendChild($topic);
      }
    }
    if ($hasSubject) {
      $mods->appendChild($subject);
    }
    
    if (isset($form_values['mods_cc']['cc']) && $form_values['mods_cc']['cc']['cc_enable']) {
      $commercial = trim($form_values['mods_cc']['cc']['cc_commercial']);
      $modifications = trim($form_values['mods_cc']['cc']['cc_modifications']);
      $jurisdiction = trim($form_values['mods_cc']['cc']['cc_jurisdiction']);

      module_load_include('inc','islandora_form_elements','includes/creative_commons.inc');
      if (!isset(CreativeCommons::$cc_jurisdiction_vals[$jurisdiction]))
        $jurisdiction='';
      $version = CreativeCommons::$cc_versions[$jurisdiction];
      $license = 'by'. ($commercial != ''?'-'.$commercial:'') . ($modifications != ''?'-'.$modifications:'') . '/' . $version . '/'.($jurisdiction != ''?$jurisdiction.'/':'') ;
      $accessCondition = $dom->createElement('mods:accessCondition', htmlspecialchars($license));
      $accessCondition->setAttribute('type', 'Creative Commons License');
      $mods->appendChild($accessCondition);
    }
    
    if (isset($form_values['mods_rights']) && trim($form_values['mods_rights']) != '') {
      $accessCondition = $dom->createElement('mods:accessCondition', htmlspecialchars($form_values['mods_rights']));
      $accessCondition->setAttribute('type', 'restriction on access; use and reproduction');
      $mods->appendChild($accessCondition);
    }
    
    if (isset($form_values['mods_people']) &&  isset($form_values['mods_people']['people']) && is_array($form_values['mods_people']['people']) ) {
      foreach ($form_values['mods_people']['people'] as $key => $val) {
        $name = $dom->createElement('mods:name');
        
        if (isset($val['role'])) {
            $role = $dom->createElement('mods:roleTerm', htmlspecialchars(trim($val['role'])));
            $role->setAttribute('type', 'text');
            $role->setAttribute('authority', 'marcrelator');
            $name->appendChild($role);
        }
      
        if (isset($val['organization'])) {
          $name->setAttribute('type', 'organization');
          if (trim($val['organization']) != '') {
            $namePart=$dom->createElement('mods:namePart', htmlspecialchars(trim($val['organization'])));
            $name->appendChild($namePart);
            $mods->appendChild($name);
          }
        } 
        elseif (isset($val['conference'])) {
          $name->setAttribute('type', 'conference');
          if (trim($val['conference']) != '') {
            $namePart=$dom->createElement('mods:namePart', htmlspecialchars(trim($val['conference'])));
            $name->appendChild($namePart);
            $mods->appendChild($name);
          }
        } 
        else {
          $name->setAttribute('type', 'personal');
          if (trim($val['title']) != '') {
            $namePart=$dom->createElement('mods:namePart', htmlspecialchars(trim($val['title'])));
            $namePart->setAttribute('type', 'termsOfAddress');
            $name->appendChild($namePart);      
          }
          
          if (trim($val['fname']) != '' && trim($val['lname']) != '') {
            $namePart=$dom->createElement('mods:namePart', htmlspecialchars(trim($val['fname'])));
            $namePart->setAttribute('type', 'given');
            $name->appendChild($namePart);      
            $namePart=$dom->createElement('mods:namePart', htmlspecialchars(trim($val['lname'])));
            $namePart->setAttribute('type', 'family');
            $name->appendChild($namePart);            
            $mods->appendChild($name);
          }   
        }
        if (isset($val['date'])) { 
          $namePart=$dom->createElement('mods:namePart', htmlspecialchars(trim($val['date'])));
          $namePart->setAttribute('type','date');
          $name->appendChild($namePart);
        }
      } 
    }        
    return $mods;
  }
}