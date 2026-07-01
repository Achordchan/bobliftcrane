<?php
if (!defined('ABSPATH')) exit;

/**
 * Main translation function - accepts array of texts
 * Handles translation requests using clean JSON array format
 * Works with AI APIs (JSON mode) and traditional APIs (batch endpoints)
 * @param array|string $content Array of texts or single string (backward compat)
 * @param string $targetLang Target language code
 * @param string $sourceLang Source language code
 * @return array|string Array of translations or single string
 */

require_once PTAI_INCLUDES_DIR . 'lang-map.php';

function ptai_call_translation_ai($content, $targetLang, $sourceLang = '')
{
  // Map language codes to names for AI prompts
  $langMap = ptai_get_lang_map();
  $langName = $langMap[$targetLang] ?? $targetLang;
  $defaultLang = $langMap[$sourceLang] ?? $sourceLang;

  // Get settings from database
  $api_engine = ptai_get_option('polytranslate_ai_api_options', 'api_engine', 'OpenRouter');

  // Ensure content is array
  $is_batch = is_array($content);
  $texts = $is_batch ? $content : [$content];

  switch ($api_engine) {
    case 'OpenRouter':
      $apiKey = ptai_get_option('polytranslate_ai_api_options', 'openrouter_api_key', '');
      $model = ptai_get_option('polytranslate_ai_api_options', 'openrouter_model', 'google/gemini-2.5-flash-lite');
      $temperature = (float) ptai_get_option('polytranslate_ai_api_options', 'openrouter_temperature', '0.1');
      if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Please configure your OpenRouter AI API key'];
      }
      $result = ptai_call_openrouter_batch($apiKey, $model, $texts, $langName, $defaultLang, $temperature, $sourceLang);
      break;

    case 'OpenAI':
      $apiKey = ptai_get_option('polytranslate_ai_api_options', 'openai_api_key', '');
      $model = ptai_get_option('polytranslate_ai_api_options', 'openai_model', 'gpt-4o-mini');
      $temperature = (float) ptai_get_option('polytranslate_ai_api_options', 'openai_temperature', '0.1');
      if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Please configure your OpenAI AI API key'];
      }
      $result = ptai_call_openai_batch($apiKey, $model, $texts, $langName, $defaultLang, $temperature, $sourceLang);
      break;

    case 'DeepL':
      $apiKey = ptai_get_option('polytranslate_ai_api_options', 'deepl_api_key', '');
      if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Please configure your DeepL AI API key'];
      }
      $result = ptai_call_deepl_batch($apiKey, $texts, $targetLang, $sourceLang);
      break;

    case 'Google':
      $apiKey = ptai_get_option('polytranslate_ai_api_options', 'google_api_key', '');
      if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Please configure your Google AI API key'];
      }
      $result = ptai_call_google_translate_batch($apiKey, $texts, $targetLang, $sourceLang);
      break;

    default:
      return ['success' => false, 'error' => 'Unsupported API engine'];
  }

  // Return single string if input was string, array if input was array
  if (!$is_batch && is_array($result) && isset($result[0])) {
    return $result[0];
  }

  return $result;
}

/**
 * Get the system prompt for translation AI calls
 *
 * @param string $defaultLang Source language name
 * @param string $langName Target language name
 * @return string The formatted system prompt
 */
