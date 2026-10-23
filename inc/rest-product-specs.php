<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the boblift_specs field on wp/v2/product, so a figure in a product's spec table can be corrected in every
 * language at once through the WordPress.com connector (which only reaches wp/v2; _specs is not REST meta).
 *
 * Read (context edit): {"en": [{"label": "...", "value": "..."}], "zh": [...], ...}, the spec table of the product and of
 * each of its translations, keyed by language.
 *
 * Write, to the English product only (the operations name rows by their English label):
 *   "set":     [{"label": "Rotation Angle", "value": "360°"}] changes the value of the row with that English label, in
 *              every language. "value" is one string for all languages or a map {"en": "...", "zh": "...", ...} naming
 *              every language of the product.
 *   "insert":  [{"after": "Max. Flow", "label": {"en": "Oil Tank Capacity", "zh": "...", ...}, "value": "57 L"}] adds a
 *              row after the row with that English label, in every language ("label" names every language; "value" as
 *              for "set"). A row whose English label is already in the table is skipped.
 *   "replace": {"en": [{"from": "continuous 360° slewing", "to": "360° slewing", "in": "content"}], "zh": [...]} replaces
 *              text in that language's body ("in": "content", the default), excerpt ("excerpt") or SEO description
 *              ("seo_description"). "from" must occur exactly once; when it is gone and "to" is already there the item
 *              is skipped, so sending the same request twice changes nothing.
 * A row is changed in a translation only while its table still follows the English one row for row
 * (boblift_spec_rows_aligned()); otherwise the request is refused. Every language is worked out and checked before
 * anything is written, so a request that cannot be applied changes nothing. Each item is then written under its own
 * per-post lock (the one boblift_content_insert uses, so no REST save of that item can land in between) and re-checked
 * against its stored state; if an item changed between the check and its write, the request stops with an error naming
 * the items already written, and sending it again finishes the rest. A request that also sends "content", "excerpt" or
 * "meta" is refused: send one or the other.
 */
