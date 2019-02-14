<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Orphaned Islandora Objects management form.
 */
class OrphanedObjects extends FormBase {

  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_manage_orphaned_objects_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora', 'inc', 'includes/orphaned_objects');
    if ($form_state->get('show_confirm') !== NULL) {
      $pids = $form_state->get('pids_to_delete');
      $form['confirm_message'] = [
        '#type' => 'item',
        '#markup' => $this->stringTranslation->formatPlural(count($form_state->get('pids_to_delete')),
        'Are you sure you want to delete this object? This action cannot be reversed.',
        'Are you sure you want to delete these @count objects? This action cannot be reversed.'),
      ];
      if (count($pids) <= 10) {
        $form['pids_to_delete'] = [
          '#type' => 'markup',
          '#theme' => 'item_list',
          '#list_type' => 'ol',
        ];
        $options = ['attributes' => ['target' => '_blank']];
        foreach ($pids as $pid) {
          $form['pids_to_delete']['#items'][] = Link::createFromRoute($pid, 'islandora.view_object', ['object' => $pid], $options);
        }
      }
      $form['confirm_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm'),
        '#weight' => 2,
        '#submit' => ['islandora_manage_orphaned_objects_confirm_submit'],
      ];
      $form['cancel_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#weight' => 3,
      ];
    }
    else {
      drupal_set_message($this->t('This page lists objects that have at least one parent, according to their RELS-EXT, that does not
      exist in the Fedora repository. These orphans might exist due to a failed batch ingest, their parents being deleted,
      or a variety of other reasons. Some of these orphans may exist intentionally.
      Please be cautious when deleting, as this action is irreversible.'), 'warning');
      $orphaned_objects = islandora_get_orphaned_objects();
      $rows = [];
      foreach ($orphaned_objects as $orphaned_object) {
        $pid = $orphaned_object['object']['value'];
        if (islandora_namespace_accessible($pid)) {
          $rows[$pid] = [
            Link::createFromRoute(
              "{$orphaned_object['title']['value']} ({$pid})",
              'islandora.view_object',
              ['object' => $pid]
            ),
          ];
        }
      }
      ksort($rows);
      $form['management_table'] = [
        '#type' => 'tableselect',
        '#header' => [$this->t('Object')],
        '#options' => $rows,
        '#attributes' => [],
        '#empty' => $this->t('No orphaned objects were found.'),
      ];
      if (!empty($rows)) {
        $form['submit_selected'] = [
          '#type' => 'submit',
          '#name' => 'islandora-orphaned-objects-submit-selected',
          '#value' => $this->t('Delete Selected'),
        ];
        $form['submit_all'] = [
          '#type' => 'submit',
          '#name' => 'islandora-orphaned-objects-submit-all',
          '#value' => $this->t('Delete All'),
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] !== 'islandora-orphaned-objects-submit-selected') {
      return;
    }
    $selected = array_filter($form_state->getValue('management_table'));
    if (empty($selected)) {
      $form_state->setError($form['management_table'], $this->t('At least one object must be selected to delete!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#parents'] == ['cancel_submit']) {
      return;
    }
    elseif ($form_state->getTriggeringElement()['#name'] == 'islandora-orphaned-objects-submit-selected') {
      $selected = array_keys(array_filter($form_state->getValue('management_table')));
    }
    else {
      $selected = array_keys($form_state->getValue('management_table'));
    }
    $form_state->set('pids_to_delete', $selected);
    // Rebuild to show the confirm form.
    $form_state->setRebuild();
    $form_state->set('show_confirm', TRUE);
  }

}