function ptai_get_translation_system_prompt($defaultLang, $langName, $sourceLang)
{
  $defaultLang = (!empty($sourceLang)) ? 'from ' . $defaultLang : '';

  return sprintf(
    'You are a professional translator. You MUST respond with ONLY valid JSON - nothing else.

MANDATORY RESPONSE FORMAT:
You MUST return a JSON object with exactly this structure:
{
  "translations": [translation1, translation2, ...]
}

TRANSLATION RULES:
1. Translate each input text %s to %s
2. Return translations in the exact same order as inputs
3. Preserve ALL HTML tags, attributes, and structure - only translate visible text
4. Do not translate HTML attributes like alt, title, aria-label, etc. - keep them unchanged
5. Do not translate placeholders like %%title%%, %%%%title%%%% and similar placeholders - keep them unchanged
6. Preserve WordPress shortcodes like [shortcode], [/shortcode], [shortcode attr="value"] unchanged
7. Do NOT add any text outside the JSON object - no markdown, no explanations
8. NEVER wrap the response in code blocks (no ```json markers)
9. Ensure the array has exactly the same number of elements as the input
10. Preserve brand names, product model names, URLs, file paths, CSS classes and technical codes unchanged
11. Return ONLY the JSON object, nothing else

CRITICAL: Your response must be valid JSON that can be parsed. Test it before responding.

Example:
Input: ["Hello", "<p>World</p>", "[gallery]"]
Output: {"translations": ["Bonjour", "<p>Monde</p>", "[gallery]"]}',
    $defaultLang,
    $langName
  );
}

/**
 * OpenRouter API - Batch translation with JSON mode
 */
function ptai_call_openrouter_batch($apiKey, $model, $texts, $langName, $defaultLang, $temperature = 0.1, $sourceLang = '')
{

  // phpcs:disable WordPress.PHP.DevelopmentFunctions

  $systemPrompt = ptai_get_translation_system_prompt($defaultLang, $langName, $sourceLang);

  $userContent = json_encode(array_values($texts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', [
    'headers' => [
      'Authorization' => 'Bearer ' . $apiKey,
      'Content-Type'  => 'application/json',
      'HTTP-Referer'  => home_url(),
      'X-OpenRouter-Title'       => 'PolyTranslate AI'
    ],
    'body' => json_encode([
      'model' => $model,
      'temperature' => $temperature,
      'response_format' => ['type' => 'json_object'],
      'messages' => [
        [
          'role'    => 'system',
          'content' => $systemPrompt
        ],
        [
          'role'    => 'user',
          'content' => $userContent
        ]
      ]
    ]),
    'timeout' => 240,
  ]);

  if (is_wp_error($response)) {
    return ['success' => false, 'error' => $response->get_error_message()];
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);

  if (!isset($body['choices'][0]['message']['content'])) {
    $error = $body['error']['message'] ?? 'Unknown error from OpenRouter';
    return ['success' => false, 'error' => $error];
  }

  $content = trim($body['choices'][0]['message']['content']);

  // Remove markdown code blocks if present (in case AI adds them despite instructions)
  $content = preg_replace('/^```json\s*/i', '', $content);
  $content = preg_replace('/^```\s*/i', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);
  $content = trim($content);

  $decoded = json_decode($content, true);

  if ($decoded === null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[PTAI OpenRouter JSON Error] ' . json_last_error_msg() . ' | Content: ' . mb_substr($content, 0, 500));
    }
    return ['success' => false, 'error' => 'Invalid JSON response from OpenRouter: ' . json_last_error_msg()];
  }

  if (!is_array($decoded) && !is_object($decoded)) {
    return ['success' => false, 'error' => 'Response is not an object or array from OpenRouter'];
  }

  // Convert object to array if needed
  $decoded = (array)$decoded;

  // PRIMARY: Check for the mandatory "translations" key
  if (isset($decoded['translations']) && is_array($decoded['translations'])) {
    $result = array_values($decoded['translations']);
    if (!empty($result)) {
      return $result;
    }
  }

  // FALLBACK: Check for "texts" key
  if (isset($decoded['texts']) && is_array($decoded['texts'])) {
    $result = array_values($decoded['texts']);
    if (!empty($result)) {
      return $result;
    }
  }

  // FALLBACK: If root is already an indexed array
  if (isset($decoded[0])) {
    return array_values($decoded);
  }

  // FALLBACK: Try to find any array in the object
  foreach ($decoded as $key => $value) {
    if (is_array($value) && !empty($value) && isset($value[0]) && is_string($value[0])) {
      return array_values($value);
    }
  }

  // If all else fails, log what we got
  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[PTAI OpenRouter Parse Error] Could not extract translations. Response keys: ' . implode(', ', array_keys($decoded)));
  }

  return ['success' => false, 'error' => 'Could not extract translation array from response. Expected {"translations": [...]} format.'];
}

/**
 * OpenAI API - Batch translation with JSON mode
 */