function boblift_register_rest_product_specs_field(): void
{
    register_rest_field('product', 'boblift_specs', [
        'get_callback' => static function (array $post): array {
            $tables = [];
            foreach (boblift_product_specs_group((int) $post['id']) as $language => $id) {
                $tables[$language] = array_values((array) boblift_decode_meta(get_post_meta($id, '_specs', true), []));
            }
            return $tables;
        },
        'update_callback' => static function ($value, WP_Post $post, string $field, WP_REST_Request $request) {
            foreach (['content', 'excerpt', 'meta'] as $core) {
                if ($request[$core] !== null) {
                    return new WP_Error('boblift_specs_core', sprintf('Send either "%s" or "boblift_specs", not both.', $core), ['status' => 400]);
                }
            }
            $saved = boblift_rest_save_product_specs($post->ID, is_array($value) ? $value : []);
            if (is_wp_error($saved)) {
                return $saved;
            }
            // The response is built from this object, so it should show the product as saved.
            $fresh = get_post($post->ID);
            if ($fresh instanceof WP_Post) {
                $post->post_content = $fresh->post_content;
                $post->post_excerpt = $fresh->post_excerpt;
            }
            return true;
        },
        'schema' => [
            'description' => 'Spec tables by language; on the English product, write {"set", "insert", "replace"} to change every language.',
            'type' => 'object',
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_product_specs_field');

/** The product's translation group as language => ID, the English product first. */
function boblift_product_specs_group(int $product_id): array
{
    $group = function_exists('pll_get_post_translations') ? array_map('intval', (array) pll_get_post_translations($product_id)) : [];
    if ($group === []) {
        $group = ['en' => $product_id];
    }
    if (isset($group['en'])) {
        $group = ['en' => $group['en']] + $group;
    }
    return $group;
}

/** The value a "set" or "insert" item gives one language: the one string, or that language's entry of the map. */
function boblift_product_specs_value_for($value, string $language): ?string
{
    if (is_string($value)) {
        return $value;
    }
    return is_array($value) && isset($value[$language]) && is_string($value[$language]) ? $value[$language] : null;
}

/** Index of the row with this English label, or an error unless exactly one row has it. */
function boblift_product_specs_row_index(array $english_rows, string $label): int|WP_Error
{
    $found = [];
    foreach ($english_rows as $index => $row) {
        if (is_array($row) && trim((string) ($row['label'] ?? '')) === $label) {
            $found[] = $index;
        }
    }
    if (count($found) !== 1) {
        return new WP_Error('boblift_specs_label', sprintf('The English table has %d rows labelled "%s"; it must have exactly one.', count($found), $label), ['status' => 400]);
    }
    return $found[0];
}

/**
 * Works out the new spec table of every language from the "set" and "insert" items. Returns language => rows (only
 * languages whose table changes), or an error when an item cannot be applied to every language.
 */
function boblift_product_specs_plan_rows(array $tables, array $value): array|WP_Error
{
    $english = $tables['en'];
    foreach ($tables as $language => $rows) {
        if ($language !== 'en' && !boblift_spec_rows_aligned($rows, $english)) {
            return new WP_Error('boblift_specs_aligned', sprintf('The %s table does not follow the English one row for row; fix it on the product screen first.', $language), ['status' => 409]);
        }
    }
    $new = $tables;
    foreach ((array) ($value['set'] ?? []) as $item) {
        $index = boblift_product_specs_row_index($new['en'], is_array($item) ? trim((string) ($item['label'] ?? '')) : '');
        if (is_wp_error($index)) {
            return $index;
        }
        foreach (array_keys($new) as $language) {
            $text = boblift_product_specs_value_for($item['value'] ?? null, $language);
            if ($text === null || trim($text) === '') {
                return new WP_Error('boblift_specs_value', sprintf('"set" for "%s" gives no %s value.', $item['label'], $language), ['status' => 400]);
            }
            $new[$language][$index]['value'] = trim($text);
        }
    }
    foreach ((array) ($value['insert'] ?? []) as $item) {
        $label_map = is_array($item) && is_array($item['label'] ?? null) ? $item['label'] : [];
        $english_label = trim((string) ($label_map['en'] ?? ''));
        if ($english_label === '') {
            return new WP_Error('boblift_specs_insert', '"insert" needs a "label" map with an "en" label.', ['status' => 400]);
        }
        if (array_filter($new['en'], static fn($row): bool => is_array($row) && trim((string) ($row['label'] ?? '')) === $english_label) !== []) {
            continue;
        }
        $after = boblift_product_specs_row_index($new['en'], trim((string) ($item['after'] ?? '')));
        if (is_wp_error($after)) {
            return $after;
        }
        foreach (array_keys($new) as $language) {
            $label = trim((string) ($label_map[$language] ?? ''));
            $text = boblift_product_specs_value_for($item['value'] ?? null, $language);
            if ($label === '' || $text === null || trim($text) === '') {
                return new WP_Error('boblift_specs_insert', sprintf('"insert" of "%s" gives no %s label or value.', $english_label, $language), ['status' => 400]);
            }
            array_splice($new[$language], $after + 1, 0, [['label' => $label, 'value' => trim($text)]]);
        }
    }
    return array_filter($new, static fn(array $rows, string $language): bool => $rows !== $tables[$language], ARRAY_FILTER_USE_BOTH);
}

/** The text a "replace" item works on, as stored right now (read past the object cache). */
function boblift_product_specs_text(int $post_id, string $in): string
{
    global $wpdb;
    if ($in === 'seo_description') {
        return (string) $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_seo_description' ORDER BY meta_id LIMIT 1", $post_id));
    }
    $column = $in === 'excerpt' ? 'post_excerpt' : 'post_content';
    return (string) $wpdb->get_var($wpdb->prepare("SELECT {$column} FROM {$wpdb->posts} WHERE ID = %d", $post_id));
}

/** Applies one language's "replace" items to its texts. Returns in => new text (only texts that change), or an error. */
function boblift_product_specs_plan_texts(int $post_id, string $language, array $items): array|WP_Error
{
    $texts = [];
    foreach ($items as $item) {
        $in = is_array($item) ? (string) ($item['in'] ?? 'content') : '';
        $from = is_array($item) ? (string) ($item['from'] ?? '') : '';
        $to = is_array($item) ? (string) ($item['to'] ?? '') : '';
        if (!in_array($in, ['content', 'excerpt', 'seo_description'], true) || $from === '' || $from === $to) {
            return new WP_Error('boblift_specs_replace', sprintf('A %s "replace" item needs "from", a different "to" and "in" of content, excerpt or seo_description.', $language), ['status' => 400]);
        }
        if (!current_user_can('unfiltered_html')) {
            $to = $in === 'seo_description' ? sanitize_text_field($to) : wp_kses_post($to);
        }
        $text = $texts[$in] ?? boblift_product_specs_text($post_id, $in);
        $count = substr_count($text, $from);
        if ($count === 0 && $to !== '' && str_contains($text, $to)) {
            continue;
        }
        if ($count !== 1) {
            return new WP_Error('boblift_specs_replace', sprintf('In the %s %s, "%s" occurs %d times; it must occur exactly once.', $language, $in, $from, $count), ['status' => 400]);
        }
        $texts[$in] = str_replace($from, $to, $text);
    }
    foreach ($texts as $in => $text) {
        if ($text === boblift_product_specs_text($post_id, $in)) {
            unset($texts[$in]);
        }
    }
    return $texts;
}

/**
 * Writes one item's planned spec table and texts, each only if it is still what the plan was made from (byte for byte);
 * true when written, false when something changed meanwhile.
 */
function boblift_product_specs_write(int $post_id, array $old_rows, ?array $rows, array $old_texts, array $texts): bool
{
    global $wpdb;
    if ($rows !== null) {
        $current = array_values((array) boblift_decode_meta(get_post_meta($post_id, '_specs', true), []));
        if ($current !== $old_rows) {
            return false;
        }
    }
    foreach ($texts as $in => $text) {
        if (boblift_product_specs_text($post_id, $in) !== $old_texts[$in]) {
            return false;
        }
    }
    if ($rows !== null) {
        update_post_meta($post_id, '_specs', wp_slash((string) wp_json_encode($rows, JSON_UNESCAPED_UNICODE)));
    }
    $columns = array_intersect_key(['content' => 'post_content', 'excerpt' => 'post_excerpt'], $texts);
    foreach ($columns as $in => $column) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->posts} SET {$column} = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d",
            $texts[$in],
            current_time('mysql'),
            current_time('mysql', true),
            $post_id
        ));
    }
    if (isset($texts['seo_description'])) {
        update_post_meta($post_id, '_seo_description', wp_slash($texts['seo_description']));
    }
    clean_post_cache($post_id);
    if ($columns !== []) {
        wp_save_post_revision($post_id);
    }
    return true;
}

