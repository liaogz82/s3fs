<?php

namespace Drupal\s3fs\EventSubscriber;

use Drupal\advagg\Asset\AssetOptimizationEvent;
use Drupal\advagg\Asset\SingleAssetOptimizerBase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to asset optimization events and update assets urls.
 */
class S3fsAdvAggSubscriber implements EventSubscriberInterface {

  /**
   * The minifier.
   *
   * @var \Drupal\advagg\Asset\SingleAssetOptimizerBase
   */
  protected $minifier;

  private $rewriteFileURIBasePath;

  /**
   * Construct the optimizer instance.
   *
   * @param \Drupal\advagg\Asset\SingleAssetOptimizerBase $minifier
   *   The minifier.
   */
  public function __construct(SingleAssetOptimizerBase $minifier) {
    $this->minifier = $minifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [AssetOptimizationEvent::CSS => ['updateUrls', 0]];
  }

  /**
   * Update asset urls to access static files that they aren't in S3 bucket.
   *
   * @param \Drupal\advagg\Asset\AssetOptimizationEvent $asset
   *   The asset optimization event.
   */
  public function updateUrls(AssetOptimizationEvent $asset) {
    $content = $this->processAssetContent($asset);
    $asset->setContent($content);
  }

  /**
   * Process asset content for make urls compatible.
   *
   * @param \Drupal\advagg\Asset\AssetOptimizationEvent $asset
   *
   * @return mixed
   *
   * @see \Drupal\Core\Asset\CssOptimizer::processFile()
   */
  public function processAssetContent(AssetOptimizationEvent $asset) {
    $content = $asset->getContent();
    $css_asset = $asset->getAsset();
    // Get the parent directory of this file, relative to the Drupal root.
    $css_base_path = substr($css_asset['data'], 0, strrpos($css_asset['data'], '/'));
    // Store base path.
    $this->rewriteFileURIBasePath = $css_base_path . '/';
    // Restore asset urls
    $content = str_replace('/' . $this->rewriteFileURIBasePath, "", $content);

    return preg_replace_callback('/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i', [$this, 'rewriteFileURI'], $content);
  }

  // @todo Use S3fsCssOptimizer
  public function rewriteFileURI($matches) {
    // Prefix with base and remove '../' segments where possible.
    $path = $this->rewriteFileURIBasePath . $matches[1];
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    return 'url(' . file_create_url($path) . ')';
  }

}
