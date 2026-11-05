<?php
/**
 * Accept the theme's object-valued REST fields when a client sends them as a JSON string.
 *
 * The WordPress.com connector passes fields that its tool schema does not list (boblift_specs,
 * boblift_product_copy, boblift_content_insert, ...) as strings, so WordPress rejected them with
 * "is not of type object" before any of the field's own checks ran. Before the request is validated, a
 * listed field whose value is a string holding a JSON object or list is replaced with the decoded value;
 * everything else (other fields, plain strings, invalid JSON) is left exactly as sent, so the field's own
 * validation still decides.
 */

defined('ABSPATH') || exit;

/** Object-valued fields the theme registers on wp/v2 routes. */
function boblift_rest_json_string_fields(): array
{
    return [
        'boblift_specs',
        'boblift_product_copy',
        'boblift_product_setup',
        'boblift_term_translation',
        'boblift_content_insert',
        'boblift_translation',
        'boblift_feedback_record_edit',
        'boblift_filter_bins',
        'boblift_legal_page',
        'boblift_product_faq',
        'boblift_tonnage_copy',
        'boblift_top_bar',
    ];
}

/** Decode a string that holds a JSON object or list; null when it does not. */
function boblift_rest_decode_json_string(mixed $value): ?array
{
    if (!is_string($value)) {
        return null;
    }
    $trimmed = ltrim($value);
    if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
        return null;
    }
    $decoded = json_decode($trimmed, true);
    return is_array($decoded) && json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

add_filter('rest_pre_dispatch', static function ($result, $server, WP_REST_Request $request) {
    if ($result !== null || !in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true) || !str_starts_with($request->get_route(), '/wp/v2/')) {
        return $result;
    }
    foreach (boblift_rest_json_string_fields() as $field) {
        $decoded = boblift_rest_decode_json_string($request->get_param($field));
        if ($decoded !== null) {
            $request->set_param($field, $decoded);
        }
    }
    return $result;
}, 10, 3);
