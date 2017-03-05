<?php

namespace Drupal\s3fs;

/**
 * S3fs service interface.
 */
interface S3fsServiceInterface {

  /**
   * Validate the S3fs config.
   *
   * @param $config
   *   Array of configuration settings from which to configure the client.
   * @param $returnError
   *   Boolean, False by default.
   *
   * @return Boolean/array
   */
  function validate(array $config, $returnError = FALSE);

  /**
   * Sets up the S3Client object.
   *
   * @param $config
   *   Array of configuration settings from which to configure the client.
   *
   * @return \Aws\S3\S3Client
   *   The fully-configured S3Client object.
   *
   * @throws \Drupal\s3fs\S3fsException
   */
  function getAmazonS3Client($config);

  /**
   * Copies all the local files from the specified file system into S3.
   *
   * @param array $config
   *   An s3fs configuration array.
   * @param $scheme
   *   A variable defining which scheme (Public or Private) to copy.
   */
  function copyFileSystemToS3($config, $scheme);

  /**
   * Scans a given directory.
   *
   * @param $dir
   *   The directory to be scanned.
   *
   * @return array
   *   Array of file paths.
   */
  function dirScan($dir);

}
