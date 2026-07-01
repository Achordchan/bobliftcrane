<?php
if (!defined('ABSPATH')) exit;

// Render the settings page
function polytranslate_ai_advanced_page()
{
  $args       = ['public' => true];
  $post_types = get_post_types($args, 'objects');
  $taxonomies = get_taxonomies($args, 'objects');
  $post_meta_fields = ptai_get_option('polytranslate_ai_advanced_options', 'translatable_meta_post_types', []);
  $taxonomy_meta_fields = ptai_get_option('polytranslate_ai_advanced_options', 'translatable_meta_taxonomies', []);
  $show_meta_fields = ptai_get_option('polytranslate_ai_advanced_options', 'show_meta_fields', 0);
  $items_to_scan = ptai_get_option('polytranslate_ai_advanced_options', 'items_to_scan', 100);
  $premium = true;
  $upgradeUrl = ptai_fs()->get_upgrade_url();
?>
  <!-- add note to the users this option will take effect only for pro users -->
  <?php if (!$premium): ?>
    <div x-cloak x-show="tab === 'advanced'" class="ptai-pro-feature-notice notice notice-warning">
      <p><strong><?php esc_html_e('Pro Feature Notice:', 'polytranslate-ai'); ?></strong>
        <?php esc_html_e('Custom fields translation is a premium feature available exclusively in Polytranslate AI Pro. Settings on this page will not take effect in the free version. Upgrade to unlock advanced translation capabilities for your custom fields.', 'polytranslate-ai'); ?>
        <a href="<?php echo esc_url($upgradeUrl); ?>" class="button button-secondary" style="margin-left: 0 10px;">
          <?php esc_html_e('Upgrade to Pro', 'polytranslate-ai'); ?>
        </a>
      </p>
    </div>
  <?php endif; ?>
  <h2><?php esc_html_e('Custom Fields Translation', 'polytranslate-ai'); ?></h2>

  <form method="post" action="options.php" class="polytranslate-ai-advanced-form">
    <?php
    settings_fields('polytranslate_ai_advanced_options');
    ?>

    <!-- Enable Show Meta Fields Translation -->
    <table class="form-table">
      <tr>
        <th scope="row">
          <label><?php esc_html_e('Advanced Settings', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <p>
            <label>
              <input type="checkbox" class="checkbox-toggle-switch" id="polytranslate_ai_show_meta_fields" name="polytranslate_ai_advanced_options[show_meta_fields]" <?php checked($show_meta_fields, 1); ?> />
              <?php esc_html_e('Show Meta Fields', 'polytranslate-ai'); ?>
            </label>
          </p>
          <p class="description"><?php esc_html_e('Enable to show meta fields in the post and term list tables for selecting translatable fields.', 'polytranslate-ai'); ?></p>
          <p>
            <label>
              <input style="max-width: 70px;" type="number" id="polytranslate_ai_items_to_scan" name="polytranslate_ai_advanced_options[items_to_scan]" min="10" step="10" value="<?php echo esc_attr($items_to_scan); ?>" />
              <?php esc_html_e('Items to Scan', 'polytranslate-ai'); ?>
            </label>
          </p>
          <p class="description"><?php esc_html_e('Number of posts/terms to scan for meta fields. Higher numbers may impact performance.', 'polytranslate-ai'); ?></p>
        </td>
      </tr>
    </table>
    <?php submit_button(); ?>

    <!-- Post Types Meta Fields Section -->
    <h3><?php esc_html_e('Post Type Meta Fields', 'polytranslate-ai'); ?></h3>
    <input type="search" style="max-width: fit-content;" id="polytranslate-ai-posts-meta-fields-search" data-column="all" placeholder="<?php esc_attr_e('Search', 'polytranslate-ai'); ?>">
    <div style="max-height:400px; overflow-y:auto; border:1px solid #ccd0d4; width:fit-content; margin:20px 0; visibility:hidden;" class="polytranslate-ai-table-wrapper">
      <table id="polytranslate-ai-posts-meta-fields-table" class="form-table wp-list-table widefat fixed striped" style="margin: 0;">
        <thead>
          <tr>
            <th class="group-text"><?php esc_html_e('Post Type', 'polytranslate-ai'); ?></th>
            <th><?php esc_html_e('Meta Field', 'polytranslate-ai'); ?></th>
            <th class="group-text"><?php esc_html_e('Translatable', 'polytranslate-ai'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($post_types as $post_type_obj):
            $meta_keys = ptai_get_meta_keys_for_cpt($post_type_obj->name, $items_to_scan);
            if (!empty($meta_keys)):
              foreach ($meta_keys as $key):
                $is_translatable = isset($post_meta_fields[$post_type_obj->name]) &&
                  in_array($key, $post_meta_fields[$post_type_obj->name], true);
          ?>
                <tr>
                  <td><?php echo esc_html($post_type_obj->name); ?></td>
                  <td><?php echo esc_html($key); ?></td>
                  <td>
                    <input type="checkbox" class="checkbox-toggle-switch"
                      name="polytranslate_ai_advanced_options[translatable_meta_post_types][<?php echo esc_attr($post_type_obj->name); ?>][]"
                      value="<?php echo esc_attr($key); ?>"
                      <?php checked($is_translatable, true); ?>>
                    <span class="visibility-status"></span>
                  </td>
                </tr>
          <?php endforeach;
            endif;
          endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php submit_button(); ?>

    <!-- Taxonomies Meta Fields Section -->
    <h3><?php esc_html_e('Taxonomy Meta Fields', 'polytranslate-ai'); ?></h3>
    <input type="search" style="max-width: fit-content;" id="polytranslate-ai-taxonomies-meta-fields-search" data-column="all" placeholder="<?php esc_attr_e('Search', 'polytranslate-ai'); ?>">
    <div style="max-height:400px; overflow-y:auto; border:1px solid #ccd0d4; width:fit-content; margin:20px 0; visibility:hidden;" class="polytranslate-ai-table-wrapper">
      <table id="polytranslate-ai-taxonomies-meta-fields-table" class="form-table wp-list-table widefat fixed striped" style="margin: 0;">
        <thead>
          <tr>
            <th class="group-text"><?php esc_html_e('Taxonomy Type', 'polytranslate-ai'); ?></th>
            <th><?php esc_html_e('Meta Field', 'polytranslate-ai'); ?></th>
            <th class="group-text"><?php esc_html_e('Translatable', 'polytranslate-ai'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($taxonomies as $taxonomy_obj):
            $meta_keys = ptai_get_meta_keys_for_taxonomy($taxonomy_obj->name, $items_to_scan);
            if (!empty($meta_keys)):
              foreach ($meta_keys as $key):
                $is_translatable = isset($taxonomy_meta_fields[$taxonomy_obj->name]) &&
                  in_array($key, $taxonomy_meta_fields[$taxonomy_obj->name], true);
          ?>
                <tr>
                  <td><?php echo esc_html($taxonomy_obj->name); ?></td>
                  <td><?php echo esc_html($key); ?></td>
                  <td>
                    <input type="checkbox" class="checkbox-toggle-switch"
                      name="polytranslate_ai_advanced_options[translatable_meta_taxonomies][<?php echo esc_attr($taxonomy_obj->name); ?>][]"
                      value="<?php echo esc_attr($key); ?>"
                      <?php checked($is_translatable, true); ?>>
                    <span class="visibility-status"></span>
                  </td>
                </tr>
          <?php endforeach;
            endif;
          endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php submit_button(); ?>
  </form>
<?php
}
