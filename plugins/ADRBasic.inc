<?php

// $Id$

/**
 * @file
 *
 * Basic viewer for Colorado Alliance style Fedora Objects.
 */

/**
 * A viewer for Fedora Objects
 */
class ADRBasic {

  /**
   * The PID of the object to be viewed/managed.
   * 
   * @var string
   */
  protected $pid;
  /**
   * The object the user will be viewing
   *
   * @var Fedora_Item
   */
  protected $item;
  /**
   * If the user has permission this will be the two tabs Overview and Manage, if the user doesn't have permission to modify
   * the object only the Overview tab will be presented.
   * 
   * @var array
   */
  protected $tabs;

  /**
   * Create a view for describing/managing an object.
   * 
   * @param string $pid
   */
  public function __construct($pid) {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $this->pid = $pid;
    $this->item = new Fedora_Item($this->pid);
    $this->tabs['adrbasic'] = array(
      '#type' => 'tabset',
    );
  }

  /**
   * Determines if the user can view the manage tab, this is based on drupal/fedora permissions.
   *
   * @return boolean
   */
  private function canManageObject() {
    return TRUE;
  }

  private function renderManageTab() {
    
  }

  // for the file viewer maybe some sorta accordian thing where the description "hides"
  private function renderOverviewTab() {
    $description = $this->renderDescription();
    $viewer = $this->renderDatastreamViewer($this->pid, $dsid);
    $description = "<h3><a href='#'>Description</a></h3><div>$description</div>";
    $viewer = "<h3><a href='#'>Viewer</a></h3><div>$viewer</div>";
    $output = "<div class='accordian'>$description$viewer</div>";
    return $output;
  }

  private function renderDescription() {
    $xml = new SimpleXMLElement($this->item->get_datastream_dissemination("MODS"));
    $xml->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    $title = $xml->xpath('/mods:mods/mods:titleInfo/mods:title');
    $author = $xml->xpath('//mods:role[mods:roleTerm = "creator"]/../../mods:name/mods:namePart');
    $type_of_resource = $xml->xpath('//mods:typeOfResource');
    $digital_origin = $xml->xpath('//mods:physicalDescription/mods:digitalOrigin');
    $date_created = $xml->xpath('//mods:dateCreated[@keyDate="yes"]');
    $date_digitized = $xml->xpath('//mods:dateCaptured');
    $rights_statement = $xml->xpath('//mods:accessCondition[@type="useAndReproduction"]');
    $description = $xml->xpath('//mods:abstract');
    $note_admin = $xml->xpath('//mods:note[@type="admin"]');
    $subject_topic = $xml->xpath('//mods:subject/mods:topic');
    $subject_corporate = $xml->xpath('//mods:subject/mods:name[@type="corporate"]/mods:namePart');
    $subject_personal = $xml->xpath('//mods:subject/mods:name[@type="personal"]/mods:namePart');
    $subject_time = $xml->xpath('//mods:subject/mods:temporal');
    $subject_geographic = $xml->xpath('//mods:subject/mods:geographic');
    $subject_form = $xml->xpath('//mods:subject/mods:genre');
    $place_of_publication = $xml->xpath('//mods:originInfo/mods:place/mods:placeTerm');
    $publisher = $xml->xpath('//mods:originInfo/mods:publisher');
    $language = $xml->xpath('//mods:recordInfo/mods:languageOfCataloging/mods:languageTerm[@authority="iso639-2"]');
    $series = $xml->xpath('//mods:relatedItem/mods:titleInfo[@type="series"]/mods:title');
    $html .= '<table align="left"><tr><td valign="top" align="right" class="fedora_mods_field" width="15%">';
    $html .='<strong>Title</strong></td><td>';
    foreach ($title as $t) {
      $html .= $t . '<br />';
    }
    $html .= $this->htmlSnippet("Author", $author);
    $html .= $this->htmlSnippet("Type of Resource", $type_of_resource);
    $html .= $this->htmlSnippet("Digital Origin", $digital_origin);
    $html .= $this->htmlSnippet("Date Created", $date_created);
    $html .= $this->htmlSnippet("Date Digitized", $date_digitized);
    $html .= $this->htmlSnippet("Rights Statement", $rights_statement);
    $html .= $this->htmlSnippet("Description", $description);
    $html .= $this->htmlSnippet("Note", $note_admin);
    $html .= $this->htmlSnippet("Topic", $subject_topic);
    $html .= $this->htmlSnippet("Corporate Name(s)", $subject_corporate);
    $html .= $this->htmlSnippet("Subject (Personal)", $subject_personal);
    $html .= $this->htmlSnippet("Subject (Time)", $subject_time);
    $html .= $this->htmlSnippet("Geography", $subject_geographic);
    $html .= $this->htmlSnippet("Form", $subject_form);
    $html .= $this->htmlSnippet("Place of Publication", $place_of_publication);
    $html .= $this->htmlSnippet("Publisher", $publisher);
    $html .= $this->htmlSnippet("Language", $language);
    $html .= $this->htmlSnippet("Series", $series);
    $html .='</td></tr></table></div></div><br />';
    return $html;
  }

