<?php

// $Id$

module_load_include('inc', 'fedora_repository', 'plugins/ModsFormBuilder');
class CoCCCModsFormBuilder extends ModsFormBuilder {
  static $MODS_NS = 'http://www.loc.gov/mods/v3';
  private $cm;
  private $item;
  private $pid;
  private $temp_dir;

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

  function unzip($zip) {
        $path = $this->tempdir(file_directory_path(),'zip_', 0777 );
        $this->temp_dir = $path;
        $zip->extractTo($path);
        $files = file_scan_directory($path,'.*');
        return $files;
    }

 function tempdir($dir, $prefix='', $mode=0700)
  {
    if (substr($dir, -1) != '/') $dir .= '/';

    do
    {
      $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (!mkdir($path, $mode));

    return $path;
  }

  
 function handleEditMetadataForm(&$form_id, &$form_values, &$soap_client) 
 {
    $dom = new DomDocument("1.0", "UTF-8");
    $dom->formatOutput = TRUE;
    $mods = $this->modsFromForm($form_values,$dom);
    $dom->appendChild($mods);
	//$pid=$form_values['pid'];
	//$this->item = new fedora_item($pid);
    $this->item->modify_datastream_by_value($dom->saveXML(), 'MODS', "MODS Record", 'text/xml');
    
    drupal_set_message(t('Successfully updated MODS datastream for object %pid', array('%pid'=>$this->pid)));
    drupal_goto('/fedora/repository/'.$this->pid);
 }
  
 function buildEditMetadataForm()
 {
     $form['#multistep'] = TRUE; // used so that it triggers a form rebuild every time.
      $form['indicator2'] = array(
	'#type' => 'fieldset',
	'#title' => t('Edit metadata'),
      );
      
    if ($this->cm !== FALSE && $this->item != NULL) {
      $form['pid'] = array('#type'=>'hidden','#value'=>$this->pid);
      
      $elements = $this->cm->getIngestFormElements();
      $content  = $this->item->get_datastream_dissemination('MODS');
    
      if (trim($content) != '') { 
	$dom = DOMDocument::loadXML($content);
	$xpath = new DOMXPath($dom);
	// Register the php: namespace (required)
	$xpath->registerNamespace("php", "http://php.net/xpath");

	// Register PHP functions (no restrictions)
	//$xpath->registerPHPFunctions();
	
	foreach ($elements as $element) {

	  $el = array(
	    '#title' => $element['label'],
	    '#required' => ($element['required'] ? 1 : 0),
	    '#description' => $element['description'],
	    '#type' => $element['type']
	  );

	  $includeEl = true;
	  $name = explode('][', $element['name']);
	  $elLocation = &$form['indicator2'];
	  while (isset($elLocation[$name[0]]) && ($partial = array_shift($name)) != NULL) {
	   $elLocation = &$elLocation[$partial];
	  }

	  foreach ($element['parameters'] as $key => $val) {
	    switch ($key)  {
	      case '#autocomplete_path':
		$val .= '/'. $form_values['storage']['collection_pid'];
		break;
		
	      case '#exclude_from_edit_metadata':
		$includeEl=FALSE;
		break;
		  
	      case '#edit_metadata_xpath':
		$nodeList = $xpath->evaluate($val);
// 		echo $val. ' '.$nodeList->length.' '. $nodeList. '<br/>';
		if (is_string($nodeList)) 
		{
		  $el['#default_value']=$nodeList;
		} else if ($nodeList->length > 1) 
		{
		  $el['#default_value'] = array();
		  foreach ($nodeList as $node)
		  {
		    $el['#default_value'][] = $node->nodeValue;
		  }
		} else if ($nodeList->length > 0) 
		{
		  $el['#default_value'] = $nodeList->item(0)->nodeValue;
		}
		break;
	    }
	    
	    if ($key != '#sticky') {
	      $el[$key]=$val;
	    }
	    
	  }
	  
	  if ($element['type'] == 'people')
	  {

	    $names = $xpath->evaluate('/mods:mods/mods:name');
	    $people=array();
	    foreach ($names as $mname) {
	      
	      $type = $mname->getAttribute('type');
	      $role = $mname->getElementsByTagName('roleTerm')->item(0)->nodeValue;
	      
	      $nameParts = $mname->getElementsByTagName('namePart');
	      foreach ($nameParts as $namePart)
	      {
		switch ($namePart->getAttribute('type')) { 
		  case 'given': $given = $namePart->nodeValue; break;
		  case 'family': $family = $namePart->nodeValue; break;
		  case 'termsOfAddress': $title = $namePart->nodeValue; break;
		  default: $orgName = $namePart->nodeValue; break; 
		}
	      }
	      
	      $person=array('role'=>$role);
	      switch ($type)
	      {
		case 'personal':
		  $person['fname']=$given;
		  $person['lname']=$family;
		  $person['title']=$title;
		  break;
		case 'organization':
		  $person['organization'] = $orgName;
		  break;
		case 'conference':
		  $person['conference']=$orgName;
		  break;
	      }
	      $people[]=$person;
	    }
	    
	    $el['#default_value'] = $people;
	    
	  }
	    
	  
	  if ($element['type'] == 'select' || $element['type'] == 'other_select') {
	    $el['#options']= isset($element['authoritative_list'])?$element['authoritative_list']:array();
	  }
     
	  if ($includeEl) {
	    $elLocation[join('][', $name)] = $el;
	  }
	}

    $form['submit'] = array(
        '#type' => 'submit',
        '#submit' => array('fedora_repository_edit_qdc_form_submit'),
        '#value' => 'Save Metadata'
    );
	  
	return $form;	

      }
    }
    
 }
 
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
         $zip = new ZipArchive();
        $zip_filename = $form_values['ingest-file-location'];
        
    if ($zip->open($zip_filename)!==TRUE) {
        drupal_set_message(t("Cannot open archive !file",array('!file',$zip_filename)),"error");
        return;
    }
    $files = $this->unzip($zip);
    foreach ($files as $file){
        $tnFound=FALSE;
        $sanitizedString = ereg_replace("[^A-Za-z0-9]", "", $file->basename);
        if(stristr($sanitizedString,'thumbnail') && !$tnFound){
            $sanitizedString="TN";
            $tnFound= TRUE;
        }
        $_SESSION['fedora_ingest_files']["$sanitizedString"]="$file->filename";
        //$_SESSION['fedora_ingest_files']["$dsid"] = $file . $file_suffix;
    }
   

      $this->createFedoraDataStreams($form_values, $dom, $rootElement);
    }
   

