<?php

namespace Drupal\s3fs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an actions form.
 */
class ActionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 's3fs_admin_actions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['refresh_cache'] = array(
      '#type' => 'fieldset',
      '#description' => $this->t(
        "The file metadata cache keeps track of every file that S3 File System writes to (and deletes from) the S3 bucket,
      so that queries for data about those files (checks for existence, filetype, etc.) don't have to hit S3.
      This speeds up many operations, most noticeably anything related to images and their derivatives."
      ),
      '#title' => $this->t('File Metadata Cache'),
    );
    $refresh_description = '<b>Not implemented yet.</b> ' . $this->t(
      "This button queries S3 for the metadata of <i><b>all</b></i> the files in your site's bucket (unless you use the
    Root Folder option), and saves it to the database. This may take a while for buckets with many thousands of files. <br>
    It should only be necessary to use this button if you've just installed S3 File System and you need to cache all the
    pre-existing files in your bucket, or if you need to restore your metadata cache from scratch for some other reason."
    );
    $form['refresh_cache']['refresh'] = array(
      '#type' => 'submit',
      '#suffix' => '<div class="refresh">' . $refresh_description . '</div>',
      '#value' => $this->t('Refresh file metadata cache'),
      '#attached' => array(
//        'css' => array(
          // Push the button closer to its own description, and push the disable
          // checkbox away from the description.
//          '#edit-refresh {margin-bottom: 0; margin-top: 1em;} div.refresh {margin-bottom: 1em;}' => array('type' => 'inline')
//        ),
      ),
      // @todo Do in refresh cache issue
      '#disabled' => TRUE,
      '#submit' => array('_s3fs_refresh_cache_submit'),
    );

    // @todo Add to Readme
    $form['copy_local'] = array(
      '#type' => 'fieldset',
      '#description' => $this->t(
        "<b>Important: This feature is for sites that have configured or going to have configured to take
      over for the public and/or private file systems. Example: You should have
      \$settings['s3fs.use_s3_for_public'] = TRUE; or \$settings['s3fs.use_s3_for_private'] = TRUE; after
      or before use this actions.</b> You may wish to copy any files which were previously uploaded to
      your site into your S3 bucket. <br> If you have a lot of files, or very large files, you'll want to
      use <i>drush s3fs-copy-local</i> instead of this form, as the limitations imposed by browsers may
      break very long copy operations."
      ),
      '#title' => $this->t('Copy Local Files to S3'),
    );

    $form['copy_local']['public'] = array(
      '#type' => 'submit',
      '#prefix' => '<br>',
      '#name' => 'public',
      '#value' => $this->t('Copy local public files to S3'),
      '#validate' => array(
        array($this, 'copyLocalValidateForm'),
      ),
      '#submit' => array(
        array($this, 'copyLocalSubmitForm'),
      ),
    );

    if (Settings::get('file_private_path')) {
      $form['copy_local']['private'] = array(
        '#type' => 'submit',
        '#prefix' => '<br>',
        '#name' => 'private',
        '#value' => $this->t('Copy local private files to S3'),
        '#validate' => array(
          array($this, 'copyLocalValidateForm'),
        ),
        '#submit' => array(
          array($this, 'copyLocalSubmitForm'),
        ),
      );
    }

    return $form;
  }

  public function copyLocalValidateForm($form, FormStateInterface $form_state) {
    $config = \Drupal::config('s3fs.settings')->get();
    if (!\Drupal::service('s3fs')->validate($config)) {
      $form_state->setError(
        $form,
        $this->t('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
      );
    }

    $local_normal_wrappers = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::LOCAL_NORMAL);
    $triggering_element = $form_state->getTriggeringElement();
    $destination_scheme = $triggering_element['#name'];

    if (!empty($local_normal_wrappers[$destination_scheme])) {
      if ($destination_scheme == 'private' && !Settings::get('file_private_path')) {
        $form_state->setError(
          $form,
          $this->t("Private system is not properly configurated, check \$settings['file_private_path'] in your settings.php.")
        );
      }
    }
    else {
      $form_state->setError(
        $form,
        $this->t('Scheme @scheme is not supported.', array(
          '@scheme' => $destination_scheme
        ))
      );
    }

    // Use this calculated values for submit step
    $form_state->set('copy_validate', array(
      'config' => $config,
      'scheme' => $destination_scheme,
    ));
  }

  public function copyLocalSubmitForm(array &$form, FormStateInterface $form_state) {
    $copy_validate_storage = $form_state->get('copy_validate');
    $config = $copy_validate_storage['config'];
    $scheme = $copy_validate_storage['scheme'];
    \Drupal::service('s3fs')->copyFileSystemToS3($config, $scheme);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We use different submits instead default submit.
  }

}
