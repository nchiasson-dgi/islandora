<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

use Drupal\islandora\Form\IslandoraSolutionPackForm;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AbstractObject;
use AbstractDatastream;

/**
 * Class DefaultController
 * @package Drupal\islandora\Controller
 */
class DefaultController extends ControllerBase {

  protected $formbuilder;

  // XXX: Coder complains if you reference \Drupal core services
  // directly without using dependency injection. Here is a working example
  // injecting formbuilder into our controller.
  public function __construct(FormBuilderInterface $formbuilder) {
    $this->formbuilder = $formbuilder;
  }

  /**
   * Dependency Injection!
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service(s) required to construct this class.
    // Order should be the same as the order they are listed in the constructor.
      $container->get('form_builder')
    );
  }

  /**
   * Administer solutions packs.
   * @return array|string
   * @throws \Exception
   */
  public function islandora_solution_packs_admin() {
    module_load_include('inc', 'islandora', 'includes/utilities');
    module_load_include('inc', 'islandora', 'includes/solution_packs');

    if (!islandora_describe_repository()) {
      islandora_display_repository_inaccessible_message();
      return '';
    }

    $output = [];
    $enabled_solution_packs = islandora_solution_packs_get_required_objects();
    foreach ($enabled_solution_packs as $solution_pack_module => $solution_pack_info) {
      // @todo We should probably get the title of the solution pack from the
      // systems table for consistency in the interface.
      $solution_pack_name = $solution_pack_info['title'];
      $objects = array_filter($solution_pack_info['objects']);
      $class_name = IslandoraSolutionPackForm::class;

      $output[$solution_pack_module] = $this->formbuilder->getForm($class_name, $solution_pack_module, $solution_pack_name, $objects);

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
      if (!empty($temp)) {
        $temp_arr = array_merge_recursive($temp_arr, $temp);
      }
    }
    $output = islandora_default_islandora_printer_object($object, \Drupal::service("renderer")->render($temp_arr));
    arsort($output);

    // Prompt to print.
    $output['#attached']['library'][] = 'islandora/islandora-print-js';
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
    if (!isset($cache[$op][$object->id][$user->id()])) {
      module_load_include('inc', 'islandora', 'includes/utilities');

      $results = islandora_invoke_hook_list('islandora_object_access', $object->models, [
        $op,
        $object,
        $user,
      ]);
      // Nothing returned FALSE, and something returned TRUE.
      $cache[$op][$object->id][$user->id()] = (!in_array(FALSE, $results, TRUE) && in_array(TRUE, $results, TRUE));
    }
    return $cache[$op][$object->id][$user->id()];
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
    if (!isset($cache[$op][$datastream->parent->id][$datastream->id][$user->id()])) {
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
      $cache[$op][$datastream->parent->id][$datastream->id][$user->id()] = (
        !in_array(FALSE, $datastream_results, TRUE) && (in_array(TRUE, $object_results, TRUE) || in_array(TRUE, $datastream_results, TRUE))
        );
    }

    return $cache[$op][$datastream->parent->id][$datastream->id][$user->id()];
  }

