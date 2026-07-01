<?php
if (!defined('ABSPATH')) exit;

// Add admin submenu under Polylang with a later priority
add_action('admin_menu', 'polytranslate_ai_admin_menu');

function polytranslate_ai_admin_menu()
{
  add_menu_page(
    __('Polytranslate AI', 'polytranslate-ai'),
    __('Polytranslate AI', 'polytranslate-ai'),
    'manage_options',
    'polytranslate-ai',
    'polytranslate_ai_settings_page',
    'dashicons-admin-site-alt',
  );
  add_submenu_page(
    'polytranslate-ai',
    __('Polytranslate AI Settings', 'polytranslate-ai'), // page title
    __('Settings', 'polytranslate-ai'), // menu title
    'manage_options',
    'polytranslate-ai',
    'polytranslate_ai_settings_page'
  );
  // add_submenu_page(
  //   'polytranslate-ai',
  //   __('Polytranslate AI Settings', 'polytranslate-ai'), // page title
  //   __('Translation', 'polytranslate-ai'), // menu title
  //   'manage_options',
  //   'polytranslate-ai&tab=translation',
  //   'polytranslate_ai_settings_page'
  // );
  // add_submenu_page(
  //   'polytranslate-ai',
  //   __('Polytranslate AI Settings', 'polytranslate-ai'), // page title
  //   __('Advanced', 'polytranslate-ai'), // menu title
  //   'manage_options',
  //   'polytranslate-ai&tab=advanced',
  //   'polytranslate_ai_settings_page'
  // );
  // add_submenu_page(
  //   'polytranslate-ai',
  //   __('Polytranslate AI Settings', 'polytranslate-ai'), // page title
  //   __('Register Strings', 'polytranslate-ai'), // menu title
  //   'manage_options',
  //   'polytranslate-ai&tab=strings',
  //   'polytranslate_ai_translatable_strings_settings_page'
  // );
}

// Register settings
add_action('admin_init', 'polytranslate_ai_register_settings');

function polytranslate_ai_register_settings()
{
  register_setting('polytranslate_ai_api_options', 'polytranslate_ai_api_options', 'polytranslate_ai_sanitize_api_options');
  register_setting('polytranslate_ai_options', 'polytranslate_ai_options', 'polytranslate_ai_sanitize_options');
  register_setting('polytranslate_ai_advanced_options', 'polytranslate_ai_advanced_options', 'polytranslate_ai_sanitize_advanced_options');
  register_setting('polytranslate_ai_strings', 'polytranslate_ai_strings', 'polytranslate_ai_strings_sanitize_callback');
}


// Sanitization functions

function polytranslate_ai_strings_sanitize_callback($input)
{
  $sanitized = [];
  // Remove empty lines and sanitize each string
  $sanitized['translatable_strings'] = array_values(array_filter(array_map('sanitize_text_field', explode("\n", (string)($input['translatable_strings'] ?? '')))));

  return $sanitized;
}



function polytranslate_ai_sanitize_api_options($input)
{
  $sanitized = [];
  $sanitized['api_engine'] = sanitize_text_field($input['api_engine'] ?? 'OpenRouter');
  $sanitized['openrouter_api_key'] = sanitize_text_field($input['openrouter_api_key'] ?? '');
  $sanitized['openrouter_model'] = sanitize_text_field($input['openrouter_model'] ?? 'google/gemini-2.5-flash-lite');
  $sanitized['openrouter_temperature'] = sanitize_text_field($input['openrouter_temperature'] ?? '0.1');
  $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key'] ?? '');
  $openai_base_url = esc_url_raw($input['openai_base_url'] ?? 'https://api.openai.com/v1');
  $sanitized['openai_base_url'] = untrailingslashit($openai_base_url ?: 'https://api.openai.com/v1');
  $sanitized['openai_model'] = sanitize_text_field($input['openai_model'] ?? 'gpt-4o-mini');
  $sanitized['openai_temperature'] = sanitize_text_field($input['openai_temperature'] ?? '0.1');
  $sanitized['deepl_api_key'] = sanitize_text_field($input['deepl_api_key'] ?? '');
  $sanitized['google_api_key'] = sanitize_text_field($input['google_api_key'] ?? '');
  return $sanitized;
}

function polytranslate_ai_sanitize_options($input)
{
  $sanitized = [];
  $sanitized['post_status'] = sanitize_text_field($input['post_status'] ?? 'publish');
  $sanitized['translate_seo_meta_descriptions'] = isset($input['translate_seo_meta_descriptions']) ? 1 : 0;
  $sanitized['translate_seo_meta_titles'] = isset($input['translate_seo_meta_titles']) ? 1 : 0;
  $sanitized['enable_bulk_translation'] = isset($input['enable_bulk_translation']) ? 1 : 0;
  $sanitized['translate_categories'] = isset($input['translate_categories']) ? 1 : 0;
  $sanitized['translate_tags'] = isset($input['translate_tags']) ? 1 : 0;
  $sanitized['translate_custom_taxonomies'] = isset($input['translate_custom_taxonomies']) ? 1 : 0;
  $sanitized['translate_link_targets'] = isset($input['translate_link_targets']) ? 1 : 0;
  $sanitized['original_publish_date'] = isset($input['original_publish_date']) ? 1 : 0;
  $sanitized['original_publish_author'] = isset($input['original_publish_author']) ? 1 : 0;
  $sanitized['bulk_translate_polylang_strings'] = isset($input['bulk_translate_polylang_strings']) ? 1 : 0;
  return $sanitized;
}

