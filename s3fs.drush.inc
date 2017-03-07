<?php

/**
 * @file
 * Defines a drush command that refreshes the S3 metadata cache.
 */
use Drupal\Core\Site\Settings;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function s3fs_drush_command() {
  $items = array();

  $items['s3fs-refresh-cache'] = array(
    'description' => dt('Refresh the S3 File System metadata cache.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('s3fs-rc'),
    'callback' => 'drush_s3fs_refresh_cache',
  );

  $items['s3fs-copy-local'] = array(
    'description' => dt('Copy local files from your public and/or private file system(s) into your S3 bucket, if s3fs is configured to take them over.'),
    'options' => array(
      'scheme' => dt('Limit the process to an specific scheme. E.g. (public or private), all by default.'),
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('s3fs-cl'),
    'callback' => 'drush_s3fs_copy_local',
    'examples' => array(
      'drush s3fs-copy-local' => dt('Copy local files from your public and/or private file system(s) into your S3 bucket.'),
      'drush s3fs-copy-local --scheme=public' => dt('Copy local files only from your public file system into your S3 bucket.'),
    ),
  );

  return $items;
}

/**
 * Refreshes the file metadata cache.
 */
function drush_s3fs_refresh_cache() {
  $config = \Drupal::config('s3fs.settings')->get();

  if (!\Drupal::service('s3fs')->validate($config)) {
    return drush_set_error(
      dt('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
    );
  }

  \Drupal::service('s3fs')->refreshCache($config);
}

/**
 * Copies all files from the local public/private filesystem folders into S3.
 *
 * @param string $scheme
 *
 * @return void|int
 */
function drush_s3fs_copy_local($scheme = 'all') {
  drush_log(dt('You are going to copy @scheme scheme(s).', array('@scheme' => $scheme)), LogLevel::OK);
  drush_log(dt('You should have read "Copy local files to S3" section in README.txt.'), LogLevel::WARNING);
  drush_print(dt('This command only is useful if you have or you are going to have enabled s3 for public/private in your setting.php'));

  if (!drush_confirm('Are you sure?')) {
    return drush_user_abort();
  }

  $config = \Drupal::config('s3fs.settings')->get();
  if (!\Drupal::service('s3fs')->validate($config)) {
    return drush_set_error(
      dt('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
    );
  }

  if ($scheme == 'all' || $scheme == 'public') {
    drush_log(dt('Starting @scheme scheme', array('@scheme' => 'public')), LogLevel::OK);
    \Drupal::service('s3fs')->copyFileSystemToS3($config, 'public');
  }

  if ($scheme == 'all' || $scheme == 'private') {
    if (Settings::get('file_private_path')) {
      drush_log(dt('Starting @scheme scheme', array('@scheme' => 'private')), LogLevel::OK);
      \Drupal::service('s3fs')->copyFileSystemToS3($config, 'private');
    }
    else {
      drush_log(dt('Scheme @scheme is not properly configured, you must enable this scheme in your settings.php', array('@scheme' => 'private')), LogLevel::WARNING);
    }
  }
}
