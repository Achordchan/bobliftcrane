<?php
if (!defined('ABSPATH')) exit;

function polytranslate_ai_api_page()
{
  // Get current settings
  $api_engine = ptai_get_option('polytranslate_ai_api_options', 'api_engine', 'OpenRouter');
  $openrouter_api_key = ptai_get_option('polytranslate_ai_api_options', 'openrouter_api_key', '');
  $openrouter_model = ptai_get_option('polytranslate_ai_api_options', 'openrouter_model', 'google/gemini-2.5-flash-lite');
  $openrouter_temperature = ptai_get_option('polytranslate_ai_api_options', 'openrouter_temperature', '0.1');
  $openai_api_key = ptai_get_option('polytranslate_ai_api_options', 'openai_api_key', '');
  $openai_base_url = ptai_get_option('polytranslate_ai_api_options', 'openai_base_url', 'https://api.openai.com/v1');
  $openai_model = ptai_get_option('polytranslate_ai_api_options', 'openai_model', 'gpt-4o-mini');
  $openai_temperature = ptai_get_option('polytranslate_ai_api_options', 'openai_temperature', '0.1');
  $deepl_api_key = ptai_get_option('polytranslate_ai_api_options', 'deepl_api_key', '');
  $google_api_key = ptai_get_option('polytranslate_ai_api_options', 'google_api_key', '');
?>

  <form x-data="ptaiApiTest" method="post" action="options.php" class="polytranslate-ai-settings-form">
    <?php settings_fields('polytranslate_ai_api_options'); ?>

    <table class="form-table" id="polytranslate_ai_api_form_table">

      <!-- API Engine Selection -->
      <tr>
        <th scope="row">
          <label for="polytranslate_ai_api_engine"><?php esc_html_e('Choose API Engine', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <select id="polytranslate_ai_api_engine" name="polytranslate_ai_api_options[api_engine]">
            <option value="OpenRouter" <?php selected($api_engine, 'OpenRouter'); ?>>OpenRouter</option>
            <option value="OpenAI" <?php selected($api_engine, 'OpenAI'); ?>>OpenAI</option>
            <option value="DeepL" <?php selected($api_engine, 'DeepL'); ?>>DeepL</option>
            <option value="Google" <?php selected($api_engine, 'Google'); ?>>Google</option>
          </select>
        </td>
      </tr>


      <!-- OpenRouter API Key and Model Configuration -->
      <tr class="section" data-section="OpenRouter" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openrouter_api_key"><?php esc_html_e('OpenRouter API Key', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input
            type="password"
            id="polytranslate_openrouter_api_key"
            name="polytranslate_ai_api_options[openrouter_api_key]"
            value="<?php echo esc_attr($openrouter_api_key); ?>"
            class="required regular-text">
          <span style="margin: 5px;"><a style="text-decoration: none;" target="_blank" href="https://openrouter.ai/keys"><?php esc_html_e('Get API Key', 'polytranslate-ai'); ?></a></span>
          <button class="button ptai-test-connections" @click.prevent="testApiConnection($event)" data-provider="OpenRouter"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Test Connection', 'polytranslate-ai'); ?></button>
          <span class="ptai-test-key-result" style="display: none;"></span>
          <p class="description"><?php esc_html_e('Your OpenRouter.ai API key (starts with sk-or-v1-...)', 'polytranslate-ai'); ?></p>
        </td>
      </tr>

      <tr class="section" data-section="OpenRouter" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openrouter_model"><?php esc_html_e('AI Model', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input type="text" id="polytranslate_openrouter_model" name="polytranslate_ai_api_options[openrouter_model]" class="required regular-text" value="<?php echo esc_attr($openrouter_model); ?>" />
          <span style="margin: 5px;"><a style="text-decoration: none;" target="_blank" href="https://openrouter.ai/rankings?category=translation#categories"><?php esc_html_e('Get AI Model', 'polytranslate-ai'); ?></a></span>
          <p class="description">
            <?php esc_html_e('Add the AI model for translations.', 'polytranslate-ai'); ?>
          </p>
        </td>
      </tr>

      <tr class="section" data-section="OpenRouter" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openrouter_temperature"><?php esc_html_e('Temperature', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input type="range" id="polytranslate_openrouter_temperature" name="polytranslate_ai_api_options[openrouter_temperature]" min="0" max="1" step="0.1" value="<?php echo esc_attr($openrouter_temperature) ?>" />
          <span class="range-value" style="vertical-align: top;"><?php echo esc_html($openrouter_temperature) ?></span>
          <p class="description">
            <?php esc_html_e('• 0 - Zero creativity; strict terminology adherence (Legal/Medical/Financial)', 'polytranslate-ai'); ?><br>
            <?php esc_html_e('• 0.1 - Very low variation; literal translations (Technical docs, UI text)', 'polytranslate-ai'); ?><br>
            <?php esc_html_e('• 0.2 - Balanced accuracy + readability (General content, news articles)', 'polytranslate-ai'); ?><br>
            <?php esc_html_e('• 0.3 - Natural flow; contextual adaptation (Blog posts, narratives)', 'polytranslate-ai'); ?>
          </p>
        </td>
      </tr>


      <!-- OpenAI API Key Configuration -->
      <tr class="section" data-section="OpenAI" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openai_api_key"><?php esc_html_e('OpenAi API Key', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input
            type="password"
            value="<?php echo esc_attr($openai_api_key); ?>"
            name="polytranslate_ai_api_options[openai_api_key]"
            id="polytranslate_openai_api_key"
            class="required regular-text">
          <span style="margin: 5px;"><a style="text-decoration: none;" target="_blank" href="https://platform.openai.com/account/api-keys"><?php esc_html_e('Get API Key', 'polytranslate-ai'); ?></a></span>
          <button class="button ptai-test-connections" @click.prevent="testApiConnection($event)" data-provider="OpenAI"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Test Connection', 'polytranslate-ai'); ?></button>
          <span class="ptai-test-key-result" style="display: none;"></span>
          <p class="description"><?php esc_html_e('Your OpenAI API key (starts with sk-...)', 'polytranslate-ai'); ?></p>
        </td>
      </tr>

      <tr class="section" data-section="OpenAI" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openai_base_url"><?php esc_html_e('OpenAI 兼容 Base URL', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input type="url" id="polytranslate_openai_base_url" name="polytranslate_ai_api_options[openai_base_url]" class="regular-text" value="<?php echo esc_attr($openai_base_url); ?>" placeholder="https://api.openai.com/v1" />
          <p class="description">
            <?php esc_html_e('填写官方 OpenAI 地址，或任何兼容 OpenAI 的中转地址，例如 https://api.example.com/v1。', 'polytranslate-ai'); ?>
          </p>
        </td>
      </tr>

      <tr class="section" data-section="OpenAI" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openai_model"><?php esc_html_e('AI Model', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input type="text" id="polytranslate_openai_model" name="polytranslate_ai_api_options[openai_model]" class="required regular-text" value="<?php echo esc_attr($openai_model); ?>" />
          <span style="margin: 5px;"><a style="text-decoration: none;" target="_blank" href="https://platform.openai.com/docs/models"><?php esc_html_e('Get AI Model', 'polytranslate-ai'); ?></a></span>
          <p class="description">
            <?php esc_html_e('Add the AI model for translations.', 'polytranslate-ai'); ?>
          </p>
        </td>
      </tr>

      <tr class="section" data-section="OpenAI" style="display: none;">
        <th scope="row">
          <label for="polytranslate_openai_temperature"><?php esc_html_e('Temperature', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input type="range" id="polytranslate_openai_temperature" name="polytranslate_ai_api_options[openai_temperature]" min="0" max="1" step="0.1" value="<?php echo esc_attr($openai_temperature) ?>" />
          <span class="range-value" style="vertical-align: top;"><?php echo esc_html($openai_temperature) ?></span>
          <p class="description">
            <?php esc_html_e('• 0 - Zero creativity; strict terminology adherence (Legal/Medical/Financial)', 'polytranslate-ai'); ?><br>
            <?php esc_html_e('• 0.1 - Very low variation; literal translations (Technical docs, UI text)', 'polytranslate-ai'); ?><br>
            <?php esc_html_e('• 0.2 - Balanced accuracy + readability (General content, news articles)', 'polytranslate-ai'); ?><br>
            <?php esc_html_e('• 0.3 - Natural flow; contextual adaptation (Blog posts, narratives)', 'polytranslate-ai'); ?>
          </p>
        </td>
      </tr>

      <!-- DeepL API Key Configuration -->
      <tr class="section" data-section="DeepL" style="display: none;">
        <th scope="row">
          <label for="polytranslate_deepl_api_key"><?php esc_html_e('DeepL API Key', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input
            type="password"
            value="<?php echo esc_attr($deepl_api_key); ?>"
            name="polytranslate_ai_api_options[deepl_api_key]"
            id="polytranslate_deepl_api_key"
            class="required regular-text">
          <span style="margin: 5px;"><a style="text-decoration: none;" target="_blank" href="https://www.deepl.com/translator"><?php esc_html_e('Get API Key', 'polytranslate-ai'); ?></a></span>
          <button class="button ptai-test-connections" @click.prevent="testApiConnection($event)" data-provider="DeepL"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Test Connection', 'polytranslate-ai'); ?></button>
          <span class="ptai-test-key-result" style="display: none;"></span>
        </td>
      </tr>

      <!-- Google API Key Configuration -->
      <tr class="section" data-section="Google" style="display: none;">
        <th scope="row">
          <label for="polytranslate_google_api_key"><?php esc_html_e('Google Translate API Key', 'polytranslate-ai'); ?></label>
        </th>
        <td>
          <input
            type="password"
            value="<?php echo esc_attr($google_api_key); ?>"
            name="polytranslate_ai_api_options[google_api_key]"
            id="polytranslate_google_api_key"
            class="required regular-text">
          <span style="margin: 5px;"><a style="text-decoration: none;" target="_blank" href="https://cloud.google.com/translate/"><?php esc_html_e('Get API Key', 'polytranslate-ai'); ?></a></span>
          <button class="button ptai-test-connections" @click.prevent="testApiConnection($event)" data-provider="Google"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Test Connection', 'polytranslate-ai'); ?></button>
          <span class="ptai-test-key-result" style="display: none;"></span>
        </td>
      </tr>

    </table>

    <?php submit_button(); ?>
    <div class="notice notice-warning inline" style="margin: 15px 0 0 0; padding: 12px 15px; max-width:800px">
      <p>
        <strong><?php esc_html_e('⚠️ Important: Model Selection Tips', 'polytranslate-ai'); ?></strong><br><br>
        <?php esc_html_e('Avoid using thinking or reasoning models as they are designed for complex problem-solving and will cause slow responses or timeout errors during translation. This includes: DeepSeek R1, DeepSeek V4 Pro/Flash (thinking mode), Claude 3.7+ with reasoning, Gemini 2.5/3 Pro/Flash thinking variants, QwQ, any OpenAI o-series (o1, o3, o4-mini), GPT-5 Thinking variants, or any model with "thinking", "reasoning" or "R1" in its name. For OpenRouter and OpenAI, stick to standard fast models like GPT-4o mini or Gemini 2.5 Flash Lite.', 'polytranslate-ai'); ?>
      </p>
      <p>
        <strong><?php esc_html_e('🏠 Shared Hosting Users', 'polytranslate-ai'); ?></strong>:
        <?php esc_html_e('If your server has a limited AJAX timeout, AI models may not complete in time regardless of which model you choose. In this case we strongly recommend switching to Google Translate or DeepL from the API Engine selector above, as both are significantly faster and more reliable on shared hosting environments.', 'polytranslate-ai'); ?>
      </p>
    </div>

  </form>

<?php
}
