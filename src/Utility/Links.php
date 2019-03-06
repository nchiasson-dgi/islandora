<?php

namespace Drupal\islandora\Utility;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Render\RendererInterface;

use AbstractDatastream;

/**
 * Helper class for rendering links to datastream actions.
 *
 * @package Drupal\islandora\Utility
 */
class Links {
  use StringTranslationTrait;

  protected $config;
  protected $moduleHandler;
  protected $renderer;

  /**
   * Constructor.
   */
  public function __construct(ConfigBase $config, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->config = $config;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * Helper to generate a link to download a datastream.
   */
  public function download(AbstractDatastream $datastream) {
    $output = islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $datastream) ?
      [
        '#type' => 'link',
        '#title' => $this->t('download'),
        '#url' => Url::fromRoute('islandora.download_datastream', [
          'object' => $datastream->parent->id,
          'datastream' => $datastream->id,
        ]),
      ] :
      [
        '#plain_text' => '',
      ];

    $this->renderer->addCacheableDependency($output, $datastream);
    return $output;
  }

  /**
   * Helper to generate a link to view a datastream.
   */
  public function view(AbstractDatastream $datastream, $version = NULL, $label = NULL) {
    $label = $label === NULL ? $datastream->id : $label;

    if ($version === NULL) {
      $output = islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $datastream) ?
        [
          '#type' => 'link',
          '#title' => $label,
          '#url' => Url::fromRoute('islandora.view_datastream', [
            'object' => $datastream->parent->id,
            'datastream' => $datastream->id,
          ]),
        ] :
        ['#plain_text' => $label];
      $this->renderer->addCacheableDependency($output, $datastream);
      return $output;
    }
    else {
      $output = islandora_datastream_access(ISLANDORA_VIEW_DATASTREAM_HISTORY, $datastream) ?
        [
          '#title' => $label,
          '#type' => 'link',
          '#url' => Url::fromRoute('islandora.view_datastream_version', [
            'object' => $datastream->parent->id,
            'datastream' => $datastream->id,
            'version' => $version,
          ]),
        ] :
        ['#plain_text' => $label];

      $this->renderer->addCacheableDependency($output, $datastream[$version]);
      return $output;
    }
  }

  /**
   * Helper to generate a link to delete a datastream.
   */
  public function delete(AbstractDatastream $datastream, $version = NULL) {
    $datastreams = $this->moduleHandler->invokeAll('islandora_undeletable_datastreams', [$datastream->parent->models]);

    $can_delete = !in_array($datastream->id, $datastreams) && islandora_datastream_access(ISLANDORA_PURGE, $datastream);
    if ($version !== NULL) {
      if (count($datastream) == 1) {
        $can_delete = FALSE;
      }
      $link = Url::fromRoute('islandora.delete_datastream_version_form', [
        'object' => $datastream->parent->id,
        'datastream' => $datastream->id,
        'version' => $version,
      ]);
    }
    else {
      $link = Url::fromRoute('islandora.delete_datastream_form', [
        'object' => $datastream->parent->id,
        'datastream' => $datastream->id,
      ]);
    }

    $output = ($can_delete && isset($link)) ?
      [
        '#title' => $this->t('delete'),
        '#url' => $link,
        '#type' => 'link',
      ] :
      ['#plain_text' => ''];
    $this->renderer->addCacheableDependency($output, $version === NULL ? $datastream : $datastream[$version]);
    return $output;

  }

  /**
   * Helper to generate a link to revert a datastream to a previous version.
   */
  public function revert(AbstractDatastream $datastream, $version = NULL) {
    $can_revert = islandora_datastream_access(ISLANDORA_REVERT_DATASTREAM, $datastream);
    if ($version !== NULL) {
      if (count($datastream) == 1) {
        $can_revert = FALSE;
      }
      $link = Url::fromRoute('islandora.revert_datastream_version_form', [
        'object' => $datastream->parent->id,
        'datastream' => $datastream->id,
        'version' => $version,
      ]);
    }
    else {
      $can_revert = FALSE;
    }
    $output = ($can_revert) ?
      [
        '#type' => 'link',
        '#title' => $this->t('revert'),
        '#url' => $link,
      ] :
      ['#plain_text' => ''];

    $this->renderer->addCacheableDependency($output, $version === NULL ? $datastream : $datastream[$version]);
    return $output;
  }

  /**
   * Helper to generate a link to edit the given datastream.
   */
  public function edit(AbstractDatastream $datastream) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $edit_registry = islandora_build_datastream_edit_registry($datastream);
    $can_edit = count($edit_registry) > 0 && islandora_datastream_access(ISLANDORA_METADATA_EDIT, $datastream);

    $output = $can_edit ?
      [
        '#type' => 'link',
        '#title' => $this->t('edit'),
        '#url' => Url::fromRoute('islandora.edit_datastream', [
          'object' => $datastream->parent->id,
          'datastream' => $datastream->id,
        ]),
      ] :
      ['#plain_text' => ''];
    $this->renderer->addCacheableDependency($output, $datastream);
    return $output;
  }

  /**
   * Helper to generate a link to view the table of versions of the datastream.
   */
  public function versions(AbstractDatastream $datastream) {
    $see_history = islandora_datastream_access(ISLANDORA_VIEW_DATASTREAM_HISTORY, $datastream);
    if ($see_history) {
      $output = $datastream->versionable ?
        [
          '#type' => 'link',
          '#title' => count($datastream),
          '#url' => Url::fromRoute('islandora.datastream_version_table', [
            'object' => $datastream->parent->id,
            'datastream' => $datastream->id,
          ]),
        ] :
        [
          '#plain_text' => $this->t('Not Versioned'),
        ];
    }
    else {
      $output = ['#plain_text' => ''];
    }

    $this->renderer->addCacheableDependency($output, $datastream);
    return $output;
  }

  /**
   * Helper to generate a link to the form to replace the datastream's content.
   */
  public function replace(AbstractDatastream $datastream) {
    $output = NULL;

    if (islandora_datastream_access(ISLANDORA_REPLACE_DATASTREAM_CONTENT, $datastream)) {
      $var_string = $this->config->get('islandora_ds_replace_exclude_enforced');
      $replace_exclude = explode(",", $var_string);
      if (!in_array($datastream->id, $replace_exclude)) {
        $output = [
          '#type' => 'link',
          '#title' => $this->t('replace'),
          '#url' => Url::fromRoute('islandora.datastream_version_replace_form', [
            'object' => $datastream->parent->id,
            'datastream' => $datastream->id,
          ]),
        ];
      }
    }

    if (!isset($output)) {
      $output = ['#plain_text' => ''];
    }

    $this->renderer->addCacheableDependency($output, $datastream);
    return $output;
  }

  /**
   * Helper to generate a link to the form to kick off derivative regeneration.
   */
  public function regenerate(AbstractDatastream $datastream) {
    $output = islandora_datastream_access(ISLANDORA_REGENERATE_DERIVATIVES, $datastream) ?
      [
        '#type' => 'link',
        '#title' => $this->t('regenerate'),
        '#url' => Url::fromRoute('islandora.regenerate_datastream_derivative_form', [
          'object' => $datastream->parent->id,
          'datastream' => $datastream->id,
        ]),
      ] :
      ['#plain_text' => ''];

    $this->renderer->addCacheableDependency($output, $datastream);
    return $output;
  }

}
