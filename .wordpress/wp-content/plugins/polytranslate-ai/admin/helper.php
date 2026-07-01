<?php
if (!defined('ABSPATH')) exit;

// returns array of translated categories
function ptai_categories_relations($id, $targetLang, $postType = 'post')
{
  $catLang = [];

  if ($postType != 'post' && $postType != 'page') { // for custom post types
    $taxonomies_obj = get_object_taxonomies($postType);

    foreach ($taxonomies_obj as $tax) {
      $term_all =  wp_get_post_terms($id, $tax, ['fields' => 'ids']);
      foreach ($term_all as $term) {
        $targetTerm = ptai_boblift_get_or_create_term_translation($term, $targetLang);
        if ($targetTerm > 0) {
          $catLang[] = [$targetLang => $targetTerm];
        }
      }
    }
  } else {
    foreach (wp_get_post_categories($id) as $cat) {
      $targetTerm = ptai_boblift_get_or_create_term_translation($cat, $targetLang);
      if ($targetTerm > 0) {
        $catLang[] = [$targetLang => $targetTerm];
      }
    }
  }

  $values = array_column($catLang, $targetLang);
  return $values; //return empty array if no translations found
}


// This function is used to get the translated tags of a post
function ptai_tags_relations($id, $targetLang)
{
  $catLang = [];
  foreach (array_column(wp_get_post_tags($id), 'term_id') as $cat) {
    $targetTerm = ptai_boblift_get_or_create_term_translation($cat, $targetLang);
    if ($targetTerm > 0) {
      $catLang[] = [$targetLang => $targetTerm];
    }
  }
  $values = array_column($catLang, $targetLang);
  return $values; //return empty array if no translations found
}

// This function is used to get the translated parent categoryies
function ptai_parent_category_relations($id, $targetLang) // return parent category translated id or 0 if no parent
{
  $parent = get_term($id)->parent;
  if ($parent) {
    return ptai_boblift_get_or_create_term_translation($parent, $targetLang);
  } else {
    return 0; // return 0 if no parent category
  }
}

function ptai_boblift_get_or_create_term_translation($termId, $targetLang)
{
  if (!function_exists('pll_get_term_translations') || !function_exists('pll_set_term_language') || !function_exists('pll_save_term_translations')) {
    return 0;
  }

  $translations = pll_get_term_translations($termId);
  if (!empty($translations[$targetLang])) {
    return (int) $translations[$targetLang];
  }

  $sourceTerm = get_term($termId);
  if (!$sourceTerm || is_wp_error($sourceTerm)) {
    return 0;
  }

  $sourceLang = function_exists('pll_get_term_language') ? (string) pll_get_term_language($termId) : '';
  $translatedName = $sourceTerm->name;
  $translatedDescription = $sourceTerm->description;

  if (function_exists('ptai_call_translation_ai')) {
    $texts = [$sourceTerm->name];
    if (trim((string) $sourceTerm->description) !== '') {
      $texts[] = $sourceTerm->description;
    }
    $result = ptai_call_translation_ai($texts, $targetLang, $sourceLang);
    if (is_array($result) && isset($result['success']) && $result['success'] === false) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[PTAI BOB-LIFT] Term translation failed for term ' . $termId . ': ' . (string) ($result['error'] ?? 'Unknown error'));
      }
    } elseif (is_array($result)) {
      $translatedName = isset($result[0]) ? (string) $result[0] : $translatedName;
      $translatedDescription = isset($result[1]) ? (string) $result[1] : $translatedDescription;
    }
  }

  $parent = 0;
  if ((int) $sourceTerm->parent > 0) {
    $parent = ptai_boblift_get_or_create_term_translation((int) $sourceTerm->parent, $targetLang);
  }

  $slug = sanitize_title($translatedName);
  if ($slug === '') {
    $slug = sanitize_title($sourceTerm->slug . '-' . $targetLang);
  }
  if (term_exists($slug, $sourceTerm->taxonomy)) {
    $slug = sanitize_title($sourceTerm->slug . '-' . $targetLang);
  }

  $newTerm = wp_insert_term($translatedName, $sourceTerm->taxonomy, [
    'slug' => $slug,
    'description' => $translatedDescription,
    'parent' => $parent,
  ]);

  if (is_wp_error($newTerm) || empty($newTerm['term_id'])) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      $message = is_wp_error($newTerm) ? $newTerm->get_error_message() : 'Term ID not returned';
      error_log('[PTAI BOB-LIFT] Could not create translated term for ' . $termId . ': ' . $message);
    }
    return 0;
  }

  $newTermId = (int) $newTerm['term_id'];
  pll_set_term_language($newTermId, $targetLang);
  $translations[$targetLang] = $newTermId;
  pll_save_term_translations($translations);

  $meta = get_term_meta($termId);
  foreach ($meta as $key => $values) {
    foreach ($values as $value) {
      add_term_meta($newTermId, $key, maybe_unserialize($value));
    }
  }

  if (function_exists('ptai_boblift_translate_term_meta')) {
    ptai_boblift_translate_term_meta($termId, $newTermId, $targetLang, $sourceLang, $sourceTerm->taxonomy);
  }

  return $newTermId;
}

