<?php

namespace Drupal\photoswipe;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Preprocess photoswipe images.
 */
class PhotoswipeResponsivePreprocessProcessor extends PhotoswipePreprocessProcessor {

  /**
   * {@inheritDoc}
   */
  protected function getRenderableImage($variables) {
    $item = $this->imageDTO->getItem();
    $settings = $this->imageDTO->getSettings();
    $image_style_store = $this->entityTypeManager->getStorage('image_style');
    $resp_image_store = $this->entityTypeManager->getStorage('responsive_image_style');
    $responsive_image_style = $resp_image_store->load($settings['photoswipe_thumbnail_style']);

    $cache_tags = [];
    $image_styles_to_load = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $image_style_store->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    $image = [
      '#theme' => 'responsive_image_formatter',
      '#item' => $item,
      '#item_attributes' => $item->_attributes,
      '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
      '#cache' => [
        'tags' => $cache_tags,
      ],
      '#style_name' => $settings['photoswipe_thumbnail_style'],
    ];

    $meta_a = CacheableMetadata::createFromRenderArray($image);
    $meta_b = CacheableMetadata::createFromObject($item->getEntity());
    $meta_a->merge($meta_b)->applyTo($image);

    if (isset($variables['delta']) && $variables['delta'] === 0 && !empty($settings['photoswipe_thumbnail_style_first'])) {
      $image['#style_name'] = $settings['photoswipe_thumbnail_style_first'];
    }

    // Render as a standard image if an image style is not given or "hide".
    if ($image['#style_name'] === 'hide') {
      $image['#theme'] = 'image';
    }

    return $image;
  }

}