      //header('Content-type: application/xml');
      //echo $dom->saveXML(); exit();
    
    try {
        $object = Fedora_Item::ingest_from_FOXML($dom);

        //for some reason, ingest_from_FOXML does not generate a JMS message
        //I just modify the workflow DS and it sends a JMS message.  
        $item = new Fedora_Item($object->pid);
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
         $this->delete_directory($this->temp_dir);
    }
    catch (exception $e) {
      drupal_set_message(t('Error ingesting object: !e', array('!e' => $e->getMessage())), 'error');
      watchdog(t("Fedora_Repository"), t("Error ingesting object: !e", array('!e' => $e->getMessage())), NULL, WATCHDOG_ERROR);
      return;
    }
  }

//recursively delete a directory
function delete_directory($dirname) {
   if (is_dir($dirname))
      $dir_handle = opendir($dirname);
   if (!$dir_handle)
      return false;
   while($file = readdir($dir_handle)) {
      if ($file != "." && $file != "..") { 
         if (!is_dir($dirname."/".$file))
            unlink($dirname."/".$file);
         else
            $this->delete_directory($dirname.'/'.$file);
      }
   }
   closedir($dir_handle);
   rmdir($dirname);
   return true;
}
 

 
 function createFedoraDataStreams($form_values, &$dom, &$rootElement) {
    module_load_include('inc', 'fedora_repository', 'MimeClass');
    global $base_url;
    $mimetype = new MimeClass();
    $server=NULL;
    $file=$form_values['ingest-file-location'];
//if we don't want to store the zip comment this out
    if (!empty( $file)) {
      $dformat = $mimetype->getType($file);
      $fileUrl = $base_url . '/'. drupal_urlencode($file);
      $beginIndex = strrpos($fileUrl, '/');
      $dtitle = substr($fileUrl, $beginIndex + 1);
      $dtitle = urldecode($dtitle);
      $ds1 = $dom->createElement("foxml:datastream");
      $ds1->setAttribute("ID", "OBJ");
      $ds1->setAttribute("STATE", "A");
      $ds1->setAttribute("CONTROL_GROUP", "M");
      $ds1v= $dom->createElement("foxml:datastreamVersion");
      $rootElement->appendChild($ds1);

      $ds1v->setAttribute("ID", "OBJ.0");
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
        //$createdFile = strstr($createdFile, $file);
        $dformat = $mimetype->getType($createdFile);
        $fileUrl = $base_url . '/'. drupal_urlencode($createdFile);
        $beginIndex = strrpos($fileUrl, '/');
        $dtitle = substr($fileUrl, $beginIndex + 1);
        $dtitle = urldecode($dtitle);
        $dtitle = $dtitle;
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
      }
    }
  }

    
  function createModsStream($form_values, &$dom, &$rootElement) {
    
//      echo '<pre>'; var_dump($form_values);  exit();
    
    $datastream = $dom->createElement("foxml:datastream");
    $datastream->setAttribute("ID", "MODS");
    $datastream->setAttribute("STATE", "A");
    $datastream->setAttribute("CONTROL_GROUP", "X");
    $version = $dom->createElement("foxml:datastreamVersion");
    $version->setAttribute("ID", "MODS.0");
    $version->setAttribute("MIMETYPE", "text/xml");
    $version->setAttribute("LABEL", "MODS Record");
    $datastream->appendChild($version);
    $content = $dom->createElement("foxml:xmlContent");
    $version->appendChild($content);
    
    $mods = $this->modsFromForm($form_values,$dom);
    $content->appendChild($mods);
    
    $rootElement->appendChild($datastream);
  }  


  function modsFromForm(&$form_values,&$dom)
  {

     ///begin writing MODS
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

    if (isset($form_values['mods_creator']) && trim($form_values['mods_creator']) != '') {
      $name = $dom->createElement('mods:name');
      $namePart  = $dom->createElement('mods:namePart', htmlspecialchars($form_values['mods_creator']));
      $role  = $dom->createElement('mods:role');
      $roleTerm = $dom->createElement('mods:roleTerm','creator');
      $roleTerm->setAttribute('type','text');
      $role->appendChild($roleTerm);
      $name->appendChild($namePart);
      $name->appendChild($role);
      $mods->appendChild($name);
    }

    if (isset($form_values['mods_identifier']) && trim($form_values['mods_identifier']) != '') {
      $identifier = $dom->createElement('mods:identifier',htmlspecialchars($form_values['mods_identifier']));
     $identifier->setAttribute('type','local');
      $mods->appendChild($identifier);
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
    
    if (isset($form_values['mods_date_captured']) && trim($form_values['mods_date_captured']) != '') {
      $dateCaptured = $dom->createElement('mods:dateCaptured', htmlspecialchars(trim($form_values['mods_date_captured'])));
      $originInfo->appendChild($dateCaptured);
      $addOriginInfo = TRUE;
    }
    if (isset($form_values['mods_date_created']) && trim($form_values['mods_date_created']) != '') {
      $dateCaptured = $dom->createElement('mods:dateCreated', htmlspecialchars(trim($form_values['mods_date_created'])));
      $originInfo->appendChild($dateCaptured);
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

    if ($addOriginInfo) {
      $mods->appendChild($originInfo);
    }
    
    if (isset($form_values['mods_note_admin']) && trim($form_values['mods_note_admin']) != '') {
      $note = $dom->createElement('mods:note', htmlspecialchars(trim($form_values['mods_note_admin'])));
      $note->setAttribute('type','admin');
      $mods->appendChild($note);
    }
    if (isset($form_values['mods_note_venue']) && trim($form_values['mods_note_venue']) != '') {
      $note = $dom->createElement('mods:note', htmlspecialchars(trim($form_values['mods_note_venue'])));
      $note->setAttribute('type','venue');
      $mods->appendChild($note);
    }

    if (isset($form_values['mods_accessCondition']) && trim($form_values['mods_accessCondition']) != '') {
      $accessCondtion = $dom->createElement('mods:accessCondition', htmlspecialchars(trim($form_values['mods_accessCondition'])));
      $mods->appendChild($accessCondtion);
    }
        
    if (isset($form_values['mods_format']) && trim($form_values['mods_format']) != '') {
      $typeOfResource = $dom->createElement('mods:typeOfResource', htmlspecialchars($form_values['mods_format']));
      $mods->appendChild($typeOfResource);
    }

    if (isset($form_values['mods_languageOfCataloging']) && trim($form_values['mods_languageOfCataloging']) != '') {
      $recordInfo = $dom->createElement('mods:recordInfo');
      $languageOfCataloging = $dom->createElement('mods:languageOfCataloging');
        $languageTerm = $dom->createElement('mods:languageTerm', htmlspecialchars($form_values['mods_languageOfCataloging']));
        $languageTerm->setAttribute('authority','iso639-2');
        $languageOfCataloging->appendChild($languageTerm);
        $recordInfo->appendChild($languageOfCataloging);
      $mods->appendChild($recordInfo);
    }
    
    
    if (isset($form_values['mods_language'])  && trim($form_values['mods_language']) != '')
    {
      $languageList = explode(';', htmlspecialchars($form_values['mods_language']));
      foreach ($languageList as $lang)
      {
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
    if (isset($form_values['mods_geographic']) && trim($form_values['mods_geographic']) != '') {
      $hasSubject = TRUE;
      $nameList = explode(';', htmlspecialchars($form_values['mods_geographic']));
      foreach ($nameList as $n) {
        $name = $dom->createElement('mods:geographic',htmlspecialchars($n));
        $subject->appendChild($name);
      }
    }
    
    if (isset($form_values['mods_date']) && trim($form_values['mods_date']) != '') {
      $hasSubject = TRUE;
      $dateList = explode(';', htmlspecialchars($form_values['mods_date']));
      foreach ($dateList as $n) {
        $name = $dom->createElement('mods:temporal',  htmlspecialchars($n));
        $subject->appendChild($name);
      }
    }

    if (isset($form_values['mods_subjtitle']) && trim($form_values['mods_subjtitle']) != '') {
      $hasSubject = TRUE;
      $titleInfo= $dom->createElement('mods:titleInfo');
      $title = $dom->createElement('mods:title', htmlspecialchars($form_values['mods_subjtitle']));
      $titleInfo->appendChild($title);
      $subject->appendChild($titleInfo);
    }    
    
    if (isset($form_values['mods_fullname']) && trim($form_values['mods_fullname']) != '') {
      $hasSubject = TRUE;
      $nameList = explode(';', htmlspecialchars($form_values['mods_fullname']));
      foreach ($nameList as $n) {
        $name = $dom->createElement('mods:name');
        $name->setAttribute('type', 'personal');
        $namePart = $dom->createElement('mods:namePart', htmlspecialchars($n));
        $name->appendChild($namePart);
        $subject->appendChild($name);
      }
    }

    if (isset($form_values['mods_corporate_name']) && trim($form_values['mods_corporate_name']) != '') {
      $hasSubject = TRUE;
      $nameList = explode(';', htmlspecialchars($form_values['mods_corporate_name']));
      foreach ($nameList as $n) {
        $name = $dom->createElement('mods:name');
        $name->setAttribute('type', 'corporate');
        $namePart = $dom->createElement('mods:namePart', htmlspecialchars($n));
        $name->appendChild($namePart);
        $subject->appendChild($name);
      }
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
      } 
    }        
    return $mods;
  }

}
