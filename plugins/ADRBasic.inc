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
   * MODS datastream. Information is extracted from this XML document and rendered to the form.
   * 
   * @var array
   */
  protected $mods;

  /**
   * Create a view for describing/managing an object.
   * 
   * @param string $pid
   */
  public function __construct($pid) {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $this->pid = $pid;
    $this->item = new Fedora_Item($this->pid);
    $this->mods = new SimpleXMLElement($this->item->get_datastream_dissemination("MODS"));
    $this->mods->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    $this->foxml = new SimpleXMLElement($this->item->export_as_foxml());
    $this->mods->registerXPathNamespace("audit", "info:fedora/fedora-system:def/audit#");
  }

  /**
   * Determines if the user can view the manage tab, this is based on drupal/fedora permissions.
   *
   * @return boolean
   */
  private function canManageObject() {
    return FALSE;
  }

  private function renderMultipleValues($values) {
    $output = '';
    if (count($values) == 1) {
      return $values[0];
    }
    foreach ($values as $value) {
      if ($value != '') {
        $output .= $value . '<br/>';
      }
    }
    return $output;
  }

  private function renderOverviewTitle() {
    $titles = $this->mods->xpath('/mods:mods/mods:titleInfo/mods:title');
    return "<div id='fedora-object-title' class='overview-value'>{$this->renderMultipleValues($titles)}</div>";
  }

  private function renderOverviewAuthor() {
    $authors = $this->mods->xpath('//mods:role[mods:roleTerm = "creator"]/../../mods:name/mods:namePart');
    return "<div id='fedora-object-author' class='overview-value' >{$this->renderMultipleValues($authors)}</div>";
  }

  private function renderOverviewDescription() {
    $description = $this->mods->xpath('//mods:abstract');
    return "<div id='fedora-object-description' class='overview-value' >{$description[0]}</div>";
  }

  private function renderOverviewDateInformation() {
    $audit = $this->foxml->xpath("(//*[local-name()='responsibility'])[last()]");
    $user = $audit[0];
    $date_created = new DateTime($this->item->objectProfile->objCreateDate);
    $date_created = $date_created->format('F j, Y');
    $date_modified = new DateTime($this->item->objectProfile->objLastModDate);
    $date_modified  = $date_modified ->format('F j, Y');
    return "<div id='fedora-object-date-info'>This object was added {$date_created}<br/>This object was last updated {$date_modified} by {$user}.</div>";
  }

  private function renderOverviewInformation() {
    $output = $this->renderOverviewTitle();
    $output .= $this->renderOverviewAuthor();
    $output .= $this->renderOverviewDescription();
    $output .= $this->renderOverviewDateInformation();
    return $output;
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
    module_load_include('module', 'ext', 'ext');
    ext_load_library();
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
    module_load_include('inc', 'fedora_repository', 'plugins/ShowStreamsInFieldSets');
    module_load_include('inc', 'fedora_repository', 'plugins/tagging_form');
    $this->includeRequiredScripts();
    $output = $this->renderOverviewInformation();
    return $output;
  }

}