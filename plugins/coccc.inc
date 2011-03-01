<?php
// $Id$
class coccc {
  private $pid =NULL;
  private $item=NULL;
private $dataStreams_list=NULL;
  function coccc($pid) {
    $this->pid = $pid;
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'api/dublin_core');
    module_load_include('php', 'islandora_solr_search', 'Solr/Service');
    $this->item = new Fedora_Item($pid);
    drupal_add_css(drupal_get_path('module', 'fedora_repository') .'/stylesheets/coccc.css');
	$this->datastreams_list =  $this->item->get_datastreams_list_as_SimpleXML();
  }
    function getModsAsHtml($modsString) {
    $modsXml = new SimpleXMLElement($modsString);
    $modsXml->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    $title = $modsXml->xpath('/mods:mods/mods:titleInfo/mods:title');
    $author = $modsXml->xpath('//mods:role[mods:roleTerm = "creator"]/../mods:namePart');
    $typeOfResource = $modsXml->xpath('//mods:typeOfResource');
    $digitalOrigin = $modsXml->xpath('//mods:physicalDescription/mods:digitalOrigin');
    $dateCreated = $modsXml->xpath('//mods:dateCreated[@keyDate="yes"]');
    $dateDigitized = $modsXml->xpath('//mods:dateCaptured');
    $rightsStatement = $modsXml->xpath('//mods:accessCondition[@type="useAndReproduction"]');
    $description = $modsXml->xpath('//mods:abstract');
    $noteAdmin = $modsXml->xpath('//mods:note[@type="admin"]');
    $subjectTopic= $modsXml->xpath('//mods:subject/mods:topic');
    $subjectCorporate = $modsXml->xpath('//mods:subject/mods:name[@type="corporate"]/mods:namePart');
    $subjectPersonal = $modsXml->xpath('//mods:subject/mods:name[@type="personal"]/mods:namePart');
    $subjectTime = $modsXml->xpath('//mods:subject/mods:temporal');
    $subjectGeo = $modsXml->xpath('//mods:subject/mods:geographic');
    $subjectForm = $modsXml->xpath('//mods:subject/mods:genre');
    $placeOfPublication = $modsXml->xpath('//mods:originInfo/mods:place/mods:placeTerm');
    $publisher = $modsXml->xpath('//mods:originInfo/mods:publisher');
    $language = $modsXml->xpath('//mods:recordInfo/mods:languageOfCataloging/mods:languageTerm[@authority="iso639-2"]');
    $series = $modsXml->xpath('//mods:relatedItem/mods:titleInfo[@type="series"]/mods:title');
    $html .= '<table align="left"><tr><td valign="top" align="right" class="fedora_mods_field" width="15%">';
    $html .='<strong>Title</strong></td><td>';
    foreach($title as $t) {
      $html .= $t.'<br />';
    }
    $html .= $this->htmlSnippet("Author",$author);
    $html .= $this->htmlSnippet("Type of Resource",$typeOfResource);
    $html .= $this->htmlSnippet("Digital Origin",$digitalOrigin);
    $html .= $this->htmlSnippet("Date Created",$dateCreated);
    $html .= $this->htmlSnippet("Date Digitized",$dateDigitized);
    $html .= $this->htmlSnippet("Rights Statement",$rightsStatement);
    $html .= $this->htmlSnippet("Description",$description);
    $html .= $this->htmlSnippet("Note",$noteAdmin);
    $html .= $this->htmlSnippet("Topic",$subjectTopic);
    $html .= $this->htmlSnippet("Corporate Name(s)",$subjectCorporate);
    $html .= $this->htmlSnippet("Subject (Personal)",$subjectPersonal);
    $html .= $this->htmlSnippet("Subject (Time)",$subjectTime);
    $html .= $this->htmlSnippet("Geography",$subjectGeo);
    $html .= $this->htmlSnippet("Form",$subjectForm);
    $html .= $this->htmlSnippet("Place of Publication",$placeOfPublication);
    $html .= $this->htmlSnippet("Publisher",$publisher);
    $html .= $this->htmlSnippet("Language",$language);
    $html .= $this->htmlSnippet("Series",$series);
    $html .='</td></tr></table></div></div><br />';
    return $html;
  }

function htmlSnippet($fieldName, $value) {
    global $base_url;
    module_load_include('inc', 'islandora_solr_search', 'IslandoraSolrResults');
    $solr_search = new IslandoraSolrResults();
    $html = '</td></tr>';
    $html .='<tr><td valign="top" align="right" class="mods_field"><strong>'.$fieldName.'</strong></td><td>';
    if($fieldName == "Place of Publication"){
        $fieldName = "Place Of Publication"; //hack for now
    }
    if(!empty($value)){
    if($fieldName == 'Author' || $fieldName == 'Topic' || $fieldName == "Publisher" || $fieldName == "Corporate Name(s)" || $fieldName == "Subject (Personal)" ||
            $fieldName == "Subject (Time)" || $fieldName == "Geography" || $fieldName == "Form" || $fieldName == "Place Of Publication" ||
            $fieldName == "Series" || $fieldName == "Language" || $fieldName == "Date Created" ||
            $fieldName == "Date Digitized") {
        foreach($value as $va){
        //$html .= '<a href = "'.$base_url.'/islandora/solr/search/'.$solr_search->escape($fieldName).drupal_urlencode(':"'.$va.'"').'">'.$va .'</a><br />';
        $html .= '<a href = "'.$base_url.'/islandora/solr/search/'.drupal_urlencode($solr_search->escape($fieldName)).drupal_urlencode(':"'.Apache_Solr_Service::escapePhrase($va).'"').'">'.$va .'</a><br />';

        }
    }   else {
    foreach( $value as $va) {
      $html .= $va .'<br />';
    }
    }
    return $html;
    } else {
      return '';
    }

  }