// Get a setting from the plugin options
function ptai_get_option($optionName, $key, $default = '')
{
  $options = get_option($optionName, array());
  return isset($options[$key]) ? $options[$key] : $default;
}


// Set product type and variations for WooCommerce products
function ptai_set_product_type($productId, $newProductId)
{

  $productType = wc_get_product($productId)->get_type();
  wp_set_object_terms($newProductId, $productType, 'product_type');

  // For variable products, copy variations
  if ($productType === 'variable') {

    $source_product = wc_get_product($productId);
    $target_product = wc_get_product($newProductId);
    $target_product->set_attributes($source_product->get_attributes());
    $target_product->save();
  }
}




/**
 * Collect meta keys for posts and custom post types
 */
function ptai_get_meta_keys_for_cpt($post_type, $limit = 50)
{
  $posts = get_posts([
    'post_type'      => $post_type,
    'posts_per_page' => $limit,
    'fields'         => 'ids',
  ]);

  $keys = [];

  foreach ($posts as $post_id) {
    $meta = get_post_meta($post_id);
    $keys = array_merge($keys, array_keys($meta));
  }

  $keys = array_unique($keys);

  // Common prefixes and keys to exclude
  $excluded_prefixes = ['_wp_', '_edit_', '_menu_item_', '_yoast_', '_aioseo_', '_seopress_', '_genesis_', '_elementor_', '_wpb_', '_bbp_', '_wpml_'];

  $excluded_keys = [
    '_thumbnail_id',
    '_wp_page_template',
    '_wp_attached_file',
    '_wp_attachment_metadata',
    '_yoast_wpseo_metadesc',
    'rank_math_description',
    'rank_math_title',
    '_aioseo_description',
    '_seopress_titles_desc',
    '_genesis_description'
  ];

  return array_filter($keys, function ($key) use ($excluded_prefixes, $excluded_keys) {
    // Skip keys that start with excluded prefixes
    foreach ($excluded_prefixes as $prefix) {
      if (strpos($key, $prefix) === 0) {
        return false;
      }
    }

    // Skip specific excluded keys
    return !in_array($key, $excluded_keys, true);
  });
}

/**
 * Collect meta keys for taxonomies
 */
function ptai_get_meta_keys_for_taxonomy($taxonomy, $limit = 50)
{
  $terms = get_terms([
    'taxonomy'   => $taxonomy,
    'hide_empty' => false,
    'number'     => $limit,
    'fields'     => 'ids',
  ]);

  if (is_wp_error($terms)) {
    return [];
  }

  $keys = [];

  foreach ($terms as $term_id) {
    $meta = get_term_meta($term_id);
    $keys = array_merge($keys, array_keys($meta));
  }

  $keys = array_unique($keys);

  // Common prefixes and keys to exclude for taxonomies
  $excluded_prefixes = ['_wp_', '_yoast_', '_aioseo_', '_seopress_', '_genesis_'];

  $excluded_keys = [
    'order',
    'count',
    'term_order',
    'product_count_product_cat',
    '_yoast_wpseo_metadesc',
    'rank_math_description',
    'rank_math_title',
    '_aioseo_description',
    '_seopress_titles_desc',
    '_genesis_description'
  ];

  return array_filter($keys, function ($key) use ($excluded_prefixes, $excluded_keys) {
    // Skip keys that start with excluded prefixes
    foreach ($excluded_prefixes as $prefix) {
      if (strpos($key, $prefix) === 0) {
        return false;
      }
    }

    // Skip specific excluded keys
    return !in_array($key, $excluded_keys, true);
  });
}
