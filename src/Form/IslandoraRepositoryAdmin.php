<?php

/**
 * @file
 * Contains \Drupal\islandora\Form\IslandoraRepositoryAdmin.
 */

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraRepositoryAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_repository_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    // @FIXME
    // The Assets API has totally changed. CSS, JavaScript, and libraries are now
    // attached directly to render arrays using the #attached property.
    // 
    // 
    // @see https://www.drupal.org/node/2169605
    // @see https://www.drupal.org/node/2408597
    // drupal_add_css(drupal_get_path('module', 'islandora') . '/css/islandora.admin.css');

    $url = islandora_system_settings_form_default_value('islandora_base_url', 'http://localhost:8080/fedora', $form_state);
    $restrict_namespaces = islandora_system_settings_form_default_value('islandora_namespace_restriction_enforced', FALSE, $form_state);
    $confirmation_message = islandora_admin_settings_form_repository_access_message($url);

    $breadcrumb_backend_options = \Drupal::moduleHandler()->invokeAll('islandora_breadcrumbs_backends');
    $map_to_title = function ($backend) {
      return $backend['title'];
    };
    // In case the selected breadcrumb backend is no longer available.
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/islandora.settings.yml and config/schema/islandora.schema.yml.
    $breadcrumb_backend = \Drupal::config('islandora.settings')->get('islandora_breadcrumbs_backends');
    if (!isset($breadcrumb_backend_options[$breadcrumb_backend])) {
      $breadcrumb_backend = ISLANDORA_BREADCRUMB_LEGACY_BACKEND;
    }

    $form = [
      'islandora_tabs' => [
        '#type' => 'vertical_tabs',
        'islandora_general' => [
          '#type' => 'fieldset',
          '#title' => t('General Configuration'),
          'wrapper' => [
            '#prefix' => '<div id="islandora-url">',
            '#suffix' => '</div>',
            '#type' => 'markup',
            'islandora_base_url' => [
              '#type' => 'textfield',
              '#title' => t('Fedora base URL'),
              '#default_value' => \Drupal::config('islandora.settings')->get('islandora_base_url'),
              '#description' => t('The URL to use for REST connections <br/> !confirmation_message', [
                '!confirmation_message' => $confirmation_message
                ]),
              '#required' => TRUE,
              '#ajax' => [
                'callback' => 'islandora_update_url_div',
                'wrapper' => 'islandora-url',
                'effect' => 'fade',
                'event' => 'blur',
                'progress' => [
                  'type' => 'throbber'
                  ],
              ],
            ],
          ],
          'islandora_repository_pid' => [
            '#type' => 'textfield',
            '#title' => t('Root Collection PID'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_repository_pid'),
            '#description' => t('The PID of the Root Collection Object'),
            '#required' => TRUE,
          ],
          'islandora_use_datastream_cache_headers' => [
            '#type' => 'checkbox',
            '#title' => t('Generate/parse datastream HTTP cache headers'),
            '#description' => t('HTTP caching can reduce network traffic, by allowing clients to used cached copies.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_use_datastream_cache_headers'),
          ],
          'islandora_use_object_semaphores' => [
            '#type' => 'checkbox',
            '#title' => t('Make Processes Claim Objects for Modification'),
            '#description' => t('Enabling this will increase stability of Fedora at high concurrency but will incur a heavy performance hit.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_use_object_semaphores'),
          ],
          'islandora_semaphore_period' => [
            '#type' => 'textfield',
            '#title' => t('Time to Claim Objects for'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_semaphore_period'),
            '#description' => t('Maximum time in seconds to claim objects for modification.'),
            '#states' => [
              'invisible' => [
                ':input[name="islandora_use_object_semaphores"]' => [
                  'checked' => FALSE
                  ]
                ]
              ],
          ],
          'islandora_defer_derivatives_on_ingest' => [
            '#type' => 'checkbox',
            '#title' => t('Defer derivative generation during ingest'),
            '#description' => t('Prevent derivatives from running during ingest,
            useful if derivatives are to be created by an external service.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_defer_derivatives_on_ingest'),
          ],
          'islandora_render_context_ingeststep' => [
            '#type' => 'checkbox',
            '#title' => t('Render applicable Content Model label(s) during ingest steps'),
            '#description' => t('This enables contextual titles, displaying Content Model label(s), to be added on top of ingest forms.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_render_context_ingeststep'),
          ],
          'islandora_show_print_option' => [
            '#type' => 'checkbox',
            '#title' => t('Show print option on objects'),
            '#description' => t('Displays an extra print tab, allowing an object to be printed'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_show_print_option'),
          ],
          'islandora_render_drupal_breadcrumbs' => [
            '#type' => 'checkbox',
            '#title' => t('Render Drupal breadcrumbs'),
            '#description' => t('Larger sites may experience a notable performance improvement when disabled due to how breadcrumbs are constructed.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_render_drupal_breadcrumbs'),
          ],
          'islandora_breadcrumbs_backends' => [
            '#type' => 'radios',
            '#title' => t('Breadcrumb generation'),
            '#description' => t('How breadcrumbs for Islandora objects are generated for display.'),
            '#default_value' => $breadcrumb_backend,
            '#options' => array_map($map_to_title, $breadcrumb_backend_options),
            '#states' => [
              'visible' => [
                ':input[name="islandora_render_drupal_breadcrumbs"]' => [
                  'checked' => TRUE
                  ]
                ]
              ],
          ],
          'islandora_risearch_use_itql_when_necessary' => [
            '#type' => 'checkbox',
            '#title' => t('Use iTQL for particular queries'),
            '#description' => t('Sparql is the preferred language for querying the resource index; however, some features in the implementation of Sparql in Mulgara may not work properly. If you are using the default triple store with Fedora this should be left on to maintain legacy behaviour.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_risearch_use_itql_when_necessary'),
          ],
          'islandora_require_obj_upload' => [
            '#type' => 'checkbox',
            '#title' => t('Require a file upload during ingest'),
            '#description' => t('During the ingest workflow, make the OBJ file upload step mandatory.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_require_obj_upload'),
          ],
        ],
        'islandora_namespace' => [
          '#type' => 'fieldset',
          '#title' => t('Namespaces'),
          'islandora_namespace_restriction_enforced' => [
            '#type' => 'checkbox',
            '#title' => t('Enforce namespace restrictions'),
            '#description' => t("Allow administrator to restrict user's access to the PID namepaces listed below"),
            '#default_value' => $restrict_namespaces,
          ],
          'islandora_pids_allowed' => [
            '#type' => 'textarea',
            '#title' => t('PID namespaces allowed in this Drupal install'),
            '#description' => t('A list of PID namespaces, separated by spaces, that users are permitted to access from this Drupal installation. <br /> This could be more than a simple namespace, e.g. <strong>demo:mydemos</strong>. <br /> The namespace <strong>islandora:</strong> is reserved, and is always allowed.'),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_pids_allowed'),
            '#states' => [
              'invisible' => [
                ':input[name="islandora_namespace_restriction_enforced"]' => [
                  'checked' => FALSE
                  ]
                ]
              ],
          ],
        ],
        'islandora_ds_replace_exclude' => [
          '#type' => 'fieldset',
          '#title' => t('Excluded DSID'),
          'islandora_ds_replace_exclude_enforced' => [
            '#type' => 'textfield',
            '#title' => t('Enforce DSID restrictions'),
            '#description' => t("A comma seperated list, allowing administrator to restrict user's access to replace a versionable datastreams latest version"),
            '#default_value' => \Drupal::config('islandora.settings')->get('islandora_ds_replace_exclude_enforced'),
          ],
        ],
      ]
      ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Only validate semaphore period if semaphores are enabled.
    if ($form_state->getValue(['islandora_use_object_semaphores'])) {
      if ($form_state->getValue(['islandora_semaphore_period'])) {
        element_validate_integer_positive($form['islandora_tabs']['islandora_general']['islandora_semaphore_period'], $form_state);
      }
      else {
        $form_state->setErrorByName('islandora_semaphore_period', t('<em>Time to Claim Objects for</em> must not be empty if <em>Make Processes Claim Objects for Modification</em> is checked.'));
      }
    }
  }

}
?>