function ptai_call_openai_batch($apiKey, $model, $texts, $langName, $defaultLang, $temperature = 0.1, $sourceLang = '')
{
  $systemPrompt = ptai_get_translation_system_prompt($defaultLang, $langName, $sourceLang);

  $userContent = json_encode(array_values($texts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  $endpoint = ptai_openai_compatible_endpoint('/chat/completions');

  $requestBody = [
    'model' => $model,
    'temperature' => $temperature,
    'response_format' => ['type' => 'json_object'],
    'messages' => [
      [
        'role'    => 'system',
        'content' => $systemPrompt
      ],
      [
        'role'    => 'user',
        'content' => $userContent
      ]
    ]
  ];

  $response = wp_remote_post($endpoint, [
    'headers' => [
      'Authorization' => 'Bearer ' . $apiKey,
      'Content-Type'  => 'application/json'
    ],
    'body' => json_encode($requestBody),
    'timeout' => 240,
  ]);

  if (is_wp_error($response)) {
    return ['success' => false, 'error' => $response->get_error_message()];
  }

  if (ptai_openai_should_retry_without_json_mode($response)) {
    unset($requestBody['response_format']);
    $response = wp_remote_post($endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type'  => 'application/json'
      ],
      'body' => json_encode($requestBody),
      'timeout' => 240,
    ]);

    if (is_wp_error($response)) {
      return ['success' => false, 'error' => $response->get_error_message()];
    }
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);

  if (!isset($body['choices'][0]['message']['content'])) {
    $error = ptai_openai_response_error($response, $body);
    return ['success' => false, 'error' => $error];
  }

  $content = trim($body['choices'][0]['message']['content']);

  // Remove markdown code blocks if present (in case AI adds them despite instructions)
  $content = preg_replace('/^```json\s*/i', '', $content);
  $content = preg_replace('/^```\s*/i', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);
  $content = trim($content);

  $decoded = json_decode($content, true);

  if ($decoded === null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[PTAI OpenAI JSON Error] ' . json_last_error_msg() . ' | Content: ' . mb_substr($content, 0, 500));
    }
    return ['success' => false, 'error' => 'Invalid JSON response from OpenAI: ' . json_last_error_msg()];
  }

  if (!is_array($decoded) && !is_object($decoded)) {
    return ['success' => false, 'error' => 'Response is not an object or array from OpenAI'];
  }

  // Convert object to array if needed
  $decoded = (array)$decoded;

  // PRIMARY: Check for the mandatory "translations" key
  if (isset($decoded['translations']) && is_array($decoded['translations'])) {
    $result = array_values($decoded['translations']);
    if (!empty($result)) {
      return $result;
    }
  }

  // FALLBACK: Check for "texts" key
  if (isset($decoded['texts']) && is_array($decoded['texts'])) {
    $result = array_values($decoded['texts']);
    if (!empty($result)) {
      return $result;
    }
  }

  // FALLBACK: If root is already an indexed array
  if (isset($decoded[0])) {
    return array_values($decoded);
  }

  // FALLBACK: Try to find any array in the object
  foreach ($decoded as $key => $value) {
    if (is_array($value) && !empty($value) && isset($value[0]) && is_string($value[0])) {
      return array_values($value);
    }
  }

  // If all else fails, log what we got
  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[PTAI OpenAI Parse Error] Could not extract translations. Response keys: ' . implode(', ', array_keys($decoded)));
  }

  return ['success' => false, 'error' => 'Could not extract translation array from response. Expected {"translations": [...]} format.'];
}

function ptai_openai_compatible_base_url($baseUrl = null)
{
  $baseUrl = $baseUrl === null ? ptai_get_option('polytranslate_ai_api_options', 'openai_base_url', 'https://api.openai.com/v1') : $baseUrl;
  $baseUrl = untrailingslashit(trim((string) $baseUrl));
  if ($baseUrl === '') {
    return 'https://api.openai.com/v1';
  }
  $path = (string) (wp_parse_url($baseUrl, PHP_URL_PATH) ?: '');
  return preg_match('#/(v1|v\d+)$#i', $path) ? $baseUrl : $baseUrl . '/v1';
}

function ptai_openai_compatible_endpoint($path, $baseUrl = null)
{
  return ptai_openai_compatible_base_url($baseUrl) . '/' . ltrim((string) $path, '/');
}

