<?php
if (!defined('ABSPATH')) exit;

/**
 * WordPress Content Translation - v6.3 Block Attributes Support
 *
 * Splits content at block-level boundaries only.
 * Inline HTML tags (<a>, <strong>, <em>, etc.) stay inside text segments
 * so the API handles them natively with format=html / tag_handling=html.
 *
 * NEW: Translates block attributes from WordPress block comments
 * - Uses a generic list of translatable attribute names
 * - Works with any block type (core + third-party like Spectra/UAGB)
 * - No per-block registry needed
 *
 * CHANGES v6.3:
 * - Replaced ptai_unescape_json_unicode() with ptai_json_encode_block_attrs()
 *   which decodes ALL \uXXXX sequences universally via preg_replace_callback,
 *   so block comments always store real HTML regardless of what PHP escapes.
 * - Removed redundant decode on input: json_decode() already returns clean
 *   HTML strings, so no pre-processing needed before sending to the API.
 * - Deduplicate attribute strings before API call — identical strings
 *   (same text in multiple blocks) are sent only once, then mapped back.
 * - Step 1 main loop now skips segments already synced by Step 0's
 *   str_replace innerHTML pass — prevents double-translation of content
 *   that matches a translated block attribute value.
 *
 * @version 6.3.0 - Universal JSON encode + deduplication + skip already-translated
 */

require_once PTAI_INCLUDES_DIR . 'gutenberg-translatable-attributes.php';

/**
 * JSON-encode block attributes and restore ALL \uXXXX escape sequences
 * back to their real UTF-8 characters.
 *
 * PHP's json_encode() escapes certain characters (<, >, &, ', and others)
 * to \uXXXX sequences by default, even with JSON_UNESCAPED_UNICODE.
 * Rather than hardcoding which characters to restore, we decode every
 * \uXXXX sequence universally — so block comments always contain clean,
 * readable HTML regardless of what PHP chose to escape.
 *
 * @param array $attributes The decoded attributes array to encode.
 * @return string Clean JSON string with all \uXXXX sequences decoded to UTF-8.
 */
function ptai_json_encode_block_attrs(array $attributes): string
{
    $json = json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Decode ALL \uXXXX sequences universally — not just the known four.
    // This handles any character PHP decides to escape now or in future versions.
    return preg_replace_callback(
        '/\\\\u([0-9a-fA-F]{4})/',
        fn($m) => mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE'),
        $json
    );
}