function polytranslate_ai_sanitize_advanced_options($input)
{
  $sanitized = [];

  // Sanitize nested post types meta fields
  $sanitized['translatable_meta_post_types'] = [];
  foreach (($input['translatable_meta_post_types'] ?? []) as $post_type => $fields) {
    $sanitized['translatable_meta_post_types'][$post_type] = array_map(
      'sanitize_text_field',
      (array)$fields
    );
  }

  // Sanitize nested taxonomy meta fields
  $sanitized['translatable_meta_taxonomies'] = [];
  foreach (($input['translatable_meta_taxonomies'] ?? []) as $taxonomy => $fields) {
    $sanitized['translatable_meta_taxonomies'][$taxonomy] = array_map(
      'sanitize_text_field',
      (array)$fields
    );
  }

  $sanitized['show_meta_fields'] = isset($input['show_meta_fields']) ? 1 : 0;
  $sanitized['items_to_scan'] = isset($input['items_to_scan']) ? intval($input['items_to_scan']) : 50;

  return $sanitized;
}


// Polytranslate Settings page HTML
function polytranslate_ai_settings_page()
{
  settings_errors();
  $premium = true;
  $upgradeUrl = ptai_fs()->get_upgrade_url();
?>
  <div class="wrap" x-data="{ 
		tab: window.location.hash.substring(1) || 'api',
		init() {
			// Watch for form submissions
			this.$el.querySelectorAll('form').forEach(form => {
				form.addEventListener('submit', () => {
					sessionStorage.setItem('rame_active_tab', this.tab);
				});
			});
			
			// Restore tab after redirect
			const savedTab = sessionStorage.getItem('rame_active_tab');
			if (savedTab) {
				this.tab = savedTab;
				window.location.hash = savedTab;
				sessionStorage.removeItem('rame_active_tab');
			}
		}
	}">
    <h1><?php esc_html_e('Polytranslate AI', 'polytranslate-ai'); ?></h1>

    <h2 class="nav-tab-wrapper">
      <a @click="tab = 'api'" href="#api"
        class="nav-tab" :class="{ 'nav-tab-active' : tab==='api' }">
        <?php esc_html_e('API Settings', 'polytranslate-ai'); ?>
      </a>
      <a @click="tab = 'translation'" href="#translation"
        class="nav-tab" :class="{ 'nav-tab-active' : tab==='translation' }">
        <?php esc_html_e('Translation Settings', 'polytranslate-ai'); ?>
      </a>
      <a @click="tab = 'strings'" href="#strings"
        class="nav-tab" :class="{'nav-tab-active' : tab==='strings' }">
        <?php esc_html_e('Register Strings', 'polytranslate-ai'); ?>
      </a>
      <a @click="tab = 'advanced'; $nextTick(() => jQuery('.polytranslate-ai-table-wrapper > table').trigger('update'))" href="#advanced"
        class="nav-tab" :class="{ 'nav-tab-active' : tab==='advanced' }">
        <?php if (!$premium) {
          echo '<span title="' . esc_attr__('Upgrade to Pro to unlock advanced settings', 'polytranslate-ai') . '" class="dashicons dashicons-lock" style="color: #2271b1; vertical-align: text-bottom;"></span>';
        } ?>
        <?php esc_html_e('Advanced Settings', 'polytranslate-ai'); ?>
      </a>
      <a x-cloak x-show="ptaiData.isPro == false" @click="tab = 'free-vs-pro'" href="#free-vs-pro"
        class="nav-tab" :class="{ 'nav-tab-active' : tab==='free-vs-pro' }">
        <?php esc_html_e('Free vs Pro', 'polytranslate-ai'); ?>
      </a>

    </h2>

    <div x-cloak x-show="tab === 'api'"><?php polytranslate_ai_api_page(); ?></div>
    <div x-cloak x-show="tab === 'translation'"><?php polytranslate_ai_translation_settings_page(); ?></div>
    <div x-cloak x-show="tab === 'advanced'"><?php polytranslate_ai_advanced_page(); ?></div>
    <div x-cloak x-show="tab === 'strings'"><?php polytranslate_ai_translatable_strings_page(); ?></div>
    <div x-cloak x-show="tab === 'free-vs-pro'"><?php polytranslate_ai_free_vs_pro_page(); ?></div>
  </div>
<?php
}
