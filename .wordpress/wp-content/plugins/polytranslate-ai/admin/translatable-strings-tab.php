<?php

/**
 * Translatable Strings 
 */
if (!defined('ABSPATH')) exit;

function polytranslate_ai_translatable_strings_page()
{
  // Get current settings
  $translatable_strings = ptai_get_option('polytranslate_ai_strings', 'translatable_strings', []);
?>
  <form method="post" action="options.php" class="polytranslate-ai-settings-form">
    <?php settings_fields('polytranslate_ai_strings'); ?>
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="polytranslate_ai_translatable_strings"><?php esc_html_e('Strings to Translate', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <textarea id="polytranslate_ai_translatable_strings" name="polytranslate_ai_strings[translatable_strings]" rows="10" cols="50"
            placeholder="<?php esc_attr_e('Enter one string per line.', 'polytranslate-ai'); ?>"><?php echo esc_textarea(implode("\n", $translatable_strings)); ?></textarea>
          <p class="description"><?php esc_html_e('Add or remove strings that will be registered with Polylang for translation. These strings will be available in Languages → Translations.', 'polytranslate-ai'); ?></p>
        </td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form>
<?php
}