// phpcs:disable WordPress.PHP.DevelopmentFunctions
function ptai_translate_content($content, $target_lang, $source_lang = '')
{
    // Each call reports only its own failure: an error left by an earlier document in the same request is cleared.
    unset($GLOBALS['ptai_last_translation_error']);

    $log = function ($msg, $data = null) {
        if (!defined('WP_DEBUG') || !WP_DEBUG)
            return;
        $prefix = '[PTAI v6.3] ';
        if ($data !== null) {
            $msg .= ' ' . (is_array($data) || is_object($data) ? print_r($data, true) : $data);
        }
        error_log($prefix . $msg);
    };

    $log('═══════════════════════════════════════════════════════');

    // ──────────────────────────────────────────────────────────
    // API-SPECIFIC SAFE LIMITS
    // ──────────────────────────────────────────────────────────

    $api_engine = ptai_get_option('polytranslate_ai_api_options', 'api_engine', 'OpenRouter');
    $is_ai_api = in_array($api_engine, ['OpenAI', 'OpenRouter'], true);

    switch ($api_engine) {
        case 'DeepL':
            $MAX_BATCH_SIZE = 50;
            $MAX_CHARS = 70000;
            $SKIP_THRESHOLD = 2;
            $DELAY_BETWEEN_BATCHES = 1;
            break;
        case 'Google':
        case 'GoogleTranslate':
            $MAX_BATCH_SIZE = 100;
            $MAX_CHARS = 30000;
            $SKIP_THRESHOLD = 2;
            $DELAY_BETWEEN_BATCHES = 1;
            break;
        case 'OpenAI':
        case 'OpenRouter':
            $MAX_BATCH_SIZE = 100;
            $MAX_CHARS = 60000;
            $SKIP_THRESHOLD = 2;
            $DELAY_BETWEEN_BATCHES = 0;
            break;
        default:
            $MAX_BATCH_SIZE = 30;
            $MAX_CHARS = 10000;
            $SKIP_THRESHOLD = 2;
            $DELAY_BETWEEN_BATCHES = 1;
            break;
    }

    $log('API: ' . $api_engine . ' | Batch: ' . $MAX_BATCH_SIZE . ' | Chars: ' . $MAX_CHARS);
    $log('TRANSLATION START (v6.3 - Block-Level Split + Block Attributes)');
    $log('Target: ' . $target_lang . ' | Source: ' . ($source_lang ?: 'auto'));
    $log('Input size: ' . strlen($content) . ' bytes');

    if (empty($content) || trim($content) === '') {
        $log('Content is empty.');
        return $content;
    }

    // ──────────────────────────────────────────────────────────
    // STEP 0: TRANSLATE BLOCK ATTRIBUTES FIRST
    // Process block comments (<!-- wp:block {"attr":"value"} -->)
    // Returns translated_strings map so Step 1 can skip already-synced segments
    // ──────────────────────────────────────────────────────────

    $log('STEP 0: Translating block attributes...');
    $block_result = ptai_translate_block_attributes($content, $target_lang, $source_lang, $log);
    $content = $block_result['content'];
    $translated_attribute_strings = $block_result['translated_strings'];
    $log('Block attributes translation complete');

    // Build a set of values that were already injected into innerHTML by
    // Step 0's str_replace sync pass. The main loop will skip these segments
    // to avoid sending them to the API a second time.
    // Keys = trimmed translated text => true (array_flip gives O(1) lookup)
    $already_translated_values = !empty($translated_attribute_strings)
        ? array_flip(array_map('trim', $translated_attribute_strings))
        : [];

    // ──────────────────────────────────────────────────────────
    // STEP 1: BUILD STRUCTURE MAP (BLOCK-LEVEL SPLIT)
    // Inline tags stay INSIDE text segments for native API handling
    // ──────────────────────────────────────────────────────────

    $log('STEP 1: Building block-level structure map...');

    $block_tags = 'p|div|h[1-6]|ul|ol|li|table|thead|tbody|tfoot|tr|td|th'
        . '|blockquote|pre|hr|figure|figcaption|section|article'
        . '|header|footer|nav|main|aside|details|summary'
        . '|dl|dt|dd|form|fieldset|address|hgroup';

    $pattern = '/'
        . '('
        // WordPress block comments
        . '<!--\s*wp:.*?-->'
        . '|<!--\s*\/wp:.*?-->'
        // HTML comments (non-block)
        . '|<!--(?!\s*\/?wp:)[\s\S]*?-->'
        // Script tags
        . '|<script\b[^>]*>[\s\S]*?<\/script>'
        // Style tags
        . '|<style\b[^>]*>[\s\S]*?<\/style>'
        // SVG tags
        . '|<svg\b[^>]*>[\s\S]*?<\/svg>'
        // WordPress shortcodes
        . '|\[[a-zA-Z_][a-zA-Z0-9_-]*(?:\s+[^\]]*)?\\]'
        . '|\[\/[a-zA-Z_][a-zA-Z0-9_-]*\]'
        // Block-level HTML tags only (inline tags like <a>, <strong> stay in text)
        . '|<\/?(?:' . $block_tags . ')(?:\s[^>]*)?\/?>'
        // Blank lines (paragraph breaks in classic editor content)
        . '|\n\s*\n'
        . ')'
        . '/is';

    $segments = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    if ($segments === false) {
        $log('ERROR: preg_split failed');
        return $content;
    }

    // ──────────────────────────────────────────────────────────
    // MERGE SEGMENTS WITH SPLIT INLINE TAGS
    // When inline tags like <a>, <span> wrap around delimiters (SVG, etc.),
    // they get split. Merge segments to keep inline tags intact.
    // ──────────────────────────────────────────────────────────
    $inline_tags = 'a|span|strong|b|em|i|u|small|big|code|kbd|samp|var|mark|del|ins|sub|sup|time|abbr|dfn|q|cite|s|strike|label|font';
    $segments = ptai_merge_inline_tag_segments($segments, $pattern, $inline_tags);

    $log('Split into segments: ' . count($segments));

    $structure = array();
    $text_segments = array();
    $text_segment_indices = array();

    foreach ($segments as $i => $segment) {
        if (empty($segment))
            continue;

        // Check if this segment is ONLY a delimiter (the entire segment is markup)
        // vs text that happens to contain inline markup like <a> with SVG inside
        $is_delimiter = false;
        if (preg_match($pattern, $segment, $matches)) {
            // Check if the entire segment is just the delimiter (with optional whitespace)
            $matched_text = $matches[0];
            $remaining = trim(str_replace($matched_text, '', $segment, $count));
            // If we replaced something and what's left has no letters, it's a delimiter
            if ($count > 0 && ($remaining === '' || !preg_match('/\p{L}/u', $remaining))) {
                $is_delimiter = true;
            }
        }

        if ($is_delimiter) {
            $structure[] = array('type' => 'markup', 'content' => $segment);
            continue;
        }

        // Check for translatable text OUTSIDE of inline HTML tags
        // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
        $text_only = strip_tags($segment);
        $trimmed = trim($text_only);
        $has_letters = preg_match('/\p{L}/u', $trimmed);

        if (!$has_letters || $trimmed === '' || mb_strlen($trimmed) < $SKIP_THRESHOLD) {
            $structure[] = array('type' => 'whitespace', 'content' => $segment);
            continue;
        }

        // ── Skip segments already synced by Step 0 innerHTML pass ──────────
        // Step 0's str_replace already put the translated value into this
        // segment. Sending it again would cause a double-translation.
        if (isset($already_translated_values[trim($segment)])) {
            $structure[] = array('type' => 'whitespace', 'content' => $segment);
            $log('  Skipping already-translated segment: ' . mb_substr(trim($segment), 0, 60));
            continue;
        }
        // ───────────────────────────────────────────────────────────────────

        $text_index = count($text_segments);
        $structure_index = count($structure);

        // Capture leading/trailing whitespace
        $seg_leading = '';
        $seg_trailing = '';
        if (preg_match('/^(\s+)/s', $segment, $wm))
            $seg_leading = $wm[1];
        if (preg_match('/(\s+)$/s', $segment, $wm))
            $seg_trailing = $wm[1];

        $structure[] = array(
            'type' => 'text',
            'content' => $segment,
            'text_index' => $text_index,
            'leading_ws' => $seg_leading,
            'trailing_ws' => $seg_trailing,
        );

        $text_segments[] = trim($segment);
        $text_segment_indices[] = $structure_index;
    }

    $text_count = count($text_segments);
    $log('Translatable text segments: ' . $text_count);

    if ($text_count === 0) {
        $log('No translatable text found.');
        return $content;
    }

    $sample_count = min(3, $text_count);
    for ($s = 0; $s < $sample_count; $s++) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
        $preview = mb_substr(trim(strip_tags($text_segments[$s])), 0, 80);
        $log(sprintf('  [%d] "%s"', $s, $preview));
    }

    // ──────────────────────────────────────────────────────────
    // STEP 2: CREATE BATCHES
    // ──────────────────────────────────────────────────────────

    $log('STEP 2: Creating batches...');

    $batches = array();
    $current_batch = array();
    $current_indices = array();
    $current_size = 0;

    foreach ($text_segments as $idx => $text) {
        $text_size = strlen(json_encode($text, JSON_UNESCAPED_UNICODE));

        if (count($current_batch) >= $MAX_BATCH_SIZE || ($current_size + $text_size + 10) > $MAX_CHARS) {
            if (!empty($current_batch)) {
                $batches[] = array('texts' => $current_batch, 'indices' => $current_indices);
                $log('Batch ' . count($batches) . ': ' . count($current_batch) . ' segments, ~' . $current_size . ' chars');
                $current_batch = array();
                $current_indices = array();
                $current_size = 0;
            }
        }

        $current_batch[] = $text;
        $current_indices[] = $idx;
        $current_size += $text_size + 10;
    }

    if (!empty($current_batch)) {
        $batches[] = array('texts' => $current_batch, 'indices' => $current_indices);
        $log('Batch ' . count($batches) . ': ' . count($current_batch) . ' segments, ~' . $current_size . ' chars');
    }

    $batch_count = count($batches);
    $log('Total batches: ' . $batch_count);

    // ──────────────────────────────────────────────────────────
    // STEP 3: TRANSLATE ALL BATCHES
    // ──────────────────────────────────────────────────────────

    $log('STEP 3: Translating ' . $batch_count . ' batch(es)...');

    if (!function_exists('ptai_call_translation_ai')) {
        $log('ERROR: ptai_call_translation_ai() not found!');
        return $content;
    }

    $translated_segments = array();
    $overall_timer = microtime(true);

    foreach ($batches as $batch_num => $batch) {
        if ($batch_num > 0 && $DELAY_BETWEEN_BATCHES > 0) {
            sleep($DELAY_BETWEEN_BATCHES);
        }

        $batch_display = $batch_num + 1;
        $log("Translating batch {$batch_display}/{$batch_count}...");

        $batch_timer = microtime(true);
        $log('PRE-TRANSLATION BATCH:', $batch['texts']);
        $result = ptai_call_translation_ai($batch['texts'], $target_lang, $source_lang);
        $log('POST-TRANSLATION RESULT:', $result);
        $batch_elapsed = round(microtime(true) - $batch_timer, 2);
        $log("Batch {$batch_display} done in {$batch_elapsed}s");
        $log('Input texts count: ' . count($batch['texts']));

        // Detailed logging of what we got back
        if (is_array($result)) {
            if (isset($result['success']) && $result['success'] === false) {
                $log('API returned error: ' . ($result['error'] ?? 'Unknown'));
            } else {
                $log('Translations received: ' . count($result) . ' items');
            }
        } elseif (is_string($result)) {
            $log('String response received (unexpected format)');
        } else {
            $log('Unexpected response type: ' . gettype($result));
        }

        // Handle errors
        if (is_array($result) && isset($result['success']) && $result['success'] === false) {
            $log("ERROR batch {$batch_display}: " . ($result['error'] ?? 'Unknown'));
            // BOB-LIFT: callers that must not publish untranslated text read this (the segments fall back to the source).
            $GLOBALS['ptai_last_translation_error'] = (string) ($result['error'] ?? 'Unknown translation error');
            foreach ($batch['indices'] as $i => $idx) {
                $translated_segments[$idx] = $batch['texts'][$i];
            }
            continue;
        }

        if (empty($result)) {
            $log("ERROR batch {$batch_display}: Empty response");
            $GLOBALS['ptai_last_translation_error'] = 'Empty translation response';
            foreach ($batch['indices'] as $i => $idx) {
                $translated_segments[$idx] = $batch['texts'][$i];
            }
            continue;
        }

        // Process results
        if ($is_ai_api && !is_array($result)) {
            $result = json_decode($result, true);
        }

        if (is_array($result)) {
            // Handle nested structures from AI APIs
            if (isset($result['translations']))
                $result = $result['translations'];
            elseif (isset($result['texts']))
                $result = $result['texts'];

            foreach ($batch['indices'] as $i => $idx) {
                if (isset($result[$i])) {
                    $translated_segments[$idx] = $result[$i];
                } else {
                    $log("WARNING: Missing index {$idx}");
                    $GLOBALS['ptai_last_translation_error'] = 'Translation response is missing segments';
                    $translated_segments[$idx] = $batch['texts'][$i];
                }
            }
        } else {
            $log("ERROR: Unexpected response type: " . gettype($result));
            $GLOBALS['ptai_last_translation_error'] = 'Unexpected translation response';
            foreach ($batch['indices'] as $i => $idx) {
                $translated_segments[$idx] = $batch['texts'][$i];
            }
        }
    }

    $log("All batches done in " . round(microtime(true) - $overall_timer, 2) . "s");

    // ──────────────────────────────────────────────────────────
    // STEP 4: INJECT TRANSLATIONS INTO STRUCTURE
    // ──────────────────────────────────────────────────────────

    $log('STEP 4: Injecting translations...');
    $injected = 0;

    foreach ($text_segment_indices as $text_idx => $struct_idx) {
        if (!isset($translated_segments[$text_idx]))
            continue;

        $translation = trim($translated_segments[$text_idx]);
        $leading_ws = $structure[$struct_idx]['leading_ws'] ?? '';
        $trailing_ws = $structure[$struct_idx]['trailing_ws'] ?? '';

        $structure[$struct_idx]['content'] = $leading_ws . $translation . $trailing_ws;
        $injected++;
    }

    $log('Injected: ' . $injected);

    // ──────────────────────────────────────────────────────────
    // STEP 5: REBUILD CONTENT
    // ──────────────────────────────────────────────────────────

    $result = '';
    foreach ($structure as $item) {
        $result .= $item['content'];
    }

    $log('Final size: ' . strlen($result) . ' bytes');
    $log('TRANSLATION COMPLETE');
    $log('═══════════════════════════════════════════════════════');

    return $result;
}

