<?php
//we have extended form builder so we can use the default dc stuff from FormBuilder but if we update we can do it here.
module_load_include('inc','fedora_repository','plugins/FormBuilder');
class OfficeDocs extends FormBuilder {

  function  __construct($pid = '') {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    if (!empty($pid)) {
      
      $this->pid = $pid;
      $this->item = new Fedora_Item($pid);
    }
  }

 /* public function buildDrupalForm($form = array()) {

    // We don't need to add anything beyond the standard Darwin Core form so just pass this through
    // If we wanted to we could add other fields.
    module_load_include('inc', 'fedora_repository', 'plugins/DarwinCore');
    
    $dwc = new DarwinCore($this->item);
    return $dwc->buildDrupalForm($form);
  }*/

 /* public function buildEditMetadataForm($form = array()) {
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#weight' => 10,
      '#value' => 'Update'
    );
    $form['pid'] = array(
      '#type' => 'hidden',
      '#value' => $this->pid,
    );
    $form['dsid'] = array(
      '#type' => 'hidden',
      '#value' => "DARWIN_CORE",
    );
    
    return $this->buildDrupalForm($form);
  }*/

 /* public function handleEditMetadataForm($form_id, $form_values) {
   
    
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'plugins/DarwinCore');
    module_load_include('inc', 'fedora_repository', 'mimetype');
    global $user;
    $mimetype = new mimetype();
    $dwc = new DarwinCore($this->item);
    $dwc->handleForm($form_values);
    $this->item->purge_datastream('DARWIN_CORE');
    $this->item->add_datastream_from_string($dwc->darwinCoreXML, 'DARWIN_CORE',
            'Darwin Core Metadata', 'text/xml', 'X');
    return TRUE;
  }*/

  public function handleIngestForm($form_values) {
    $this->handleQDCForm($form_values);//builds foxml and ingests object
    $this->convertAndCreateTN($parameterArray, $dsid, $file, $file_ext);//calls the ruleengine to convert the document and create thumbnail
  }


  function convertAndCreateTN($parameterArray, $dsid, $file, $file_ext) {
  //need error checking here and in handleQDCForm
    global $user;
            $xmlString = 'requestXML=<?xml version="1.0"?><submission><repository><username>' . $user->name .
            '</username><password>' . $user->pass . '</password><host>' . variable_get('fedora_base_url', 'http://localhost:8080/fedora') .
            '</host></repository><pids><pid>' . $form_values['pid'] . '</pid></pids><dsid>' . $form_values['version'] . '</dsid><collection>/opt/ruleengine/pdfconverter/</collection></submission>';
            //path to ruleengine framework
            //may want to promote this to the db at some point
            $urlFile = drupal_get_path('module', 'scholar') . '/ruleengine_url.txt';//using same file as scholar for now
            $url = file_get_contents($urlFile);
            $returnValue = do_curl($url,1,1,$xmlString);//$objectHelper->doCurl($url, 1, 1, $xmlString);
            //$test = $this->parseReturnValue($returnValue); //did add datastream succeed.
            

            drupal_set_message(t($returnValue));

        return true;

  }
}