  private function htmlSnippet($name, $value) {
    $html = '</td></tr>';
    $html .= "<tr><td valign='top' align='right' class='mods_field'><strong>$name</strong></td><td>";
    if (!empty($value)) {
      foreach ($value as $va) {
        $html .= $va . '<br />';
      }
      return $html;
    }
    else {
      return '';
    }
  }

  public static function renderDatastreamViewer($pid = NULL, $dsid = NULL) {
    if (!isset($pid) || !isset($dsid)) {
      return '';
    }
    // Determine the datastream to render, figure out the mime type, find the proper class associated with that.
    // Render the viewer.
  }

  private function renderFileList() {
    // Renders a list of files aka datastreams
  }

  private function renderFile() {
    // Renders the file/icon
  }

  private function displayViewer() {
    // Do some ajax thing ?
  }

  private function includeRequiredScripts() {
    $path = drupal_get_path('module', 'fedora_repository');
    drupal_add_css("$path/stylesheets/ADRBasic.css");
    drupal_add_js("$path/js/ADRBasic.js");
  }

  /**
   * Render a set of tabs that describe the object and in some cases allow it to be modified by a user.
   * @global <type> $base_url
   * @global <type> $user
   * @return <type>
   */
  public function render() {
    $this->includeRequiredScripts();
    $this->tabs['overview'] = $this->renderOverviewTab();
    if ($this->canManageObject()) {
      $this->tabs['manage'] = $this->renderManageTab();
    }
    return '<div class="tabs">' . tabs_render($this->tabs) . '</div>';



    // Render tabs
    // Render Description
    // Render files
    module_load_include('inc', 'fedora_repository', 'plugins/tagging_form');
    module_load_include('inc', 'fedora_repository', 'plugins/ShowStreamsInFieldSets');
    global $base_url;
    $tabset = array();
    $tabset['my_tabset'] = array(
      '#type' => 'tabset',
    );

    global $user;
    $modsText = $this->item->get_datastream_dissemination("MODS");
    $modsAsHtml = $this->getModsAsHtml($modsText);
    $modsAsHtml = '<div class="fedora_object_container"><div class="fedora_mods_container">' . $modsAsHtml;
    $html = '</div><div class="fedora_links_container">';
    //$html .='Audio <br />'.$this->getListOfStreams('audio/mpeg');
    $html .='PDF <br />' . $this->getListOfStreams('application/pdf', array('Agreement'));
    $html .='</div></div><br />';
    $modsAsHtml .=$html;
    $tabset['my_tabset']['second_tab'] = array(
      '#type' => 'tabpage',
      '#title' => t('Descripton'),
      '#content' => $modsAsHtml,
    );

    return '<div class="tabs">' . tabs_render($tabset) . '</div>';
  }

}

class cog {

  private $pid = NULL;
  private $item = NULL;

  function cog($pid) {
    $this->pid = $pid;
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    module_load_include('inc', 'fedora_repository', 'api/dublin_core');
    $this->item = new Fedora_Item($pid);
    drupal_add_css(drupal_get_path('module', 'fedora_repository') . '/stylesheets/coccc.css');
  }

  function getModsAsHtml($modsString) {
    $xml = new SimpleXMLElement($modsString);
    $xml->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    $title = $xml->xpath('/mods:mods/mods:titleInfo/mods:title');
    $author = $xml->xpath('//mods:role[mods:roleTerm = "creator"]/../../mods:name/mods:namePart');
    $type_of_resource = $xml->xpath('//mods:typeOfResource');
    $digital_origin = $xml->xpath('//mods:physicalDescription/mods:digitalOrigin');
    $date_created = $xml->xpath('//mods:dateCreated[@keyDate="yes"]');
    $date_digitized = $xml->xpath('//mods:dateCaptured');
    $rights_statement = $xml->xpath('//mods:accessCondition[@type="useAndReproduction"]');
    $description = $xml->xpath('//mods:abstract');
    $note_admin = $xml->xpath('//mods:note[@type="admin"]');
    $subject_topic = $xml->xpath('//mods:subject/mods:topic');
    $subject_corporate = $xml->xpath('//mods:subject/mods:name[@type="corporate"]/mods:namePart');
    $subject_personal = $xml->xpath('//mods:subject/mods:name[@type="personal"]/mods:namePart');
    $subject_time = $xml->xpath('//mods:subject/mods:temporal');
    $subject_geographic = $xml->xpath('//mods:subject/mods:geographic');
    $subject_form = $xml->xpath('//mods:subject/mods:genre');
    $place_of_publication = $xml->xpath('//mods:originInfo/mods:place/mods:placeTerm');
    $publisher = $xml->xpath('//mods:originInfo/mods:publisher');
    $language = $xml->xpath('//mods:recordInfo/mods:languageOfCataloging/mods:languageTerm[@authority="iso639-2"]');
    $series = $xml->xpath('//mods:relatedItem/mods:titleInfo[@type="series"]/mods:title');
    $html .= '<table align="left"><tr><td valign="top" align="right" class="fedora_mods_field" width="15%">';
    $html .='<strong>Title</strong></td><td>';
    foreach ($title as $t) {
      $html .= $t . '<br />';
    }
    $html .= $this->htmlSnippet("Author", $author);
    $html .= $this->htmlSnippet("Type of Resource", $type_of_resource);
    $html .= $this->htmlSnippet("Digital Origin", $digital_origin);
    $html .= $this->htmlSnippet("Date Created", $date_created);
    $html .= $this->htmlSnippet("Date Digitized", $date_digitized);
    $html .= $this->htmlSnippet("Rights Statement", $rights_statement);
    $html .= $this->htmlSnippet("Description", $description);
    $html .= $this->htmlSnippet("Note", $note_admin);
    $html .= $this->htmlSnippet("Topic", $subject_topic);
    $html .= $this->htmlSnippet("Corporate Name(s)", $subject_corporate);
    $html .= $this->htmlSnippet("Subject (Personal)", $subject_personal);
    $html .= $this->htmlSnippet("Subject (Time)", $subject_time);
    $html .= $this->htmlSnippet("Geography", $subject_geographic);
    $html .= $this->htmlSnippet("Form", $subject_form);
    $html .= $this->htmlSnippet("Place of Publication", $place_of_publication);
    $html .= $this->htmlSnippet("Publisher", $publisher);
    $html .= $this->htmlSnippet("Language", $language);
    $html .= $this->htmlSnippet("Series", $series);
    $html .='</td></tr></table></div></div><br />';
    return $html;
  }