/**
 * Recursively extract translatable strings from nested attributes.
 *
 * @param array  $data                The attributes array (can be nested)
 * @param array  $translatable_lookup Lookup array for translatable names (lowercase => true)
 * @param array  $translatable_names  Original list of translatable names for suffix matching
 * @param string $prefix              Key path prefix for nested keys (e.g., "attrs.items.0")
 * @return array Flat array of ['path' => 'key.path', 'value' => 'string', 'key' => 'original_key']
 */
function ptai_extract_nested_translatables($data, $translatable_lookup, $translatable_names, $prefix = '')
{
    $results = array();

    if (!is_array($data)) {
        return $results;
    }

    foreach ($data as $key => $value) {
        // Build the path for this key
        $path = ($prefix !== '') ? $prefix . '.' . $key : (string) $key;

        if (is_array($value)) {
            // Recursively process arrays/objects
            $nested_results = ptai_extract_nested_translatables($value, $translatable_lookup, $translatable_names, $path);
            $results = array_merge($results, $nested_results);
        } elseif (is_string($value) && trim($value) !== '') {
            // Check if this key is translatable
            $key_lower = strtolower($key);
            $is_translatable = isset($translatable_lookup[$key_lower]);

            // Also check for partial matches (e.g., "title" matches "hero_title")
            if (!$is_translatable) {
                foreach ($translatable_names as $tname) {
                    // Check if key ends with translatable name
                    if (strlen($key) > strlen($tname)) {
                        $suffix = substr($key, -strlen($tname));
                        if (
                            strtolower($suffix) === strtolower($tname) &&
                            (substr($key, -strlen($tname) - 1, 1) === '_' || substr($key, -strlen($tname) - 1, 1) === '-')
                        ) {
                            $is_translatable = true;
                            break;
                        }
                    }
                }
            }

            if ($is_translatable) {
                // Skip if it looks like HTML with only non-text content (SVG, etc.)
                if (preg_match('/^<(svg|script|style|img|video|audio|iframe)/i', trim($value))) {
                    continue;
                }

                // Skip if it's only whitespace or special characters
                if (!preg_match('/\p{L}/u', $value)) {
                    continue;
                }

                $results[] = array(
                    'path' => $path,
                    'value' => $value,
                    'key' => $key,
                );
            }
        }
    }

    return $results;
}