/** Plans the whole request for every language, then writes it item by item. Returns true or an error. */
function boblift_rest_save_product_specs(int $product_id, array $value): bool|WP_Error
{
    if (!current_user_can('edit_post', $product_id)) {
        return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit this product.', ['status' => rest_authorization_required_code()]);
    }
    $group = boblift_product_specs_group($product_id);
    if (($group['en'] ?? 0) !== $product_id) {
        return new WP_Error('boblift_specs_english', sprintf('Send boblift_specs to the English product (%d).', (int) ($group['en'] ?? 0)), ['status' => 400]);
    }
    foreach ($group as $id) {
        if (!current_user_can('edit_post', $id)) {
            return new WP_Error('rest_cannot_edit', sprintf('Sorry, you are not allowed to edit product %d.', $id), ['status' => rest_authorization_required_code()]);
        }
    }
    $replace = is_array($value['replace'] ?? null) ? $value['replace'] : [];
    if (array_diff_key($replace, $group) !== []) {
        return new WP_Error('boblift_specs_language', sprintf('"replace" names languages this product does not have: %s.', implode(', ', array_keys(array_diff_key($replace, $group)))), ['status' => 400]);
    }

    $tables = [];
    foreach ($group as $language => $id) {
        $tables[$language] = array_values((array) boblift_decode_meta(get_post_meta($id, '_specs', true), []));
    }
    $rows = isset($value['set']) || isset($value['insert']) ? boblift_product_specs_plan_rows($tables, $value) : [];
    if (is_wp_error($rows)) {
        return $rows;
    }
    $texts = [];
    $old_texts = [];
    foreach ($replace as $language => $items) {
        $planned = boblift_product_specs_plan_texts($group[$language], (string) $language, (array) $items);
        if (is_wp_error($planned)) {
            return $planned;
        }
        $texts[$language] = $planned;
        foreach (array_keys($planned) as $in) {
            $old_texts[$language][$in] = boblift_product_specs_text($group[$language], $in);
        }
    }

    $written = [];
    try {
        foreach ($group as $language => $id) {
            if (!isset($rows[$language]) && empty($texts[$language])) {
                continue;
            }
            // The English product's lock is already held by this REST request; another item's lock replaces it.
            if (!boblift_content_insert_lock($id)) {
                return boblift_product_specs_partial($written, $id, 'Another save of this item is in progress');
            }
            if (!boblift_product_specs_write($id, $tables[$language], $rows[$language] ?? null, $old_texts[$language] ?? [], $texts[$language] ?? [])) {
                return boblift_product_specs_partial($written, $id, 'It changed while the request was being checked');
            }
            $written[] = $id;
        }
    } finally {
        boblift_content_insert_lock(0);
    }
    return true;
}

function boblift_product_specs_partial(array $written, int $id, string $reason): WP_Error
{
    $done = $written === [] ? 'Nothing was written.' : sprintf('Already written: %s.', implode(', ', $written));
    return new WP_Error('boblift_specs_busy', sprintf('%s (item %d); send the request again. %s', $reason, $id, $done), ['status' => 409]);
}