  public function islandoraViewDatastreamTitle(AbstractDatastream $datastream, $download = FALSE, $version = NULL) {
    return $datastream->id;
  }
  /**
   * Callback function to view or download a datastream.
   *
   * @param AbstractDatastream $datastream
   *   The datastream to view/download.
   * @param bool $download
   *   If TRUE the file is download to the user computer for viewing otherwise
   *   it will attempt to display in the browser natively.
   * @param int $version
   *   The version of the datastream to display.
   *
   * @return Symfony\Component\HttpFoundation\BinaryFileResponse|Symfony\Component\HttpFoundation\StreamedResponse
   *   A BinaryFileResponse if it's a ranged request, a StreamedResponse
   *   otherwise.
   */
  public function islandoraViewDatastream(AbstractDatastream $datastream, $download = FALSE, $version = NULL) {
    module_load_include('inc', 'islandora', 'includes/mimetype.utils');
    module_load_include('inc', 'islandora', 'includes/datastream');

    if ($version !== NULL) {
      if (isset($datastream[$version])) {
        $datastream = $datastream[$version];
      }
      else {
        return drupal_not_found();
      }
    }
    $headers = [
      'Content-type' => $datastream->mimetype,
      'Last-Modified' => $datastream->createdDate->format('D, d M Y H:i:s \G\M\T'),
    ];
    // XXX: The two response objects being used are considered non-cacheable by
    // default. By setting the cache control we allow these responses to be
    // cached. Non-cacheable responses wipe away certain headers that are nice
    // to have such as 'Last-Modified' and 'Etag' (for the checksum).
    $cache_control_visibility = $datastream->parent->repository->api->connection->username == 'anonymous' ? 'public' : 'private';
    $cache_control[] = $cache_control_visibility;
    $cache_control[] = 'must-revalidate';
    $cache_control[] = 'max-age=0';
    $headers['Cache-Control'] = implode(', ', $cache_control);
    if (isset($datastream->checksum)) {
      $headers['Etag'] = "\"{$datastream->checksum}\"";
    }
    $status = 200;
    if ($datastream->controlGroup == 'M' || $datastream->controlGroup == 'X') {
      $headers['Content-Length'] = $datastream->size;
    }
    $content_disposition = NULL;
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
      $content_disposition = "attachment; filename=\"{$filename}\"";
    }
    // We need to see if the chunking is being requested. This will mainly
    // happen with iOS video requests as they do not support any other way
    // to receive content for playback.
    if (isset($_SERVER['HTTP_RANGE'])) {
      module_load_include('inc', 'islandora', 'includes/datastream');
      $file_uri = islandora_view_datastream_retrieve_file_uri($datastream);
      $binary_content_disposition = isset($content_disposition) ? 'attachment' : NULL;
      $response = new BinaryFileResponse($file_uri, $status, $headers, $cache_control_visibility, $binary_content_disposition, FALSE, FALSE);
    }
    else {
      $streaming_callback = function () use ($datastream) {
        $datastream->getContent('php://output');
      };
      if ($content_disposition) {
        $headers['Content-Disposition'] = $content_disposition;
      }
      $response = new StreamedResponse($streaming_callback, $status, $headers);
    }
    return $response;
  }

  /**
   * Callback to download the given datastream to the users computer.
   *
   * @param AbstractDatastream $datastream
   *   The datastream to download.
   *
   * @@return Symfony\Component\HttpFoundation\BinaryFileResponse|Symfony\Component\HttpFoundation\StreamedResponse
   *   A BinaryFileResponse if it's a ranged request, a StreamedResponse
   *   otherwise.
   */
  public function islandoraDownloadDatastream(AbstractDatastream $datastream) {
    return $this->islandoraViewDatastream($datastream, TRUE);
  }

  public function islandora_edit_datastream(AbstractDatastream $datastream) {
    module_load_include('inc', 'islandora', 'includes/utilities');

    $edit_registry = islandora_build_datastream_edit_registry($datastream);
    $edit_count = count($edit_registry);
    switch ($edit_count) {
      case 0:
        // No edit implementations.
        drupal_set_message($this->t('There are no edit methods specified for the @id datastream.', ['@id' => $datastream->id]));
        return $this->redirect('islandora.edit_object', ['object' => $datastream->parent->id]);

      case 1:
        // One registry implementation, go there.
        $entry = reset($edit_registry);
        return RedirectResponse::create($entry['url']);

      default:
        // Multiple edit routes registered.
        $list = [
          '#theme' => 'item_list',
          '#items' => [],
        ];
        foreach ($edit_registry as $entry) {
          $list['#items'][$entry['name']] = [
            '#type' => 'link',
            '#title' => $entry['name'],
            // XXX: Doesn't anything which accepts as a string... I foresee
            // having to rework the hook to return the route info (route name
            // and parameters).
            '#url' => Url::fromUserInput($entry['url']),
          ];
        }
        return $list;
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
        $user_id = $user->id();
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
