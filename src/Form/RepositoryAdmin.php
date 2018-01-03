<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormStateInterface;
use RepositoryException;

/**
 * Configuration for the Islandora module.
 */
class RepositoryAdmin extends IslandoraModuleHandlerAdminForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_repository_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $values = $form_state->getValues();
    $url = isset($values['islandora_base_url']) ? $values['islandora_base_url'] : $this->config('islandora.settings')->get('islandora_base_url');
    $restrict_namespaces = isset($values['islandora_namespace_restriction_enforced']) ? $values['islandora_namespace_restriction_enforced'] : $this->config('islandora.settings')->get('islandora_namespace_restriction_enforced');
    $breadcrumb_backend_options = $this->moduleHandler->invokeAll('islandora_breadcrumbs_backends');
    $map_to_title = function ($backend) {
      return $backend['title'];
    };
    // In case the selected breadcrumb backend is no longer available.
    $breadcrumb_backend = $this->config('islandora.settings')->get('islandora_breadcrumbs_backends');
    if (!isset($breadcrumb_backend_options[$breadcrumb_backend])) {
      $breadcrumb_backend = ISLANDORA_BREADCRUMB_LEGACY_BACKEND;
    }

    // Test connection the repository.
    $status = $this->repositoryAccess($url);
    $form = [
      'islandora_tabs' => [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'islandora-general',
      ],
      'islandora_general' => [
        '#type' => 'details',
        '#title' => $this->t('General Configuration'),
        '#group' => 'islandora_tabs',
        'wrapper' => [
          '#prefix' => '<div id="islandora-url">',
          '#suffix' => '</div>',
          'islandora_base_url' => [
            '#type' => 'textfield',
            '#title' => $this->t('Fedora base URL'),
            '#default_value' => $this->config('islandora.settings')->get('islandora_base_url'),
            '#description' => $this->t('The URL to use for REST connections'),
            '#required' => TRUE,
            '#ajax' => [
              'callback' => '::updateUrlDiv',
              'wrapper' => 'islandora-url',
              'disable-refocus' => TRUE,
            ],
          ],
          'status_image' => [
            '#theme' => 'image',
            '#uri' => $status['image'],
          ],
          'status_message' => [
            '#markup' => $status['message'],
          ],
        ],
        'islandora_repository_pid' => [
          '#type' => 'textfield',
          '#title' => $this->t('Root Collection PID'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_repository_pid'),
          '#description' => $this->t('The PID of the Root Collection Object'),
          '#required' => TRUE,
        ],
        'islandora_use_object_semaphores' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Make Processes Claim Objects for Modification'),
          '#description' => $this->t('Enabling this will increase stability of Fedora at high concurrency but will incur a heavy performance hit.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_use_object_semaphores'),
        ],
        'islandora_semaphore_period' => [
          '#type' => 'number',
          '#required' => TRUE,
          '#title' => $this->t('Time to Claim Objects for'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_semaphore_period'),
          '#description' => $this->t('Maximum time in seconds to claim objects for modification.'),
          '#min' => 1,
          '#states' => [
            'invisible' => [
              ':input[name="islandora_use_object_semaphores"]' => [
                'checked' => FALSE,
              ],
            ],
            'disabled' => [
              ':input[name="islandora_use_object_semaphores"]' => [
                'checked' => FALSE,
              ],
            ],
          ],
        ],
        'islandora_defer_derivatives_on_ingest' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Defer derivative generation during ingest'),
          '#description' => $this->t('Prevent derivatives from running during ingest,
          useful if derivatives are to be created by an external service.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_defer_derivatives_on_ingest'),
        ],
        'islandora_render_context_ingeststep' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Render applicable Content Model label(s) during ingest steps'),
          '#description' => $this->t('This enables contextual titles, displaying Content Model label(s), to be added on top of ingest forms.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_render_context_ingeststep'),
        ],
        'islandora_show_print_option' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Show print option on objects'),
          '#description' => $this->t('Displays an extra print tab, allowing an object to be printed'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_show_print_option'),
        ],
        'islandora_render_drupal_breadcrumbs' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Render Drupal breadcrumbs'),
          '#description' => $this->t('Larger sites may experience a notable performance improvement when disabled due to how breadcrumbs are constructed.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_render_drupal_breadcrumbs'),
        ],
        'islandora_breadcrumbs_backends' => [
          '#type' => 'radios',
          '#title' => $this->t('Breadcrumb generation'),
          '#description' => $this->t('How breadcrumbs for Islandora objects are generated for display.'),
          '#default_value' => $breadcrumb_backend,
          '#options' => array_map($map_to_title, $breadcrumb_backend_options),
          '#states' => [
            'visible' => [
              ':input[name="islandora_render_drupal_breadcrumbs"]' => [
                'checked' => TRUE,
              ],
            ],
          ],
        ],
        'islandora_risearch_use_itql_when_necessary' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Use iTQL for particular queries'),
          '#description' => $this->t('Sparql is the preferred language for querying the resource index; however, some features in the implementation of Sparql in Mulgara may not work properly. If you are using the default triple store with Fedora this should be left on to maintain legacy behaviour.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_risearch_use_itql_when_necessary'),
        ],
        'islandora_require_obj_upload' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Require a file upload during ingest'),
          '#description' => $this->t('During the ingest workflow, make the OBJ file upload step mandatory.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_require_obj_upload'),
        ],
      ],
      'islandora_namespace' => [
        '#type' => 'details',
        '#title' => $this->t('Namespaces'),
        '#group' => 'islandora_tabs',
        'islandora_namespace_restriction_enforced' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Enforce namespace restrictions'),
          '#description' => $this->t("Allow administrator to restrict user's access to the PID namepaces listed below"),
          '#default_value' => $restrict_namespaces,
        ],
        'islandora_pids_allowed' => [
          '#type' => 'textarea',
          '#title' => $this->t('PID namespaces allowed in this Drupal install'),
          '#description' => $this->t('A list of PID namespaces, separated by spaces, that users are permitted to access from this Drupal installation. <br /> This could be more than a simple namespace, e.g. <strong>demo:mydemos</strong>. <br /> The namespace <strong>islandora:</strong> is reserved, and is always allowed.'),
          '#default_value' => $this->config('islandora.settings')->get('islandora_pids_allowed'),
          '#states' => [
            'invisible' => [
              ':input[name="islandora_namespace_restriction_enforced"]' => [
                'checked' => FALSE,
              ],
            ],
          ],
        ],
      ],
      'islandora_ds_replace_exclude' => [
        '#type' => 'details',
        '#title' => $this->t('Excluded DSID'),
        '#group' => 'islandora_tabs',
        'islandora_ds_replace_exclude_enforced' => [
          '#type' => 'textfield',
          '#title' => $this->t('Enforce DSID restrictions'),
          '#description' => $this->t("A comma seperated list, allowing administrator to restrict user's access to replace a versionable datastreams latest version"),
          '#default_value' => $this->config('islandora.settings')->get('islandora_ds_replace_exclude_enforced'),
        ],
      ],
    ];
    $form['#attached']['library'][] = 'islandora/islandora-admin';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('islandora.settings')
      ->set('islandora_base_url', $form_state->getValue('islandora_base_url'))
      ->set('islandora_repository_pid', $form_state->getValue('islandora_repository_pid'))
      ->set('islandora_use_object_semaphores', $form_state->getValue('islandora_use_object_semaphores'))
      ->set('islandora_semaphore_period', $form_state->getValue('islandora_semaphore_period'))
      ->set('islandora_defer_derivatives_on_ingest', $form_state->getValue('islandora_defer_derivatives_on_ingest'))
      ->set('islandora_show_print_option', $form_state->getValue('islandora_show_print_option'))
      ->set('islandora_render_context_ingeststep', $form_state->getValue('islandora_render_context_ingeststep'))
      ->set('islandora_breadcrumbs_backends', $form_state->getValue('islandora_breadcrumbs_backend'))
      ->set('islandora_risearch_use_itql_when_necessary', $form_state->getValue('islandora_risearch_use_itql_when_necessary'))
      ->set('islandora_require_obj_upload', $form_state->getValue('islandora_require_obj_upload'))
      ->set('islandora_namespace_restriction_enforced', $form_state->getValue('islandora_namespace_restriction_enforced'))
      ->set('islandora_pids_allowed', $form_state->getValue('islandora_pids_allowed'))
      ->set('islandora_ds_replace_exclude_enforced', $form_state->getValue('islandora_ds_replace_exclude_enforced'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a message which describes if the repository is accessible.
   *
   * Also describes if the user is considered an authenticated user by the
   * repository.
   *
   * @param string $url
   *   The url to the Fedora Repository.
   *
   * @return array
   *   An array containing:
   *   -message: The translated status message to be rendered.
   *   -image: The path to the image to be rendered corresponding to the status
   *   message.
   */
  public function repositoryAccess($url) {
    $info = $dc = FALSE;
    drupal_static_reset('islandora_get_tuque_connection');
    $connection = islandora_get_tuque_connection(NULL, $url);
    $status = [];
    if ($connection) {
      try {
        $info = $connection->api->a->describeRepository();
        // If we are able to successfully call API-M::getDatastream, assume we
        // are an authenticated user, as API-M is usally locked down.
        $dc = $connection->api->m->getDatastream('fedora-system:ContentModel-3.0', 'DC');
      }
      catch (RepositoryException $e) {
        // Ignore, we only testing to see what is accessible.
      }
    }
    if ($info && $dc) {
      $status['message'] = $this->t('Successfully connected to Fedora Server (Version @version).', [
        '@version' => $info['repositoryVersion'],
      ]);
      $status['image'] = '/core/misc/icons/73b355/check.svg';
    }
    elseif ($info) {
      $status['message'] = $this->t('Unable to authenticate when connecting to to Fedora Server (Version @version). Please configure the @filter.', [
        '@version' => $info['repositoryVersion'],
        '@filter' => 'Drupal Filter',
      ]);
      $status['image'] = '/core/misc/icons/e29700/warning.svg';
    }
    else {
      $status['message'] = $this->t('Unable to connect to Fedora server at @islandora_url', [
        '@islandora_url' => $url,
      ]);
      $status['image'] = '/core/misc/icons/e32700/error.svg';
    }
    return $status;
  }

  /**
   * Updates the URL wrapper for the admin form.
   *
   * @param array $form
   *   The Drupal form being configured.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing the state of the form.
   *
   * @return array
   *   Renderable portion of the form to be updated.
   */
  public function updateUrlDiv(array $form, FormStateInterface $form_state) {
    return $form['islandora_general']['wrapper'];
  }

}