  function htmlSnippet($name, $value) {
    $html = '</td></tr>';
    $html .='<tr><td valign="top" align="right" class="mods_field"><strong>' . $name . '</strong></td><td>';
    if (!empty($value)) {
      foreach ($value as $va) {
        $html .= $va . '<br />';
      }
      return $html;
    }
    else {
      return '';
    }
  }

//finds datastreams that have a certain mimetype
  //excludes ones that contain any strings in the array $exclude
  //finds datastreams that have a certain mimetype
  //excludes ones that contain any strings in the array $exclude
  function getListOfStreams($mimeTypeToRetrieve, $exclude=null) {
    // $list_of_streams = array();
    global $base_url;
    $html = '';
    $datastreams_list = $this->item->get_datastreams_list_as_SimpleXML();
    if ($datastreams_list != FALSE) {
      foreach ($datastreams_list->datastreamDef as $ds) {
        if ($mimeTypeToRetrieve == $ds->MIMEType) {
          // $list_of_streams[] = $ds;
          $include = TRUE;
          if (isset($exclude)) {
            foreach ($exclude as $value) {
              if (stristr($ds->ID, $value)) {
                $include = false;
              }
            }
          }
          if ($include) {
            $label = $ds->label;
            if (empty($label)) {
              $label = $ds->ID;
            }
            $html .= '<a href = "' . $base_url . '/fedora/repository/' . $this->pid . '/' . $ds->ID . '/' . drupal_urlencode($label) . '">' . $label . '</a><br />';
          }
        }
      }
    }
    //return $list_of_streams;
    return $html;
  }

  function showTabs() {
    module_load_include('inc', 'fedora_repository', 'plugins/tagging_form');
    module_load_include('inc', 'fedora_repository', 'plugins/ShowStreamsInFieldSets');
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');

    global $base_url;

    $tabset = array();

    $tabset['my_tabset'] = array(
      '#type' => 'tabset',
    );

    // $listOfStreams = $this->getListOfStreams("audio/mpeg");

    global $user;
    $modsText = $this->item->get_datastream_dissemination("MODS");
    $modsAsHtml = $this->getModsAsHtml($modsText);
    $modsAsHtml = '<div class="fedora_object_container"><div class="fedora_mods_container">' . $modsAsHtml;
    $html = '</div><div class="fedora_links_container">';
    //$html .='Audio <br />'.$this->getListOfStreams('audio/mpeg');
    $html .='PDF <br />' . $this->getListOfStreams('application/pdf', array('Agreement'));
    $html .='</div></div><br />';
    $modsAsHtml .=$html;
    $tabset['my_tabset']['second_tab'] = array(
      '#type' => 'tabpage',
      '#title' => t('Descripton'),
      '#content' => $modsAsHtml,
    );


    /*  $tabset['my_tabset']['third_tab'] = array(
      '#type' => 'tabpage',
      '#title' => t('Image'),
      '#content' => '<a href ="'.$base_url.'/fedora/repository/'.$this->pid.'/WEB/web.jpg"><img src = "'.$base_url.'/fedora/repository/'.$this->pid.'/PREVIEW/preview.jpg"></a>',
      ); */
    // Render the tabset.
    return '<div class="tabs">' . tabs_render($tabset) . '</div>';
  }

}
