<?php

namespace Drupal\s3fs\PathProcessor;

use Drupal\image\PathProcessor\PathProcessorImageStyles;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite image styles URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 *
 * This processor handles two different cases:
 * - public image styles: In order to allow the webserver to serve these files
 *   directly, the route is registered under the same path as the image style so
 *   it took over the first generation. Therefore the path processor converts
 *   the file path to a query parameter.
 * - private image styles: In contrast to public image styles, private
 *   derivatives are already using system/files/styles. Similar to public image
 *   styles, it also converts the file path to a query parameter.
 */
class S3fsPathProcessorImageStyles extends PathProcessorImageStyles {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $s3_path_prefix = '/s3/files/styles/';

    if (strpos($path, $s3_path_prefix) === 0) {
      // Strip out path prefix.
      $rest = preg_replace('|^' . preg_quote($s3_path_prefix, '|') . '|', '', $path);

      // Get the image style, scheme and path.
      if (substr_count($rest, '/') >= 2) {
        list($image_style, $scheme, $file) = explode('/', $rest, 3);
      }

      switch ($scheme) {
        case 'public':
          // Set the file as query parameter.
          $request->query->set('file', $file);
          $path = $s3_path_prefix . $image_style . '/' . $scheme;
          break;
        case 'private':
          $path_prefix = '/system/files/styles/';
          break;
      }

      if (isset($path_prefix)) {
          $path = str_replace($s3_path_prefix, $path_prefix, $path);
      }
    }

    return parent::processInbound($path, $request);
  }

}