/**
 * Set a value in a nested array using dot notation path.
 *
 * @param array  $data   The array to modify (by reference)
 * @param string $path   Dot notation path (e.g., "attrs.items.0.text")
 * @param mixed  $value  The value to set
 */
function ptai_set_nested_value(&$data, $path, $value)
{
    $keys = explode('.', $path);
    $current = &$data;

    foreach ($keys as $key) {
        if (is_array($current)) {
            if (!isset($current[$key])) {
                $current[$key] = array();
            }
            $current = &$current[$key];
        }
    }

    $current = $value;
}

/**
 * Extract complete JSON from block comment using brace counting.
 *
 * @param string $block_comment The block comment string
 * @return array|null Array with 'block_name' and 'attributes_json' or null
 */
function ptai_extract_block_json($block_comment)
{
    // Find the start of JSON
    $json_start = strpos($block_comment, '{');
    if ($json_start === false) {
        // No JSON, just extract block name
        if (preg_match('/<!--\s*wp:([a-zA-Z0-9\-\/]+)/', $block_comment, $matches)) {
            return array(
                'block_name' => $matches[1],
                'attributes_json' => null,
                'json_start' => -1,
                'json_end' => -1,
            );
        }
        return null;
    }

    // Count braces to find the complete JSON
    $brace_count = 0;
    $json_end = $json_start;
    $len = strlen($block_comment);

    for ($i = $json_start; $i < $len; $i++) {
        $char = $block_comment[$i];
        if ($char === '{') {
            $brace_count++;
        } elseif ($char === '}') {
            $brace_count--;
            if ($brace_count === 0) {
                $json_end = $i;
                break;
            }
        }
    }

    if ($brace_count !== 0) {
        return null; // Unbalanced braces
    }

    // Extract block name
    if (!preg_match('/<!--\s*wp:([a-zA-Z0-9\-\/]+)/', $block_comment, $matches)) {
        return null;
    }

    $attributes_json = substr($block_comment, $json_start, $json_end - $json_start + 1);

    return array(
        'block_name' => $matches[1],
        'attributes_json' => $attributes_json,
        'json_start' => $json_start,
        'json_end' => $json_end,
    );
}

