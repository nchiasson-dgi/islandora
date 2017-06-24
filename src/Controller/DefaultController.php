<?php /**
 * @file
 * Contains \Drupal\islandora\Controller\DefaultController.
 */

namespace Drupal\islandora\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use AbstractObject;
use AbstractDatastream;

/**
 * Default controller for the islandora module.
 */
class DefaultController extends ControllerBase {

  public function islandora_solution_packs_admin() {
    module_load_include('inc', 'islandora', 'includes/utilities');
    if (!islandora_describe_repository()) {
      islandora_display_repository_inaccessible_message();
      return '';
    }

    // @FIXME
    // The Assets API has totally changed. CSS, JavaScript, and libraries are now
    // attached directly to render arrays using the #attached property.
    //
    //
    // @see https://www.drupal.org/node/2169605
    // @see https://www.drupal.org/node/2408597
    // drupal_add_css(drupal_get_path('module', 'islandora') . '/css/islandora.admin.css');

    $output = [];
    $enabled_solution_packs = islandora_solution_packs_get_required_objects();
    foreach ($enabled_solution_packs as $solution_pack_module => $solution_pack_info) {
      // @todo We should probably get the title of the solution pack from the
    // systems table for consistency in the interface.
      $solution_pack_name = $solution_pack_info['title'];
      $objects = array_filter($solution_pack_info['objects']);
      $output[$solution_pack_module] = \Drupal::formBuilder()->getForm('islandora_solution_pack_form_' . $solution_pack_module, $solution_pack_module, $solution_pack_name, $objects);
    }
    return $output;
  }

  public function islandora_view_default_object() {
    $pid = \Drupal::config('islandora.settings')->get('islandora_repository_pid');
    return $this->redirect('islandora.view_object', array('object' => $pid));
  }

  public function islandora_drupal_title(AbstractObject $object) {
    module_load_include('inc', 'islandora', 'includes/breadcrumb');
    //drupal_set_breadcrumb(islandora_get_breadcrumbs($object));

    return $object->label;
  }

