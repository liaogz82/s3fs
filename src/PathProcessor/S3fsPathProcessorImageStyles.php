<?php

namespace Drupal\s3fs\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
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
class S3fsPathProcessorImageStyles implements InboundPathProcessorInterface {

  const IMAGE_STYLE_PATH_PREFIX = '/s3/files/styles/';

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($this->isImageStylePath($path)) {
      // Strip out path prefix.
      $rest = preg_replace('|^' . preg_quote(static::IMAGE_STYLE_PATH_PREFIX, '|') . '|', '', $path);

      // Get the image style, scheme and path.
      if (substr_count($rest, '/') >= 2) {
        list($image_style, $scheme, $file) = explode('/', $rest, 3);

        switch ($scheme) {
          case 'public':
            // Set the file as query parameter.
            $request->query->set('file', $file);
            $path = static::IMAGE_STYLE_PATH_PREFIX . $image_style . '/' . $scheme;
            break;
          case 'private':
            $path_prefix = '/system/files/styles/';
            break;
        }

        if (isset($path_prefix)) {
            $path = str_replace(static::IMAGE_STYLE_PATH_PREFIX, $path_prefix, $path);
        }
      }
    }

    return $path;
  }

  private function isImageStylePath($path) {
    return strpos($path, static::IMAGE_STYLE_PATH_PREFIX) === 0;
  }

}