/**
 * Extract translatable strings from WordPress block attributes (including nested).
 *
 * @param string $block_comment The block comment (<!-- wp:block {...} -->)
 * @return array|null Array with 'block_name', 'attributes_json', and 'translatables' (array with path, value, key)
 */
function ptai_extract_block_attribute_strings($block_comment)
{
    $extracted = ptai_extract_block_json($block_comment);
    if ($extracted === null) {
        return null;
    }

    $block_name = $extracted['block_name'];
    $attributes_json = $extracted['attributes_json'];

    if (empty($attributes_json)) {
        return null;
    }

    // Decode JSON — php's json_decode() automatically converts ALL \uXXXX
    // sequences back to real UTF-8 characters (including <, >, & and any
    // other escaped char), so values extracted here are already clean HTML
    // ready to be sent directly to the translation API.
    $attributes = json_decode($attributes_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($attributes)) {
        return null;
    }

    // Get translatable attribute names
    $translatable_names = ptai_get_translatable_attributes();

    // Build lookup for faster checking (case-insensitive)
    $translatable_lookup = array();
    foreach ($translatable_names as $name) {
        $translatable_lookup[strtolower($name)] = true;
    }

    // Find translatable attributes (including nested)
    $translatables = ptai_extract_nested_translatables($attributes, $translatable_lookup, $translatable_names);

    if (empty($translatables)) {
        return null;
    }

    return array(
        'block_name' => $block_name,
        'attributes_json' => $attributes_json,
        'translatables' => $translatables,
        'json_start' => $extracted['json_start'],
        'json_end' => $extracted['json_end'],
    );
}

