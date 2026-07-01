<?php
if (!defined('ABSPATH')) exit;

function polytranslate_ai_free_vs_pro_page()
{
  $is_premium = true;

  if ($is_premium) { ?>
    <div class="polytranslate-ai-free-vs-pro">
      <p>✅ <?php esc_html_e('You are using the Pro version. All features are unlocked.', 'polytranslate-ai'); ?></p>
    </div>
  <?php return;
  }

  $upgrade_url = ptai_fs()->get_upgrade_url();

  $free_features = [
    __('Translate Posts & Pages', 'polytranslate-ai'),
    __('Translate WooCommerce Products', 'polytranslate-ai'),
    __('Translate Custom Post Types', 'polytranslate-ai'),
    __('Translate Categories & Tags', 'polytranslate-ai'),
    __('Translate Custom Taxonomies', 'polytranslate-ai'),
    __('Gutenberg Block Content', 'polytranslate-ai'),
    __('Translate Link Targets', 'polytranslate-ai'),
    __('Preserve Original Publish Date', 'polytranslate-ai'),
    __('Preserve Original Author', 'polytranslate-ai'),
    __('Register Polylang Strings', 'polytranslate-ai'),
    __('OpenAI / DeepL / Google / OpenRouter', 'polytranslate-ai'),
  ];

  $pro_features = [
    __('Bulk Translation', 'polytranslate-ai'),
    __('Translate SEO Meta Titles and Descriptions', 'polytranslate-ai'),
    __('Custom Fields Translation (ACF, Meta Box, etc.)', 'polytranslate-ai'),
    __('Compatibility for Translating Elementor Pages', 'polytranslate-ai'),
    __('Polylang Strings Bulk Auto Translation', 'polytranslate-ai'),
    __('Priority Support', 'polytranslate-ai'),
  ];
  ?>
  <div class="polytranslate-ai-free-vs-pro">
    <div style="display: flex; align-items: center; justify-content: space-between;">
      <h1><?php esc_html_e('Free vs Pro Comparison', 'polytranslate-ai'); ?></h1>
      <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary button-large">
        <?php esc_html_e('Upgrade to Pro ➜', 'polytranslate-ai'); ?>
      </a>
    </div>
    <span><?php esc_html_e('Compare the features to find the best option for your website.', 'polytranslate-ai'); ?></span>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php esc_html_e('Feature', 'polytranslate-ai'); ?></th>
          <th><?php esc_html_e('Free', 'polytranslate-ai'); ?></th>
          <th><?php esc_html_e('Pro', 'polytranslate-ai'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($free_features as $feature) : ?>
          <tr>
            <td><?php echo esc_html($feature); ?></td>
            <td><span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span></td>
            <td><span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span></td>
          </tr>
        <?php endforeach; ?>

        <?php foreach ($pro_features as $feature) : ?>
          <tr>
            <td><?php echo esc_html($feature); ?></td>
            <td><span class="dashicons dashicons-minus" style="color:#999"></span></td>
            <td><span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php }
