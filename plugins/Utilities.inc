<?php
class Utilities {
  private $pid =NULL;
  private $item=NULL;
private $dataStreams_list=NULL;
//$item is a fedora api item
  function Utilities($item) {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'api/dublin_core');
    $this->item = $item;
    drupal_add_css(drupal_get_path('module', 'fedora_repository') .'/stylesheets/coccc.css');
    $this->datastreams_list =  $this->item->get_datastreams_list_as_SimpleXML();
  }
  //may want to move this to an xslt call then we could pass custom xslt and customize views from one code base
    function getModsAsHtml($modsString) {
    $modsXml = new SimpleXMLElement($modsString);
    $modsXml->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    $title = $modsXml->xpath('/mods:mods/mods:titleInfo/mods:title');
    $author = $modsXml->xpath('//mods:role[mods:roleTerm = "creator"]/../../mods:name/mods:namePart');
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
    module_load_include('php', 'islandora_solr_search', 'Solr/Service');
    $solr_search = new IslandoraSolrResults();
    $html = '</td></tr>';
    $html .='<tr><td valign="top" align="right" class="mods_field"><strong>'.$fieldName.'</strong></td><td>';
    if($fieldName == "Place of Publication"){
        $fieldName = "Place Of Publication"; //hack for now
    }
    if(!empty($value)){
    if($fieldName == 'Author' || $fieldName == 'Topic' || $fieldName == "Publisher" || $fieldName == "Place Of Publication") {
        foreach($value as $va){
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
            $html .= '<a href = "'.$base_url.'/fedora/repository/'.$this->item->pid.'/'.$ds->ID . '/'. drupal_urlencode($label).'">'.$ds->label.'</a><br />';
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


}
?>