/**
 * Process and translate block attributes in content.
 *
 * @param string   $content     The content with block comments
 * @param string   $target_lang Target language
 * @param string   $source_lang Source language (optional)
 * @param callable $log         Logging function
 * @return array Array with 'content' and 'translated_strings' (for deduplication)
 */
function ptai_translate_block_attributes($content, $target_lang, $source_lang, $log)
{
    // Find all block opening comments using a pattern that captures the whole comment
    // We'll extract JSON using brace counting instead of regex
    $pattern = '/<!--\s*wp:[a-zA-Z0-9\-\/]+.*?-->/s';

    if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        $log('No block comments found');
        return array('content' => $content, 'translated_strings' => array());
    }

    $log('Found ' . count($matches) . ' block comments');

    // Collect all translatable attribute strings
    $all_translatables = array();
    $block_map = array(); // Maps translation index to block comment info
    $translated_strings = array(); // For deduplication: original => translated

    foreach ($matches as $match_index => $match) {
        $full_match = $match[0][0];
        $offset = $match[0][1];

        $result = ptai_extract_block_attribute_strings($full_match);

        if ($result && !empty($result['translatables'])) {
            $block_name = $result['block_name'];
            $attr_keys = array_map(function ($t) {
                return $t['key'];
            }, $result['translatables']);
            $log("Block '{$block_name}' has translatable attributes: " . implode(', ', array_unique($attr_keys)));

            foreach ($result['translatables'] as $translatable) {
                $text_index = count($all_translatables);

                // json_decode() already returns fully decoded UTF-8 strings —
                // all \uXXXX sequences (including <, >, &) are converted to
                // real characters, so we send clean HTML to the API as-is.
                $clean_value = $translatable['value'];

                $all_translatables[] = $clean_value;
                $block_map[$text_index] = array(
                    'full_match'      => $full_match,
                    'offset'          => $offset,
                    'block_name'      => $block_name,
                    'attributes_json' => $result['attributes_json'],
                    'json_start'      => $result['json_start'],
                    'json_end'        => $result['json_end'],
                    'path'            => $translatable['path'],
                    'attr_key'        => $translatable['key'],
                    'attr_value'      => $clean_value, // clean HTML, matches innerHTML exactly
                    'match_index'     => $match_index,
                );
            }
        }
    }

    if (empty($all_translatables)) {
        $log('No translatable block attribute strings found');
        return array('content' => $content, 'translated_strings' => array());
    }

    $log('Total translatable attribute strings: ' . count($all_translatables));

    // ── DEDUPLICATION: only send unique strings to the API ─────────────────
    // Identical strings (same text appearing in multiple blocks) are translated
    // once and mapped back to all their original indices, cutting API cost.
    $unique_strings = [];  // unique_index => string
    $unique_map     = [];  // trim(string) => unique_index
    $text_to_unique = [];  // original_index => unique_index

    foreach ($all_translatables as $idx => $text) {
        $key = trim($text);
        if (!isset($unique_map[$key])) {
            $unique_map[$key] = count($unique_strings);
            $unique_strings[] = $text;
        }
        $text_to_unique[$idx] = $unique_map[$key];
    }

    $log('Unique attribute strings to translate: ' . count($unique_strings)
        . ' (from ' . count($all_translatables) . ' total)');
    // ── END DEDUPLICATION ──────────────────────────────────────────────────

    if (!function_exists('ptai_call_translation_ai')) {
        $log('ERROR: ptai_call_translation_ai() not found!');
        return array('content' => $content, 'translated_strings' => array());
    }

    $log('Translating block attribute strings...');
    $log('Original attribute strings: ', $unique_strings);
    $translated_unique = ptai_call_translation_ai($unique_strings, $target_lang, $source_lang);
    $log('Translated attribute strings: ', $translated_unique);

    if (empty($translated_unique) || (is_array($translated_unique) && isset($translated_unique['success']) && $translated_unique['success'] === false)) {
        $log('ERROR: Block attribute translation failed');
        return array('content' => $content, 'translated_strings' => array());
    }

    // Handle API response format
    if (isset($translated_unique['translations'])) {
        $translated_unique = $translated_unique['translations'];
    } elseif (isset($translated_unique['texts'])) {
        $translated_unique = $translated_unique['texts'];
    }

    // Map unique translations back to all original indices
    $translated = [];
    foreach ($text_to_unique as $orig_idx => $unique_idx) {
        $translated[$orig_idx] = $translated_unique[$unique_idx] ?? $all_translatables[$orig_idx];
    }

    // Build translations map per block comment and track for innerHTML sync
    $block_translations = array(); // match_index => [path => translated_value]

    foreach ($block_map as $text_index => $info) {
        if (!isset($translated[$text_index])) {
            $log("WARNING: Missing translation for index {$text_index}");
            continue;
        }

        $match_index      = $info['match_index'];
        $path             = $info['path'];
        $original_value   = $info['attr_value'];
        $translated_value = $translated[$text_index];

        if (!isset($block_translations[$match_index])) {
            $block_translations[$match_index] = array();
        }
        $block_translations[$match_index][$path] = $translated_value;

        // Track original => translated for innerHTML sync and Step 1 skip check.
        // Use trimmed original as key so the lookup stays consistent.
        $trimmed_original   = trim($original_value);
        $trimmed_translated = is_string($translated_value) ? trim($translated_value) : $translated_value;
        if ($trimmed_original !== '') {
            $translated_strings[$trimmed_original] = $trimmed_translated;
        }
    }

    // Group blocks by match_index for processing
    $blocks_by_match = array();
    foreach ($block_map as $text_index => $info) {
        $match_index = $info['match_index'];
        if (!isset($blocks_by_match[$match_index])) {
            $blocks_by_match[$match_index] = $info;
        }
    }

    // Build list and sort by offset descending (process from end to start)
    // so that earlier offsets remain valid as we replace content
    $blocks_to_process = array_values($blocks_by_match);
    usort($blocks_to_process, function ($a, $b) {
        return $b['offset'] - $a['offset'];
    });

    foreach ($blocks_to_process as $info) {
        $full_match      = $info['full_match'];
        $offset          = $info['offset'];
        $block_name      = $info['block_name'];
        $attributes_json = $info['attributes_json'];
        $match_index     = $info['match_index'];

        if (!isset($block_translations[$match_index])) {
            continue;
        }

        $translations = $block_translations[$match_index];

        // Decode attributes
        $attributes = json_decode($attributes_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        // Update attributes with translations using dot-notation paths
        foreach ($translations as $path => $translated_value) {
            ptai_set_nested_value($attributes, $path, $translated_value);
        }

        // Re-encode using ptai_json_encode_block_attrs() which decodes ALL
        // \uXXXX sequences universally, so block comments store real HTML
        // characters (<strong> etc.) instead of unicode escape sequences.
        $new_attributes_json = ptai_json_encode_block_attrs($attributes);

        // Check if self-closing
        $is_self_closing = preg_match('/\/\s*-->$/', $full_match);
        $closing = $is_self_closing ? ' /-->' : ' -->';

        // Build new block comment
        $new_block_comment = '<!-- wp:' . $block_name . ' ' . $new_attributes_json . $closing;

        // Replace in content (offset-safe because we process end to start)
        $content = substr_replace($content, $new_block_comment, $offset, strlen($full_match));

        $log("Updated block '{$block_name}' attributes");
    }

    // Sync translated attribute values into innerHTML — no extra API call needed.
    // Because attr values came from json_decode() they are clean HTML and match
    // exactly what the block's innerHTML contains, so str_replace is safe.
    foreach ($translated_strings as $original => $translated_val) {
        if ($original !== $translated_val) {
            $content = str_replace($original, $translated_val, $content);
        }
    }

    $log('innerHTML sync applied: ' . count($translated_strings) . ' replacements');

    return array(
        'content'            => $content,
        'translated_strings' => $translated_strings,
    );
}

