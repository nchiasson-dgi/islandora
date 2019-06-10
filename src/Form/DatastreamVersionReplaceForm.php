<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractDatastream;

/**
 * Datastream replacement form.
 *
 * @package \Drupal\islandora\Form
 */
class DatastreamVersionReplaceForm extends FormBase {
  /**
   * File entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileEntityStorage;

  /**
   * Constructor.
   */
  public function __construct(EntityStorageInterface $file_entity_storage) {
    $this->fileEntityStorage = $file_entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_datastream_version_replace_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractDatastream $datastream = NULL) {
    $form_state->loadInclude('islandora', 'inc', 'includes/content_model');
    $form_state->loadInclude('islandora', 'inc', 'includes/utilities');
    $form_state->loadInclude('islandora', 'inc', 'includes/mimetype.utils');

    $object = islandora_object_load($datastream->parent->id);
    $form_state->set('object_id', $object->id);
    $form_state->set('dsid', $datastream->id);

    $extensions = islandora_get_extensions_for_datastream($object, $datastream->id);
    if (empty($extensions)) {
      // In case no extensions are returned, don't limit.
      $valid_extensions = NULL;
    }
    else {
      $valid_extensions = implode(' ', $extensions);
    }
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    return [
      'dsid_fieldset' => [
        '#type' => 'fieldset',
        '#title' => $this->t("Update Datastream"),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'dsid' => [
          '#type' => 'markup',
          '#markup' => $this->t("<div>DSID: <strong>@dsid</strong></div>", [
            '@dsid' => $datastream->id,
          ]),
        ],
        'label' => [
          '#type' => 'markup',
          '#markup' => $this->t("<div>Label: <strong>@label</strong></div>", [
            '@label' => $datastream->label,
          ]),
        ],
        'file' => [
          '#type' => 'managed_file',
          '#required' => TRUE,
          '#title' => $this->t('Upload Document'),
          '#size' => 64,
          '#description' => $this->t('Select a file to upload.') .
          $this->t('<br/>Files must be less than <strong>@size MB.</strong>', [
            '@size' => $upload_size,
          ]),
          '#upload_location' => file_default_scheme() . '://',
          '#upload_validators' => [
            'file_validate_extensions' => [
              $valid_extensions,
            ],
            // Assume its specified in MB.
            'file_validate_size' => [
              $upload_size * 1024 * 1024,
            ],
          ],
        ],
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Add Contents'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->get('object_id'));
    $form_state->setRedirect('islandora.view_object', ['object' => $object->id]);
    $file = $this->fileEntityStorage->load(reset($form_state->getValue(['file'])));
    try {
      $ds = $object[$form_state->get('dsid')];
      $mime = $file->getMimeType();
      if ($ds->mimetype != $mime) {
        $ds->mimetype = $mime;
      }
      $ds->setContentFromFile($file->getFileUri());
      $file->delete();
    }
    catch (exception $e) {
      drupal_set_message($this->t('An error occurred during datastream updates. See the log for more information.'), 'error');
      $this->getLogger('islandora')->error('Failed to add new versionable datastream.<br/>code: @code<br/>message: @msg', [
        '@code' => $e->getCode(),
        '@msg' => $e->getMessage(),
      ]);
      $file->delete();
      return;
    }
    drupal_set_message($this->t("Successfully Updated Datastream"));
  }

}
