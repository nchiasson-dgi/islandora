<?php
class UNCThesis {
  private $pid =NULL;
  private $item=NULL;
  private $utilityClass = NULL;
  private $dataStreams_list=NULL;
  function UNCThesis($pid) {
    $this->pid = $pid;
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'api/dublin_core');
    module_load_include('inc', 'fedora_repository', 'plugins/Utilities');
    module_load_include('inc', 'fedora_repository', 'plugins/ShowFlexpaper');
    $this->item = new Fedora_Item($pid);
    $this->utilityClass = new Utilities($this->item);
    drupal_add_css(drupal_get_path('module', 'fedora_repository') .'/stylesheets/coccc.css');
	//$this->datastreams_list =  $this->item->get_datastreams_list_as_SimpleXML();
  }

  function showTabs() {
   
    global $base_url;

    $tabset = array();

    $tabset['my_tabset'] = array(
            '#type' => 'tabset',
    );

   // $listOfStreams = $this->getListOfStreams("audio/mpeg");

    global $user;
    $modsText = $this->item->get_datastream_dissemination("MODS");
    $modsAsHtml = $this->utilityClass->getModsAsHtml($modsText);
    $modsAsHtml = '<div class="fedora_object_container"><div class="fedora_mods_container">'.$modsAsHtml;
    $tabset['my_tabset']['1'] = array(
            '#type' => 'tabpage',
            '#title' => t('Description'),
            '#content' => $modsAsHtml,
    );
    
    $flexPaper = new ShowFlexpaper($this->pid);
    $tabset['my_tabset']['2'] = array(
            '#type' => 'tabpage',
            '#title' => t('Read Online'),
            '#content' => $flexPaper->loadFlexpaper(),
     );


    // Render the tabset.
    return '<div class="tabs">'.tabs_render($tabset).'</div>';

  }
}

?>