/**
 * Merge segments that have split inline tags across delimiters.
 *
 * When inline tags like <a>, <span> wrap around delimiters (SVG, scripts, etc.),
 * preg_split breaks them apart. This function merges such segments to keep
 * inline tags intact.
 *
 * @param array  $segments          The segments from preg_split
 * @param string $delimiter_pattern Pattern used to split (to identify delimiters)
 * @param string $inline_tags       Pipe-separated list of inline tag names
 * @return array Merged segments
 */
function ptai_merge_inline_tag_segments($segments, $delimiter_pattern, $inline_tags)
{
    if (empty($segments)) {
        return $segments;
    }

    // Pattern for delimiters that are safe to merge across (self-contained elements)
    $safe_delimiter_pattern = '/^(\s*|<svg\b[^>]*>[\s\S]*?<\/svg>|<script\b[^>]*>[\s\S]*?<\/script>|<style\b[^>]*>[\s\S]*?<\/style>|<!--[\s\S]*?-->)$/is';

    $merged = array();
    $i = 0;
    $count = count($segments);

    while ($i < $count) {
        $segment = $segments[$i];

        // If this is a delimiter (markup), just add it and continue
        if (preg_match($delimiter_pattern, $segment)) {
            $merged[] = $segment;
            $i++;
            continue;
        }

        // Check for unclosed inline tags in this segment
        $tag_pattern   = '/<(' . $inline_tags . ')\b[^>]*>/i';
        $close_pattern = '/<\/(' . $inline_tags . ')>/i';

        // Find all opening tags
        preg_match_all($tag_pattern, $segment, $opens, PREG_SET_ORDER);
        // Find all closing tags
        preg_match_all($close_pattern, $segment, $closes, PREG_SET_ORDER);

        // Build list of unclosed tags using a stack
        $open_stack = array();
        foreach ($opens as $open) {
            $open_stack[] = strtolower($open[1]);
        }
        foreach ($closes as $close) {
            $tag_name = strtolower($close[1]);
            $pos = array_search($tag_name, $open_stack);
            if ($pos !== false) {
                unset($open_stack[$pos]);
                $open_stack = array_values($open_stack);
            }
        }

        // If no unclosed tags, just add the segment
        if (empty($open_stack)) {
            $merged[] = $segment;
            $i++;
            continue;
        }

        // We have unclosed tags — look ahead to find where they close
        $merged_content = $segment;
        $j = $i + 1;
        $found_closing = false;

        while ($j < $count && !empty($open_stack)) {
            $next_segment = $segments[$j];

            // Check if it's a delimiter
            if (preg_match($delimiter_pattern, $next_segment)) {
                // Check if it's a "safe" delimiter we can merge across
                // (whitespace, SVG, script, style, or HTML comments)
                if (preg_match($safe_delimiter_pattern, $next_segment)) {
                    $merged_content .= $next_segment;
                    $j++;
                    continue;
                }
                // It's a block-level delimiter, stop looking
                break;
            }

            // Add this text segment
            $merged_content .= $next_segment;

            // Check if any of our open tags are now closed
            preg_match_all($close_pattern, $next_segment, $next_closes, PREG_SET_ORDER);
            foreach ($next_closes as $close) {
                $tag_name = strtolower($close[1]);
                $pos = array_search($tag_name, $open_stack);
                if ($pos !== false) {
                    unset($open_stack[$pos]);
                    $open_stack = array_values($open_stack);
                    $found_closing = true;
                }
            }

            // Also track new opens in the merged content
            preg_match_all($tag_pattern, $next_segment, $next_opens, PREG_SET_ORDER);
            foreach ($next_opens as $open) {
                $tag_name = strtolower($open[1]);
                // Check if this tag is self-closing
                if (substr($open[0], -2) === '/>') {
                    continue;
                }
                // Count opens and closes of this tag in this segment
                $close_count      = substr_count(strtolower($next_segment), '</' . $tag_name . '>');
                $open_count       = substr_count(strtolower($next_segment), '<' . $tag_name);
                // Check if it's self-closed with />
                $self_close_count = substr_count($next_segment, '/>');
                $net_opens        = $open_count - $close_count - $self_close_count;
                if ($net_opens > 0) {
                    for ($k = 0; $k < $net_opens; $k++) {
                        $open_stack[] = $tag_name;
                    }
                }
            }

            $j++;
        }

        // If we found closing tags, use the merged content
        if ($found_closing || empty($open_stack)) {
            $merged[] = $merged_content;
            $i = $j;
        } else {
            // No closing found, keep original segment
            $merged[] = $segment;
            $i++;
        }
    }

    return $merged;
}
