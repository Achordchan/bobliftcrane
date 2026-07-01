<?php
if (!defined('ABSPATH')) exit;
class PTAI_AI_Restrictor
{
  private  $isPro = false;
  private  $upgradeUrl = '';
  public function __construct()
  {
    $this->isPro = true;
    $this->upgradeUrl = ptai_fs()->get_upgrade_url();
    if (!$this->isPro) {
      add_action('admin_footer', [$this, 'showBulkButton']);
      add_action('admin_footer-languages_page_mlang_strings', [$this, 'showPolyLangStringsBulk']);
    }
  }
  public function showBulkButton()
  {
    $screen = get_current_screen();

    // Check if we're on any edit page (posts, pages, custom post types, or taxonomies)
    if ($screen && (strpos($screen->id, 'edit-') === 0 || strpos($screen->base, 'edit-tags') === 0)) {
?>
      <script type="text/javascript">
        jQuery(document).ready(function($) {
          // Function to add button
          function addCustomButton() {
            // Check if button already exists
            if ($('#ptai-free-ai-bulk-button').length) {
              return;
            }
            if (!$('.row-actions .missing_translation .missing-translation-flag').length) {
              return; // Ensure we only add if the missing translation flag is present
            }
            let button = '<a href="<?php echo esc_url($this->upgradeUrl); ?>" target="_blank" title="<?php esc_attr_e('Upgrade to Pro to unlock bulk translation', 'polytranslate-ai'); ?>" class="button" id="ptai-free-ai-bulk-button"><span class="dashicons dashicons-lock"></span><?php esc_html_e('Bulk Translate', 'polytranslate-ai'); ?></a>';

            // Try to add to existing actions div first
            if ($('.tablenav.top .alignleft.actions').length) {
              $('.tablenav.top .alignleft.actions').first().append(button);
              $('.tablenav.bottom .alignleft.actions').first().append(button);
            }
            // If no actions div exists, create one
            else if ($('.tablenav.top').length) {
              $('.tablenav.top').prepend('<div class="alignleft actions">' + button + '</div>');
              $('.tablenav.bottom').prepend('<div class="alignleft actions">' + button + '</div>');
            }

          }

          // Add button immediately
          addCustomButton();

          // Also add after a short delay to handle dynamic content
          setTimeout(addCustomButton, 100);
        });
      </script>
    <?php
    }
  }

  public function showPolyLangStringsBulk()
  { ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        const $table = $('table.wp-list-table').first();
        if (!$table.length) return;
        // ── Toolbar ────────────────────────────────────────────────────────
        $table.before(`
              <div id="ptai-toolbar-free">
                <strong>${ ptaiData.i18n.aiBulkTranslate }</strong>
                <label>
                  <input type="checkbox" id="ptai-overwrite" disabled> ${ ptaiData.i18n.OverwriteExisting }
                </label>
                <a href="<?php echo esc_url($this->upgradeUrl); ?>" target="_blank" title="<?php esc_attr_e('Upgrade to Pro to unlock bulk translation', 'polytranslate-ai'); ?>" class="button" id="ptai-free-ai-bulk-button"><span class="dashicons dashicons-lock"></span>${ ptaiData.i18n.BtnFill }</a>
              </div>
		      `);
      });
    </script>
<?php
  }
}
new PTAI_AI_Restrictor();