//finds datastreams that have a certain mimetype
  //excludes ones that contain any strings in the array $exclude
  function getListOfStreams($mimeTypeToRetrieve, $exclude=null) {
    // $list_of_streams = array();
    global $base_url;
    $html ='';
    $datastreams_list =  $this->item->get_datastreams_list_as_SimpleXML();
    if ($this->datastreams_list != FALSE) {
      foreach ($this->datastreams_list->datastreamDef as $ds) {
        if($mimeTypeToRetrieve == $ds->MIMEType ) {
          // $list_of_streams[] = $ds;
          $include = TRUE;
          if(isset($exclude)) {
            foreach($exclude as $value) {
              if(stristr($ds->ID, $value)) {
                $include = false;
              }
            }
          }
          if($include) {
            $label = $ds->label;
            if(empty($label)){
              $label = $ds->ID;
            }
            $html .= '<a href = "'.$base_url.'/fedora/repository/'.$this->pid.'/'.$ds->ID . '/'. drupal_urlencode($label).'">'.$ds->label.'</a><br />';
          }
        }
      }
    }
    //return $list_of_streams;
    return $html;
  }


//finds datastreams that have a certain mimetype
  //excludes ones that contain any strings in the array $exclude
  //returns an array of streams we are interested in
  function getListOfStreamsRaw($mimeTypeToRetrieve, $exclude=null) {
    $list_of_streams = array();
    global $base_url;
    $html ='';

    if ($this->datastreams_list != FALSE) {
      foreach ($this->datastreams_list->datastreamDef as $ds) {
        if($mimeTypeToRetrieve == $ds->MIMEType ) {
          // $list_of_streams[] = $ds;
          $include = TRUE;
          if(isset($exclude)) {
            foreach($exclude as $value) {
              if(stristr($ds->ID, $value)) {
                $include = false;
              }
            }
          }
          if($include) {
            $label = $ds->label;
            if(empty($label)){
              $label = $ds->ID;
            }
              $list_of_streams[] = $ds->ID.'/'.$label;
            }
        }
      }
    }

    return $list_of_streams;
  }

  function showTabs() {
    //module_load_include('inc', 'fedora_repository', 'plugins/tagging_form');
    module_load_include('inc', 'fedora_repository', 'plugins/ShowStreamsInFieldSets');
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $ssif = new ShowStreamsInFieldSets($this->pid);
    global $base_url;

    $tabset = array();

    $tabset['my_tabset'] = array(
            '#type' => 'tabset',
    );

   // $listOfStreams = $this->getListOfStreams("audio/mpeg");

    global $user;
    $modsText = $this->item->get_datastream_dissemination("MODS");
    $modsAsHtml = $this->getModsAsHtml($modsText);
    $modsAsHtml = '<div class="fedora_object_container"><div class="fedora_mods_container">'.$modsAsHtml;
     $html ='</div><div class="fedora_links_container">';
    $html .='Audio <br />'.$this->getListOfStreams('audio/mpeg');
    $html .='PDF <br />'.$this->getListOfStreams('application/pdf',array('Agreement'));
    $html .='</div></div><br />';
    $modsAsHtml .=$html;
    $tabset['my_tabset']['1'] = array(
            '#type' => 'tabpage',
            '#title' => t('Description'),
            '#content' => $modsAsHtml,
    );


    $tabset['my_tabset']['2'] = array(
            '#type' => 'tabpage',
           '#title' => t('Image'),
            '#content' => '<a href ="'.$base_url.'/fedora/repository/'.$this->pid.'/WEB/web.jpg"><img src = "'.$base_url.'/fedora/repository/'.$this->pid.'/PREVIEW/preview.jpg"></a>',
    );
   // $tabset['my_tabset']['3'] = array(
   //         '#type' => 'tabpage',
   //         '#title' => t('Image Viewer'),
   //         '#content' => $ssif->showImageInFlashPlayer(),
   //  );
	$rawImageList = $this->getListOfStreamsRaw('image/jpeg',array('TN','thumbnail','preview'));
  $tabset['my_tabset']['4'] = array(
            '#type' => 'tabpage',
            '#title' => t('Image Viewer'),
            '#content' => $ssif->showImageInFlashPlayer($rawImageList),
     );


    // Render the tabset.
    return '<div class="tabs">'.tabs_render($tabset).'</div>';

  }
}