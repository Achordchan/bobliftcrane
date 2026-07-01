<?php
if (!defined('ABSPATH')) exit;

require_once PTAI_ADMIN_DIR . 'helper.php';
/**
 * Add missing translation flag to row actions for posts, pages, categories, tags, and custom taxonomies
 */
$ptai_translate_categories = ptai_get_option('polytranslate_ai_options', 'translate_categories', 1);
$ptai_translate_tags = ptai_get_option('polytranslate_ai_options', 'translate_tags', 1);
$ptai_translate_custom_taxonomies = ptai_get_option('polytranslate_ai_options', 'translate_custom_taxonomies', 1);

add_filter('post_row_actions', 'polytranslate_ai_add_missing_translation_flag_to_row_actions', 10, 2);
add_filter('page_row_actions', 'polytranslate_ai_add_missing_translation_flag_to_row_actions', 10, 2);

if ($ptai_translate_categories) {
    add_filter('category_row_actions', 'polytranslate_ai_add_missing_translation_flag_to_row_actions', 10, 2);
}

if ($ptai_translate_tags) {
    add_filter('post_tag_row_actions', 'polytranslate_ai_add_missing_translation_flag_to_row_actions', 10, 2);
}

// Dynamically add to ALL custom taxonomies
if ($ptai_translate_custom_taxonomies) {
    add_action('admin_init', 'polytranslate_ai_add_translation_flag_to_custom_taxonomies');
  }


function polytranslate_ai_add_translation_flag_to_custom_taxonomies()
{
  // Get custom taxonomies
  $taxonomies = get_taxonomies(['_builtin' => false], 'names');

  foreach ($taxonomies as $taxonomy) {
    add_filter($taxonomy . '_row_actions', 'polytranslate_ai_add_missing_translation_flag_to_row_actions', 10, 2);
  }
}


function polytranslate_ai_add_missing_translation_flag_to_row_actions($actions, $post)
{

  // Only show translation actions if user can edit posts
  if (!current_user_can('edit_posts')) {
    return $actions;
  }

  // Check if Polylang functions exist
  if (function_exists('pll_the_languages') && function_exists('pll_get_post_language')) {

    $languages = pll_the_languages(['raw' => true, 'hide_if_empty' => false, 'show_flags' => true]);


    $langSlug = [];
    $langFlag = [];
    $langName = [];


    if (isset($post->ID)) { // Work on posts
      $sourceLang = pll_get_post_language($post->ID); // Get default language slug
      if (!$sourceLang) return $actions; // If no source language, return actions
      foreach ($languages as $lang) {
        // Check if the translation is missing
        if (!pll_get_post($post->ID, $lang['slug'])) { // Exclude that have translation
          $langFlag[] = $lang['flag'];
          $langSlug[] = $lang['slug'];
          $langName[] = $lang['name'];
        }
      }

      $item = "post_type={$post->post_type}&id={$post->ID}";
    } elseif (isset($post->term_id)) { // Work on Taxonomies
      $sourceLang = pll_get_term_language($post->term_id); // Get default language slug
      if (!$sourceLang) return $actions; // If no source language, return actions
      foreach ($languages as $lang) {
        // Check if the translation is missing
        if (!pll_get_term($post->term_id, $lang['slug'])) {
          $langFlag[] = $lang['flag'];
          $langSlug[] = $lang['slug'];
          $langName[] = $lang['name'];
        }
      }

      $item = "post_type={$post->taxonomy}&term_id={$post->term_id}";
    }

    $mapArr = array_map(function ($slug, $flag, $name) use ($item, $sourceLang) {
      return '<a title="' . $name . '" href="' . admin_url("admin-ajax.php?action=polytranslate_ai_translate_content&" . $item . "&source=" . $sourceLang . "&slug=" . $slug . "&_wpnonce=" . wp_create_nonce('polytranslate-ai-nonce')) . '"class="polytranslate-ai-translate-link" style="margin: 0 4px;">' . $flag . '</a>';
    }, $langSlug, $langFlag, $langName);

    $finalLang = implode('', $mapArr);
    $actions['missing_translation'] = '<span class="missing-translation-flag"><a>' . esc_html__('Translate', 'polytranslate-ai') . ':</a> ' . $finalLang . '</span>';
  }

  //-------------------------
  //   $elementor_data = get_post_meta($post->ID, '_elementor_data', true);

  // print_r ($elementor_data);
  // var_dump($post);


  // Show Meta Fields if enabled in settings
  $show_meta_fields = ptai_get_option('polytranslate_ai_advanced_options', 'show_meta_fields', 0);
  if ($show_meta_fields):
    switch ($post) {
      case $post instanceof WP_Post:
        $metaFields = get_post_meta($post->ID);
        break;
      case $post instanceof WP_Term:
        $metaFields = get_term_meta($post->term_id);
        break;
      default:
        $metaFields = get_post_meta($post->ID);
        break;
    }

    if (isset($metaFields) && !empty($metaFields)):
?>
      <div style="max-height:400px; overflow-y:auto; border:1px solid #ccd0d4; width:fit-content;">

        <table class="widefat striped polytranslate-ai-meta-table" style="border-collapse:collapse; width:100%;">
          <thead style="position:sticky; top:0; background:#f0f0f1; z-index:2;">
            <tr>
              <th><strong>Meta Key</strong></th>
              <th><strong>Meta Value</strong></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($metaFields as $key => $values) :
              if (in_array($key, ['_elementor_data', '_uag_page_assets', '_elementor_controls_usage'])) continue; ?>
              <?php foreach ($values as $value) : ?>
                <tr>
                  <td><?php echo esc_html($key); ?></td>
                  <td><?php echo esc_html($value); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
  <?php
    endif; // End if show meta fields
  endif;
  ?>

<?php
  return $actions;
}
?>