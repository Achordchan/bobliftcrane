<?php
if (!defined('ABSPATH')) exit;


function polytranslate_ai_translation_settings_page()
{
  // Get current settings
  $post_status = ptai_get_option('polytranslate_ai_options', 'post_status', 'publish');
  $translate_seo_meta_descriptions = ptai_get_option('polytranslate_ai_options', 'translate_seo_meta_descriptions', 0);
  $translate_seo_meta_titles = ptai_get_option('polytranslate_ai_options', 'translate_seo_meta_titles', 0);
  $enable_bulk_translation = ptai_get_option('polytranslate_ai_options', 'enable_bulk_translation', 0);
  $translate_categories = ptai_get_option('polytranslate_ai_options', 'translate_categories', 1);
  $translate_tags = ptai_get_option('polytranslate_ai_options', 'translate_tags', 1);
  $translate_custom_taxonomies = ptai_get_option('polytranslate_ai_options', 'translate_custom_taxonomies', 1);
  $translate_link_targets = ptai_get_option('polytranslate_ai_options', 'translate_link_targets', 1);
  $original_publish_date = ptai_get_option('polytranslate_ai_options', 'original_publish_date', 0);
  $original_publish_author = ptai_get_option('polytranslate_ai_options', 'original_publish_author', 0);
  $bulk_translate_polylang_strings = ptai_get_option('polytranslate_ai_options', 'bulk_translate_polylang_strings', 0);
  $premium = true;
  $upgradeUrl = ptai_fs()->get_upgrade_url();
?>

  <form method="post" action="options.php" class="polytranslate-ai-settings-form">
    <?php settings_fields('polytranslate_ai_options'); ?>

    <table class="form-table">
      <!-- Translated Post Status -->
      <tr>
        <th scope="row">
          <label
            for="polytranslate_ai_post_status"><?php esc_html_e('Translated Post Status', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <select id="polytranslate_ai_post_status" name="polytranslate_ai_options[post_status]">
            <option value="publish" <?php selected($post_status, 'publish'); ?>>
              <?php esc_html_e('Publish', 'polytranslate-ai'); ?></option>
            <option value="pending" <?php selected($post_status, 'pending'); ?>>
              <?php esc_html_e('Pending Review', 'polytranslate-ai'); ?></option>
            <option value="draft" <?php selected($post_status, 'draft'); ?>>
              <?php esc_html_e('Draft', 'polytranslate-ai'); ?></option>
          </select>
          <p class="description"><?php esc_html_e('Status for newly translated posts', 'polytranslate-ai'); ?></p>
        </td>
      </tr>
      <!-- Enable Features -->
      <tr>
        <th scope="row">
          <label><?php esc_html_e('Enable Features', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <!-- Translate Categories -->
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch" id="polytranslate_ai_translate_categories"
                name="polytranslate_ai_options[translate_categories]" <?php checked($translate_categories, 1); ?> />
              <?php esc_html_e('Translate Categories', 'polytranslate-ai'); ?>
            </label>
          </p>
          <!-- Translate Tags -->
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch" id="polytranslate_ai_translate_tags"
                name="polytranslate_ai_options[translate_tags]" <?php checked($translate_tags, 1); ?> />
              <?php esc_html_e('Translate Tags', 'polytranslate-ai'); ?>
            </label>
          </p>
          <!-- Translate Custom Taxonomies -->
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch"
                id="polytranslate_ai_translate_custom_taxonomies"
                name="polytranslate_ai_options[translate_custom_taxonomies]"
                <?php checked($translate_custom_taxonomies, 1); ?> />
              <?php esc_html_e('Translate Custom Taxonomies', 'polytranslate-ai'); ?>
            </label>
          </p>
          <!-- Translate Link Targets -->
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch" id="polytranslate_ai_translate_link_targets"
                name="polytranslate_ai_options[translate_link_targets]" <?php checked($translate_link_targets, 1); ?> />
              <?php esc_html_e('Translate Link Targets', 'polytranslate-ai'); ?>
            </label>
            <span style="vertical-align: middle;  color: #515962;"
              title="<?php esc_html_e('Adjust links in posts so they point to the translated content', 'polytranslate-ai'); ?>"
              class="dashicons dashicons-info"></span>
          </p>
          <!-- Use original publish date -->
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch" id="polytranslate_ai_original_publish_date"
                name="polytranslate_ai_options[original_publish_date]" <?php checked($original_publish_date, 1); ?> />
              <?php esc_html_e('Preserve Original Date', 'polytranslate-ai'); ?>
            </label>
            <span style="vertical-align: middle;  color: #515962;"
              title="<?php esc_html_e('Keep the original publish date', 'polytranslate-ai'); ?>"
              class="dashicons dashicons-info"></span>
          </p>
          <!-- Use original publish author -->
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch" id="polytranslate_ai_original_publish_author"
                name="polytranslate_ai_options[original_publish_author]" <?php checked($original_publish_author, 1); ?> />
              <?php esc_html_e('Preserve Original Author', 'polytranslate-ai'); ?>
            </label>
            <span style="vertical-align: middle;  color: #515962;"
              title="<?php esc_html_e('Keep the original author', 'polytranslate-ai'); ?>"
              class="dashicons dashicons-info"></span>
          </p>
          <!-- Enable Bulk Translation -->
          <p>
            <label>
              <input <?php if (!$premium) echo 'disabled'; ?> type="checkbox" class="checkbox-toggle-switch"
                id="polytranslate_ai_enable_bulk_translation" name="polytranslate_ai_options[enable_bulk_translation]"
                <?php checked($enable_bulk_translation, 1); ?> />
              <?php esc_html_e('Bulk Translation', 'polytranslate-ai'); ?>
            </label>
            <span style="vertical-align: middle; color: #515962;"
              title="<?php esc_html_e('When using Bulk Translation for Categories, Tags, or Custom Taxonomies, you must translate parent terms first before translating their child terms.', 'polytranslate-ai'); ?>"
              class="dashicons dashicons-info"></span>
            <?php if (!$premium) echo '<a style="text-decoration: none;" href="' . esc_url($upgradeUrl) . '" title="' . esc_attr__('Upgrade to Pro to unlock', 'polytranslate-ai') . '"><span class="dashicons dashicons-lock"></span></a>'; ?>

          </p>
          <!-- Translate SEO Meta Descriptions -->
          <p>
            <label>
              <input <?php if (!$premium) echo 'disabled'; ?> type="checkbox" class="checkbox-toggle-switch"
                id="polytranslate_ai_translate_seo_meta_descriptions"
                name="polytranslate_ai_options[translate_seo_meta_descriptions]"
                <?php checked($translate_seo_meta_descriptions, 1); ?> />
              <?php esc_html_e('SEO Meta Descriptions Translation', 'polytranslate-ai'); ?>
            </label>
            <?php if (!$premium) echo '<a style="text-decoration: none;" href="' . esc_url($upgradeUrl) . '" title="' . esc_attr__('Upgrade to Pro to Unlock', 'polytranslate-ai') . '"><span class="dashicons dashicons-lock"></span></a>'; ?>
          </p>
          <!-- Translate SEO Meta Titles -->
          <p>
            <label>
              <input <?php if (!$premium) echo 'disabled'; ?> type="checkbox" class="checkbox-toggle-switch"
                id="polytranslate_ai_translate_seo_meta_titles"
                name="polytranslate_ai_options[translate_seo_meta_titles]"
                <?php checked($translate_seo_meta_titles, 1); ?> />
              <?php esc_html_e('SEO Meta Titles Translation', 'polytranslate-ai'); ?>
            </label>
            <?php if (!$premium) echo '<a style="text-decoration: none;" href="' . esc_url($upgradeUrl) . '" title="' . esc_attr__('Upgrade to Pro to Unlock', 'polytranslate-ai') . '"><span class="dashicons dashicons-lock"></span></a>'; ?>
          </p>
          <!-- Polylang Strings Bulk Translation -->
          <p>
            <label>
              <input <?php if (!$premium) echo 'disabled'; ?> type="checkbox" class="checkbox-toggle-switch"
                id="polytranslate_ai_bulk_translate_polylang_strings"
                name="polytranslate_ai_options[bulk_translate_polylang_strings]"
                <?php checked($bulk_translate_polylang_strings, 1); ?> />
              <?php esc_html_e('Polylang Strings Bulk Translation', 'polytranslate-ai'); ?>
            </label>
            <span style="vertical-align: middle; color: #515962;"
              title="<?php esc_html_e('When enabled, you can bulk translate Polylang strings from the Translatable Strings tab.', 'polytranslate-ai'); ?>"
              class="dashicons dashicons-info"></span>
            <?php if (!$premium) echo '<a style="text-decoration: none;" href="' . esc_url($upgradeUrl) . '" title="' . esc_attr__('Upgrade to Pro to Unlock', 'polytranslate-ai') . '"><span class="dashicons dashicons-lock"></span></a>'; ?>
          </p>
        </td>
      </tr>

    </table>


    <?php submit_button(); ?>
  </form>
<?php
}
