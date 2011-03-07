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
  protected $titles;
  protected $authors;
  protected $description;
  protected $dateCreated;
  protected $dateModified;
  protected $user;
  protected $defaultMimeIcons = array(
    'application/rdf+xml' => 'pdf.png',
    'text/xml' => 'pdf.png',
  );

  /**
   * Create a view for describing/managing an object.
   *
   * @param string $pid
   */
  public function __construct($pid) {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $this->pid = $pid;
    $this->item = new Fedora_Item($this->pid);
    $this->setDisplayInfo();
  }

  private function setDisplayInfo() {
    $mods = new SimpleXMLElement($this->item->get_datastream_dissemination("MODS"));
    $mods->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    $this->titles = $mods->xpath('/mods:mods/mods:titleInfo/mods:title');
    $this->authors = $mods->xpath('//mods:role[mods:roleTerm = "creator"]/../../mods:name/mods:namePart');
    $this->description = $mods->xpath('//mods:abstract');
    $foxml = new SimpleXMLElement($this->item->export_as_foxml());
    $foxml->registerXPathNamespace("audit", "info:fedora/fedora-system:def/audit#");
    $audit = $foxml->xpath("(//*[local-name()='responsibility'])[last()]");
    $this->user = $audit[0];
    $this->dateCreated = new DateTime($this->item->objectProfile->objCreateDate);
    $this->dateCreated = $this->dateCreated->format('F j, Y');
    $this->dateModified = new DateTime($this->item->objectProfile->objLastModDate);
    $this->dateModified = $this->dateModified->format('F j, Y');
  }

  /**
   * Determines if the user can view the manage tab, this is based on drupal/fedora permissions.
   *
   * @return boolean
   */
  private function canManageObject() {
    return FALSE;
  }

  /*
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
    return "<div id='fedora-object-title'>{$this->renderMultipleValues($titles)}</div>";
    }

    private function renderOverviewAuthor() {
    $authors = $this->mods->xpath('//mods:role[mods:roleTerm = "creator"]/../../mods:name/mods:namePart');
    return "<div id='fedora-object-author'>{$this->renderMultipleValues($authors)}</div>";
    }

    private function renderOverviewDescription() {
    $description = $this->mods->xpath('//mods:abstract');
    return "<div id='fedora-object-description'>{$description[0]}</div>";
    }

    private function renderOverviewDateInformation() {
    $audit = $this->foxml->xpath("(//*[local-name()='responsibility'])[last()]");
    $user = $audit[0];
    $date_created = new DateTime($this->item->objectProfile->objCreateDate);
    $date_created = $date_created->format('F j, Y');
    $date_modified = new DateTime($this->item->objectProfile->objLastModDate);
    $date_modified = $date_modified->format('F j, Y');
    return "<div id='fedora-object-date-info'>This object was added {$date_created}<br/>This object was last updated {$date_modified} by {$user}.</div>";
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
   */

  private function includeRequiredScripts() {
    module_load_include('module', 'ext', 'ext');
    ext_load_library();
    $path = drupal_get_path('module', 'fedora_repository');
    drupal_add_css("$path/stylesheets/ADRBasic.css");
    $this->createDescriptionStore();
    $this->createDatastreamStore();
    $include_files = array(
      'ADRBasicViewer.ui.js',
      'ADRBasicViewer.js',
      'DescriptionPanel.ui.js',
      'DescriptionPanel.js',
      'FilesPanel.ui.js',
      'FilesPanel.js',
      'ManagePanel.ui.js',
      'ManagePanel.js',
      'ViewerPanel.ui.js',
      'ViewerPanel.js',
      'ADRBasic.js',
    );
    foreach ($include_files as $file) {
      drupal_add_js("$path/js/ADRBasic/$file");
    }
  }

  private function createDescriptionStore() {
    $data = array();
    $fields = array('title', 'author', 'description');
    $data[] = array(
      implode(' ', $this->titles), implode(' ', $this->authors), $this->description
    );
    $fields = $this->convertPhpArrayToJsString($fields);
    $data = $this->convertPhpArrayToJsString($data);
    $datastream = $this->createStore('FedoraObjectInfoStore', $data, $fields);
    drupal_add_js($datastream, 'inline');
  }

  private function createDatastreamStore() {
    $data = array();
    $fields = array('dsid', 'label', 'mime', 'img');
    foreach ($this->item->datastreams as $dsid => $attr) {
      $img = $this->getDatastreamIconURL($dsid, $attr['MIMEType']);
      $data[] = array($dsid, $attr['label'], $attr['MIMEType'], $img);
    }
    $fields = $this->convertPhpArrayToJsString($fields);
    $data = $this->convertPhpArrayToJsString($data);
    $datastream = $this->createStore('DatastreamStore', $data, $fields);
    drupal_add_js($datastream, 'inline');
  }

  private function getDatastreamIconURL($dsid, $mime_type) {
    // Find in object
    // If not found use default for mime type.
    $path_to_default_images = drupal_get_path('module', 'fedora_repository') . '/images/mime_types/';
    $image = $this->defaultMimeIcons[$mime_type];
    return 'http://coalliance.dev/' . $path_to_default_images . $image;// $image;
  }

  private function createStore($storeId, $data, $fields) {
    return "$storeId = Ext.extend(Ext.data.ArrayStore, {
    constructor: function(cfg) {
        cfg = cfg || {};
        $storeId.superclass.constructor.call(this, Ext.apply({
            storeId: '$storeId',
            idIndex: 0,
            autoDestroy: true,
            data: $data,
            fields: $fields}, cfg));
    }});
    new $storeId()";
  }

  /**
   *
   * @param <type> $array
   * @return string
   */
  private function convertPhpArrayToJsString($array) {
    $output = '[ ';
    foreach ($array as $value) {
      if (is_array($value)) {
        $output .= $this->convertPhpArrayToJsString($value) . ',';
      }
      else {
        $output .= "'$value',";
      }
    }
    $output .= ' ]';
    return $output;
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
  }

}