<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Component\Utility\Html;

use AbstractObject;

/**
 * Datastream ingest form.
 *
 * @package \Drupal\islandora\Form
 */
class IslandoraAddDatastreamForm extends FormBase {
  /**
   * File entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructor.
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct($container);

    $this->fileStorage = $container->get('entity_type.manager')->getStorage('file');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_add_datastream_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    module_load_include('inc', 'islandora', 'includes/content_model');
    module_load_include('inc', 'islandora', 'includes/utilities');

    $form_state->set(['object_id'], $object->id);

    $datastream_requirements = islandora_get_missing_datastreams_requirements($object);
    $unused_datastreams = array_keys($datastream_requirements);
    $unused_datastreams = "'" . implode("', '", $unused_datastreams) . "'";
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));

    return [
      '#attributes' => [
        'enctype' => 'multipart/form-data',
      ],
      'dsid_fieldset' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Add a Datastream'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'dsid' => [
          '#title' => 'Datastream ID',
          '#description' => $this->t("An ID for this stream that is unique to this object. Must start with a letter and contain only alphanumeric characters, dashes and underscores. The following datastreams are defined by this content model but don't currently exist: <strong>@unused_dsids</strong>.", [
            '@unused_dsids' => $unused_datastreams,
          ]),
          '#type' => 'textfield',
          '#size' => 64,
          '#maxlength' => 64,
          '#required' => TRUE,
          '#element_validate' => [
            '::dsidDoesNotExist',
            '::dsidStartsWithLetter',
            '::dsidIsValid',
          ],
          '#autocomplete_path' => "islandora/object/{$object->id}/manage/datastreams/add/autocomplete",
        ],
        'label' => [
          '#title' => 'Datastream Label',
          '#required' => TRUE,
          '#size' => 64,
          '#maxlength' => 64,
          '#description' => $this->t('A human-readable label.'),
          '#type' => 'textfield',
          '#element_validate' => [
            '::labelDoesNotContainForwardSlash',
          ],
        ],
        'file' => [
          '#type' => 'managed_file',
          '#required' => TRUE,
          '#title' => $this->t('Upload Document'),
          '#size' => 48,
          '#description' => $this->t('Select a file to upload.<br/>Files must be less than <strong>@size MB.</strong>', [
            '@size' => $upload_size,
          ]),
          '#default_value' => !$form_state->getValue(['files']) ? $form_state->getValue([
            'files',
          ]) : NULL,
          '#upload_location' => file_default_scheme() . '://',
          '#upload_validators' => [
            // Disable default file_validate_extensions; we need direct control.
            'file_validate_extensions' => [
              NULL,
            ],
            // Assume its specified in MB.
            'file_validate_size' => [
              $upload_size * 1024 * 1024,
            ],
          ],
        ],
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Add Datastream'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/mimetype.utils');
    $extensions = islandora_get_extensions_for_datastream($form_state->get([
      'object',
    ]), $form_state->getValue(['dsid']));
    $file = $this->fileStorage->load(reset($form_state->getValue(['file'])));
    // Only validate extensions if mimes defined in ds-composite.
    if ($file && $extensions) {
      $errors = file_validate_extensions($file, implode(' ', $extensions));
      if (count($errors) > 0) {
        $form_state->setErrorByName('file', $this->t("!error (for the set DSID)", [
          '!error' => $errors[0],
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get(['object_id']));
    $form_state->setRedirect('islandora.view_object', ['object' => $object->id]);
    $file = $this->fileStorage->load(reset($form_state->getValue(['file'])));
    try {
      $ds = $object->constructDatastream($form_state->getValue(['dsid']), 'M');
      $ds->label = $form_state->getValue(['label']);
      $ds->mimetype = $file->getMimeType();
      $ds->setContentFromFile($file->getFileUri());
      $object->ingestDatastream($ds);
      drupal_set_message($this->t("Successfully Added Datastream!"));
    }
    catch (Exception $e) {
      drupal_set_message($this->t('@message', [
        '@message' => Html::escape($e->getMessage()),
      ]), 'error');
      return;
    }
    finally {
      $file->delete();
    }
  }

  /**
   * Callback for #element_validate.
   */
  public function dsidDoesNotExist(array $element, FormStateInterface $form_state, array $form) {
    $object = islandora_object_load($form_state->get('object_id'));
    if (isset($object[$element['#value']])) {
      $form_state->setError($element, $this->t("@title already exists in the object.", [
        '@title' => $element['#title'],
      ]));
    }
  }

  /**
   * Callback for #element_validate.
   */
  public function dsidStartsWithLetter(array $element, FormStateInterface $form_state, array $form) {
    if (!(preg_match("/^[a-zA-Z]/", $element['#value']))) {
      $form_state->setError($element, $this->t("@title has to start with a letter.", [
        '@title' => $element['#title'],
      ]));
    }
  }

  /**
   * Callback for #element_validate.
   */
  public function dsidIsValid(array $element, FormStateInterface $form_state, array $form) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    if (!islandora_is_valid_dsid($element['#value'])) {
      $form_state->setError($element, $this->t("@title contains invalid characters.", [
        '@title' => $element['#title'],
      ]));
    }
  }

  /**
   * Callback for #element_validate.
   */
  public function labelDoesNotContainForwardSlash(array $element, FormStateInterface $form_state, array $form) {
    if (strpos($element['#value'], '/') !== FALSE) {
      $form_state->setError($element, $this->t('@title cannot contain a "/" character.', [
        '@title' => $element['#title'],
      ]));
    }
  }

}
