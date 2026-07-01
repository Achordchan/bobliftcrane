<?php
/**
 * Polytranslate AI - API Test Handler
 */

if(!defined('ABSPATH')) exit; // Exit if accessed directly.


class PTAI_Test_Api_Connection
{
  public function __construct()
  {
    add_action('wp_ajax_ptai_test_api_connection', [$this, 'ptai_test_api_connection']);
  }

  public function ptai_test_api_connection()
  {
    check_ajax_referer('ptai_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    $provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? ''));
    $api_key  = sanitize_text_field(wp_unslash($_POST['api_key'] ?? ''));
    $base_url = isset($_POST['base_url']) ? untrailingslashit(esc_url_raw(wp_unslash($_POST['base_url']))) : '';
    $model    = sanitize_text_field(wp_unslash($_POST['model'] ?? ''));

    if (empty($api_key)) {
      wp_send_json_error('API key is required');
    }

    $response = null;

    switch ($provider) {
      case 'OpenRouter':
        $response = wp_remote_get('https://openrouter.ai/api/v1/models', [
          'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
          ],
          'timeout' => 15,
        ]);
        break;

      case 'OpenAI':
        $model = $model ?: ptai_get_option('polytranslate_ai_api_options', 'openai_model', 'gpt-4o-mini');
        $response = wp_remote_post(ptai_openai_compatible_endpoint('/chat/completions', $base_url ?: null), [
          'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
          ],
          'body' => json_encode([
            'model' => $model,
            'messages' => [
              ['role' => 'user', 'content' => 'Return OK only.'],
            ],
            'max_tokens' => 4,
            'temperature' => 0,
          ]),
          'timeout' => 15,
        ]);
        break;

      case 'DeepL':
        // Determine which endpoint to use based on the API key suffix
        $deeplUrl = 'https://api.deepl.com/v2/usage';
        if (strpos($api_key, ':fx') !== false) {
          $deeplUrl = 'https://api-free.deepl.com/v2/usage';
        }
        $response = wp_remote_get($deeplUrl, [
          'headers' => [
            'Authorization' => 'DeepL-Auth-Key ' . $api_key,
          ],
          'timeout' => 15,
        ]);
        break;

      case 'Google':
        // Minimal translate call (costs almost nothing)
        $response = wp_remote_post('https://translation.googleapis.com/language/translate/v2?key=' . urlencode($api_key), [
          'headers' => ['Content-Type' => 'application/json'],
          'body'    => json_encode([
            'q'      => 'Test',
            'target' => 'en',
          ]),
          'timeout' => 15,
        ]);
        break;

      default:
        wp_send_json_error('Invalid provider');
    }

    if (is_wp_error($response)) {
      wp_send_json_error($response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code !== 200) {
      wp_send_json_error("HTTP $code - Check your API key and quota");
    }
    wp_send_json_success('Connection successful!');
  }
}

new PTAI_Test_Api_Connection();
