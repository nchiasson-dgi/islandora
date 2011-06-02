<?php

class FedoraObject {
  function  __construct($pid = '') {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    if (!empty($pid)) {

      $this->pid = $pid;
      $this->item = new Fedora_Item($pid);
    }
  }
  
  public function showFieldSets() {
    global $user;
    $objectHelper = new ObjectHelper();
    $tabset = array();

    $show_purge_tab = (!empty($_POST['form_id']) && $_POST['form_id'] == 'fedora_repository_purge_object_form');

    $base_path = base_path();
    $path = $base_path . drupal_get_path('module','fedora_repository');

    if (fedora_repository_access(OBJECTHELPER :: $EDIT_FEDORA_METADATA, $this->pid, $user)) {
      $edit_link = "<a href='$base_path" . "formbuilder/edit/$this->pid/MODS'><img alt='edit' src='$path/images/Crystal_Clear_action_run.png' width=50/>Edit Metadata<p></a>";
    }
    else {
      $edit_link = '';
    }
    
    $purge_form = drupal_get_form('fedora_repository_purge_object_form', $this->pid, check_plain(substr(request_uri(), strlen(base_path()))));

    $dc_html = $objectHelper->getFormattedDC($this->item);
    
    $ds_list = $objectHelper->get_formatted_datastream_list($this->pid, NULL, $this->item);

    $tabset['fedora_object_details'] = array(
      '#type' => 'tabpage',
      '#title' => t('Object Details'),
      '#selected' => $show_purge_tab,
      '#content' => $dc_html . $ds_list . $edit_link . $purge_form,
    );
    
    return $tabset;
  }
}