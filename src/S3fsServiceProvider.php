<?php

namespace Drupal\s3fs;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Site\Settings;

/**
 * The stream wrapper class.
 *
 * In the docs for this class, anywhere you see "<scheme>", it can mean either
 * "s3" or "public", depending on which stream is currently being serviced.
 */
class S3fsServiceProvider implements ServiceModifierInterface {

  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    if (Settings::get('s3fs.use_s3_for_public')) {
      // Replace the public stream wrapper with S3fsStream.
      $container->getDefinition('stream_wrapper.public')
        ->setClass('Drupal\s3fs\StreamWrapper\PublicS3fsStream');
    }
    if (Settings::get('s3fs.use_s3_for_private') && $container->hasDefinition('stream_wrapper.private')) {
      // Replace the private stream wrapper with S3fsStream.
      $container->getDefinition('stream_wrapper.private')
        ->setClass('Drupal\s3fs\StreamWrapper\PrivateS3fsStream');
    }

    // Fix CSS static urls
    $container->getDefinition('asset.css.optimizer')
      ->setClass('Drupal\s3fs\S3fsCssOptimizer');
  }
}