function ptai_openai_response_error($response, $body)
{
  if (is_array($body)) {
    if (isset($body['error']['message'])) {
      return (string) $body['error']['message'];
    }
    if (isset($body['message'])) {
      return (string) $body['message'];
    }
    if (isset($body['error']) && is_string($body['error'])) {
      return $body['error'];
    }
  }

  $code = (int) wp_remote_retrieve_response_code($response);
  $raw = trim(wp_strip_all_tags((string) wp_remote_retrieve_body($response)));
  $raw = preg_replace('/\s+/', ' ', $raw);
  $raw = mb_substr((string) $raw, 0, 220);
  return trim('OpenAI compatible endpoint returned HTTP ' . $code . ($raw !== '' ? ': ' . $raw : ''));
}

function ptai_openai_should_retry_without_json_mode($response)
{
  $code = (int) wp_remote_retrieve_response_code($response);
  if ($code < 400 || $code >= 500) {
    return false;
  }

  $body = strtolower((string) wp_remote_retrieve_body($response));
  return str_contains($body, 'response_format') || str_contains($body, 'json_object') || str_contains($body, 'json mode');
}

/**
 * DeepL API - Batch translation
 * DeepL supports multiple texts in a single request
 */
function ptai_call_deepl_batch($apiKey, $texts, $targetLang, $sourceLang = '')
{
  // Determine which endpoint to use based on the API key suffix
  $apiUrl = 'https://api.deepl.com/v2/translate';
  if (strpos($apiKey, ':fx') !== false) {
    $apiUrl = 'https://api-free.deepl.com/v2/translate';
  }

  // Build request body with multiple texts
  $body = [
    'text' => array_values($texts),
    'target_lang' => strtoupper($targetLang),
    'tag_handling' => 'html',
    'tag_handling_version' => 'v2',
    'preserve_formatting' => true,
  ];

  if (!empty($sourceLang)) {
    $body['source_lang'] = strtoupper($sourceLang);
  }

  $response = wp_remote_post($apiUrl, [
    'headers' => [
      'Authorization' => 'DeepL-Auth-Key ' . $apiKey,
      'Content-Type'  => 'application/json'
    ],
    'body' => json_encode($body),
    'timeout' => 240,
  ]);

  if (is_wp_error($response)) {
    return ['success' => false, 'error' => $response->get_error_message()];
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);

  if (!isset($body['translations']) || !is_array($body['translations'])) {
    $error = $body['message'] ?? 'Unknown error from DeepL';
    return ['success' => false, 'error' => $error];
  }

  // Extract translated texts in order
  $results = [];
  foreach ($body['translations'] as $translation) {
    $results[] = $translation['text'] ?? '';
  }

  return $results;
}

/**
 * Google Translate API - Batch translation
 * Google supports multiple texts in a single request
 */
function ptai_call_google_translate_batch($apiKey, $texts, $targetLang, $sourceLang)
{
  $url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey);

  // Build request with multiple texts
  $body = [
    'q' => array_values($texts),
    'target' => strtolower($targetLang),
    'format' => 'html',
  ];

  if (!empty($sourceLang)) {
    $body['source'] = strtolower($sourceLang);
  }

  $response = wp_remote_post($url, [
    'headers' => [
      'Content-Type' => 'application/json',
      'Referer' => home_url(),
    ],
    'body' => json_encode($body),
    'timeout' => 240,
  ]);

  if (is_wp_error($response)) {
    return ['success' => false, 'error' => $response->get_error_message()];
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);

  if (!isset($body['data']['translations']) || !is_array($body['data']['translations'])) {
    $error = $body['error']['message'] ?? 'Unknown error from Google Translate';
    return ['success' => false, 'error' => $error];
  }

  // Extract translated texts in order
  $results = [];
  foreach ($body['data']['translations'] as $translation) {
    $translatedText = $translation['translatedText'] ?? '';

    // Decode HTML entities returned by Google Translate
    $translatedText = html_entity_decode($translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Google's HTML mode inserts spaces before punctuation and near HTML tags
    // in all language pairs. Strip them here so callers receive clean text.
    $translatedText = preg_replace('/\s+([.،,!?;:\)\]])/u', '$1', $translatedText);
    $translatedText = preg_replace('/([\(\[])\s+/u', '$1', $translatedText);

    $results[] = $translatedText;
  }

  return $results;
}