  public function islandora_object_access_callback($perm, $object, AccountInterface $account) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    if (!$object && !islandora_describe_repository()) {
      islandora_display_repository_inaccessible_message();
      return FALSE;
    }
    return AccessResult::allowedIf(islandora_object_access($perm, $object, $account));
  }

  public function islandora_view_object(AbstractObject $object) {
    module_load_include('inc', 'islandora', 'includes/breadcrumb');
    module_load_include('inc', 'islandora', 'includes/utilities');
    // XXX: This seems so very dumb but given how empty slugs don't play nice
    // in Drupal as defaults this needs to be the case. If it's possible to get
    // around this by making the empty slug route in YAML or a custom Routing
    // object we can remove this.
    if (\Drupal::request()->getRequestUri() === '/islandora/object/') {
      return $this->redirect('islandora.view_object', array('object' => \Drupal::config('islandora.settings')->get('islandora_repository_pid')));
    }
    // Warn if object is inactive or deleted.
    if ($object->state != 'A') {
      drupal_set_message(t('This object is not active. Metadata may not display correctly.'), 'warning');
    }
    // Optional pager parameters.
    $page_number = (empty($_GET['page'])) ? '1' : $_GET['page'];
    $page_size = (empty($_GET['pagesize'])) ? '10' : $_GET['pagesize'];
    $output = [];
    $hooks = islandora_build_hook_list(ISLANDORA_VIEW_HOOK, $object->models);
    foreach ($hooks as $hook) {
      // @todo Remove page number and size from this hook, implementers of the
    // hook should use drupal page handling directly.
      $temp = \Drupal::moduleHandler()->invokeAll($hook, [
        $object,
        $page_number,
        $page_size,
      ]);
      islandora_as_renderable_array($temp);
      if (!empty($temp)) {
        $output = array_merge_recursive($output, $temp);
      }
    }
    if (empty($output)) {
      // No results, use the default view.
      $output = islandora_default_islandora_view_object($object);
    }

    arsort($output);
    \Drupal::moduleHandler()->alter($hooks, $object, $output);
    return $output;
  }

  public function islandora_print_object_access($op, $object, AccountInterface $account) {
    if (!\Drupal::config('islandora.settings')->get('islandora_show_print_option')) {
      return FALSE;
    }
    $access = islandora_object_access($op, $object);
    return $access;
  }

  public function islandora_printer_object(AbstractObject $object) {
    $output = [];
    $temp_arr = [];

    // Dispatch print hook.
    foreach (islandora_build_hook_list(ISLANDORA_PRINT_HOOK, $object->models) as $hook) {
      $temp = \Drupal::moduleHandler()->invokeAll($hook, [$object]);
      islandora_as_renderable_array($temp);
      if (!empty($temp)) {
        $temp_arr = array_merge_recursive($temp_arr, $temp);
      }
    }
    $output = islandora_default_islandora_printer_object($object, \Drupal::service("renderer")->render($temp_arr));
    arsort($output);
    islandora_as_renderable_array($output);

    // Prompt to print.
    // @FIXME
    // The Assets API has totally changed. CSS, JavaScript, and libraries are now
    // attached directly to render arrays using the #attached property.
    //
    //
    // @see https://www.drupal.org/node/2169605
    // @see https://www.drupal.org/node/2408597
    // drupal_add_js('jQuery(document).ready(function () { window.print(); });', 'inline');

    return $output;
  }

  public function islandora_object_access($op, $object, $user = NULL, AccountInterface $account) {
    $cache = &drupal_static(__FUNCTION__);
    if (!is_object($object)) {
      // The object could not be loaded... Presumably, we don't have
    // permission.
      return FALSE;
    }
    if ($user === NULL) {
      $user = \Drupal::currentUser();
    }

    // Populate the cache on a miss.
    if (!isset($cache[$op][$object->id][$user->uid])) {
      module_load_include('inc', 'islandora', 'includes/utilities');

      $results = islandora_invoke_hook_list('islandora_object_access', $object->models, [
        $op,
        $object,
        $user,
      ]);
      // Nothing returned FALSE, and something returned TRUE.
      $cache[$op][$object->id][$user->uid] = (!in_array(FALSE, $results, TRUE) && in_array(TRUE, $results, TRUE));
    }
    ksm($cache);
    return $cache[$op][$object->id][$user->uid];
  }

  public function islandora_print_object(AbstractObject $object) {
    // @FIXME
// drupal_set_title() has been removed. There are now a few ways to set the title
// dynamically, depending on the situation.
//
//
// @see https://www.drupal.org/node/2067859
// drupal_set_title($object->label);

    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// return theme('islandora_object_print', array('object' => $object));

  }

  public function islandora_object_manage_access_callback($perms, $object = NULL, AccountInterface $account) {
    module_load_include('inc', 'islandora', 'includes/utilities');

    if (!$object && !islandora_describe_repository()) {
      islandora_display_repository_inaccessible_message();
      return FALSE;
    }

    $has_access = FALSE;
    for ($i = 0; $i < count($perms) && !$has_access; $i++) {
      $has_access = $has_access || islandora_object_access($perms[$i], $object);
    }

    return $has_access;
  }

  public function islandora_manage_overview_object(AbstractObject $object) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $output = islandora_create_manage_overview($object);
    $hooks = islandora_build_hook_list(ISLANDORA_OVERVIEW_HOOK, $object->models);
    foreach ($hooks as $hook) {
      $temp = \Drupal::moduleHandler()->invokeAll($hook, [$object]);
      islandora_as_renderable_array($temp);
      if (!empty($temp)) {
        arsort($temp);
        $output = array_merge_recursive($output, $temp);
      }
    }
    \Drupal::moduleHandler()->alter($hooks, $object, $output);
    islandora_as_renderable_array($output);
    return $output;
  }

  public function islandora_edit_object(AbstractObject $object) {
    module_load_include('inc', 'islandora', 'includes/breadcrumb');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $output = [];
    foreach (islandora_build_hook_list(ISLANDORA_EDIT_HOOK, $object->models) as $hook) {
      $temp = \Drupal::moduleHandler()->invokeAll($hook, [$object]);
      islandora_as_renderable_array($temp);
      if (!empty($temp)) {
        $output = array_merge_recursive($output, $temp);
      }
    }
    if (empty($output)) {
      // Add in the default, if we did not get any results.
      $output = islandora_default_islandora_edit_object($object);
    }
    arsort($output);
    \Drupal::moduleHandler()->alter(ISLANDORA_EDIT_HOOK, $object, $output);
    islandora_as_renderable_array($output);
    return $output;
  }

  public function islandora_add_datastream_form_autocomplete_callback(AbstractObject $object, $query = '') {
    module_load_include('inc', 'islandora', 'includes/content_model');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $dsids = array_keys(islandora_get_missing_datastreams_requirements($object));
    $dsids = array_combine($dsids, $dsids);
    $query = trim($query);
    if (!empty($query)) {
      $filter = function($id) use($query) {
        return stripos($id, $query) !== FALSE;
      };
      $dsids = array_filter($dsids, $filter);
    }
    drupal_json_output($dsids);

  }

  public function islandora_datastream_access($op, $datastream, $user = NULL, AccountInterface $account) {
    $cache = &drupal_static(__FUNCTION__);

    if (!is_object($datastream)) {
      // The object could not be loaded... Presumably, we don't have
    // permission.
      return NULL;
    }
    if ($user === NULL) {
      $user = \Drupal::currentUser();
    }

    // Populate the cache on a miss.
    if (!isset($cache[$op][$datastream->parent->id][$datastream->id][$user->uid])) {
      module_load_include('inc', 'islandora', 'includes/utilities');
      $object_results = islandora_invoke_hook_list('islandora_object_access', $datastream->parent->models, [
        $op,
        $datastream->parent,
        $user,
      ]);

      $datastream_results = islandora_invoke_hook_list('islandora_datastream_access', $datastream->parent->models, [
        $op,
        $datastream,
        $user,
      ]);

      // The datastream check returned FALSE, and one in the object or datastream
      // checks returned TRUE.
      $cache[$op][$datastream->parent->id][$datastream->id][$user->uid] = (
        !in_array(FALSE, $datastream_results, TRUE) && (in_array(TRUE, $object_results, TRUE) || in_array(TRUE, $datastream_results, TRUE))
        );
    }

    return $cache[$op][$datastream->parent->id][$datastream->id][$user->uid];
  }

  public function islandora_view_datastream(AbstractDatastream $datastream, $download = FALSE, $version = NULL) {
    module_load_include('inc', 'islandora', 'includes/mimetype.utils');
    module_load_include('inc', 'islandora', 'includes/datastream');

    // XXX: Certain features of the Devel module rely on the use of "shutdown
    // handlers", such as query logging... The problem is that they might blindly
    // add additional output which will break things if what is actually being
    // output is anything but a webpage... like an image or video or audio or
    // whatever the datastream is here.
    $GLOBALS['devel_shutdown'] = FALSE;

    if ($version !== NULL) {
      if (isset($datastream[$version])) {
        $datastream = $datastream[$version];
      }
      else {
        return drupal_not_found();
      }
    }
    header('Content-type: ' . $datastream->mimetype);
    if ($datastream->controlGroup == 'M' || $datastream->controlGroup == 'X') {
      header('Content-length: ' . $datastream->size);
    }
    if ($download) {
      // Browsers will not append all extensions.
      $extension = '.' . islandora_get_extension_for_mimetype($datastream->mimetype);
      // Prevent adding on a duplicate extension.
      $label = $datastream->label;
      $extension_length = strlen($extension);
      $duplicate_extension_position = strlen($label) > $extension_length ?
        strripos($label, $extension, -$extension_length) :
        FALSE;
      $filename = $label;
      if ($duplicate_extension_position === FALSE) {
        $filename .= $extension;
      }
      header("Content-Disposition: attachment; filename=\"$filename\"");
    }

    $cache_check = islandora_view_datastream_cache_check($datastream);
    if ($cache_check !== 200) {
      if ($cache_check === 304) {
        header('HTTP/1.1 304 Not Modified');
      }
      elseif ($cache_check === 412) {
        header('HTTP/1.0 412 Precondition Failed');
      }
    }
    islandora_view_datastream_set_cache_headers($datastream);

    // New content needed.
    if ($cache_check === 200) {
      // We need to see if the chunking is being requested. This will mainly
    // happen with iOS video requests as they do not support any other way
    // to receive content for playback.
      $chunk_headers = FALSE;
      if (isset($_SERVER['HTTP_RANGE'])) {
        // Set headers specific to chunking.
        $chunk_headers = islandora_view_datastream_set_chunk_headers($datastream);
      }
      // Try not to load the file into PHP memory!
      // Close and flush ALL the output buffers!
      while (@ob_end_flush()) {
      }
      ;

      if (isset($_SERVER['HTTP_RANGE'])) {
        if ($chunk_headers) {
          islandora_view_datastream_deliver_chunks($datastream, $chunk_headers);
        }
      }
      else {
        $datastream->getContent('php://output');
      }
    }
    exit();
  }

  public function islandora_download_datastream(AbstractDatastream $datastream) {
    islandora_view_datastream($datastream, TRUE);
  }

  public function islandora_edit_datastream(AbstractDatastream $datastream) {
    module_load_include('inc', 'islandora', 'includes/utilities');

    $edit_registry = islandora_build_datastream_edit_registry($datastream);
    $edit_count = count($edit_registry);
    switch ($edit_count) {
      case 0:
        // No edit implementations.
        drupal_set_message(t('There are no edit methods specified for this datastream.'));
        drupal_goto("islandora/object/{$datastream->parent->id}/manage/datastreams");
        break;

      case 1:
        // One registry implementation, go there.
        $entry = reset($edit_registry);
        drupal_goto($entry['url']);
        break;

      default:
        // Multiple edit routes registered.
        return islandora_edit_datastream_registry_render($edit_registry);
    }
  }

  public function islandora_datastream_version_table(AbstractDatastream $datastream) {
    module_load_include('inc', 'islandora', 'includes/datastream');
    module_load_include('inc', 'islandora', 'includes/utilities');
    $parent = $datastream->parent;
    // @FIXME
    // drupal_set_title() has been removed. There are now a few ways to set the title
    // dynamically, depending on the situation.
    //
    //
    // @see https://www.drupal.org/node/2067859
    // drupal_set_title(t("@dsid Previous Versions", array('@dsid' => $datastream->id)));

    $audit_values = islandora_get_audit_trail($parent->id, $datastream->id);

    $header = [];
    $header[] = ['data' => t('Created Date')];
    $header[] = ['data' => t('Size')];
    $header[] = ['data' => t('Label')];
    $header[] = ['data' => t('Responsibility')];
    $header[] = ['data' => t('Mime type')];
    $header[] = ['data' => t('Operations'), 'colspan' => '2'];
    $rows = [];

    foreach ($datastream as $version => $datastream_version) {
      $row = [];
      $reponsibility = $parent->owner;
      foreach ($audit_values as $audit_value) {
        $internal = $datastream_version->createdDate;
        if ($audit_value['date'] == $datastream_version->createdDate) {
          $reponsibility = $audit_value['responsibility'];
        }
      }
      $user = user_load_by_name($reponsibility);
      if ($user) {
        $user_id = $user->uid;
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // $user_val = l($reponsibility, "user/$user_id");

      }
      else {
        $user_val = $reponsibility;
      }
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      //
      //
      // @see https://www.drupal.org/node/2195739
      // $row[] = array(
      //       'class' => 'datastream-date',
      //       'data' => theme('islandora_datastream_view_link', array(
      //         'datastream' => $datastream,
      //         'label' => $datastream_version->createdDate->format(DATE_RFC850),
      //         'version' => $version,
      //       )),
      //     );

      $row[] = [
        'class' => 'datastream-size',
        'data' => islandora_datastream_get_human_readable_size($datastream_version),
      ];
      $row[] = [
        'class' => 'datastream-label',
        'data' => $datastream_version->label,
      ];
      $row[] = [
        'class' => 'datastream-responsibility',
        'data' => $user_val,
      ];
      $row[] = [
        'class' => 'datastream-mime',
        'data' => $datastream_version->mimeType,
      ];
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      //
      //
      // @see https://www.drupal.org/node/2195739
      // $row[] = array(
      //       'class' => 'datastream-delete',
      //       'data' => theme('islandora_datastream_delete_link', array(
      //         'datastream' => $datastream,
      //         'version' => $version,
      //       )),
      //     );

      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      //
      //
      // @see https://www.drupal.org/node/2195739
      // $row[] = array(
      //       'class' => 'datastream-revert',
      //       'data' => theme('islandora_datastream_revert_link', array(
      //         'datastream' => $datastream,
      //         'version' => $version,
      //       )),
      //     );

      $rows[] = $row;
    }

    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    //
    //
    // @see https://www.drupal.org/node/2195739
    // return theme('table', array('header' => $header, 'rows' => $rows));

  }

  public function islandora_event_status() {
    $results = FALSE;
    if (isset($_SESSION['islandora_event_messages'])) {
      foreach ($_SESSION['islandora_event_messages'] as $message) {
        drupal_set_message($message['message'], $message['severity']);
        $results = TRUE;
      }
      unset($_SESSION['islandora_event_messages']);
    }
    $text = ($results) ? t('The status messages above will be deleted after viewing this page.') : t('No messages to display.');
    return ['#markup' => $text];
  }

  public function islandora_content_model_autocomplete($string) {
    $content_models = islandora_get_content_model_names();
    $output = [];
    foreach ($content_models as $model => $label) {
      if (preg_match("/{$string}/i", $label) !== 0) {
        $output[$model] = $label;
      }
    }
    return drupal_json_output($output);
  }

  public function islandora_mime_type_autocomplete($string) {
    require_once \Drupal::root() . "/includes/file.mimetypes.inc";
    $mime_types = file_mimetype_mapping();
    $output = [];
    foreach ($mime_types as $mime_type) {
      if (preg_match("/{$string}/i", $mime_type) !== 0) {
        $output[$mime_type] = $mime_type;
      }
    }
    return drupal_json_output($output);
  }

}
