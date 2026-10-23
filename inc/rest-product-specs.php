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
 *              plain text in that language's body ("in": "content", the default), excerpt ("excerpt") or SEO description
 *              ("seo_description"). "from" and "to" are plain text (no "<" or ">"), "to" is not empty, and "from" must
 *              occur exactly once outside the occurrences of "to", in the text of the page rather than inside a tag or
 *              comment. When it no longer occurs there and "to" does, the item is already done and is skipped.
 * Only the field itself may be sent: a request with any other parameter is refused before anything is saved.
 *
 * The request takes the per-post lock of every language version (the lock boblift_content_insert and every REST save
 * of these items take, so no such save can land in between), reads the stored tables and texts past the object cache,
 * works out every change and checks it, and writes them all in one database transaction. A request that cannot be
 * applied in full changes nothing, so sending the same request again is always safe. A row is changed in a translation
 * only while its table still follows the English one row for row (boblift_spec_rows_aligned()).
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
        'update_callback' => static function ($value, WP_Post $post) {
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

/** The core update runs before field callbacks, so a request that also changes anything else is refused before it. */
function boblift_rest_pre_insert_product_specs(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    if (is_wp_error($prepared) || $request['boblift_specs'] === null) {
        return $prepared;
    }
    $sent = array_keys(array_merge((array) $request->get_body_params(), (array) $request->get_json_params()));
    $other = array_diff($sent, ['boblift_specs', 'id']);
    if ($other !== []) {
        return new WP_Error('boblift_specs_alone', sprintf('Send "boblift_specs" on its own; this request also sends: %s.', implode(', ', $other)), ['status' => 400]);
    }
    return $prepared;
}
add_filter('rest_pre_insert_product', 'boblift_rest_pre_insert_product_specs', 10, 2);

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
        $label = is_array($item) ? trim((string) ($item['label'] ?? '')) : '';
        $index = boblift_product_specs_row_index($new['en'], $label);
        if (is_wp_error($index)) {
            return $index;
        }
        foreach (array_keys($new) as $language) {
            $text = boblift_product_specs_value_for($item['value'] ?? null, $language);
            if ($text === null || trim($text) === '') {
                return new WP_Error('boblift_specs_value', sprintf('"set" for "%s" gives no %s value.', $label, $language), ['status' => 400]);
            }
            $new[$language][$index]['value'] = sanitize_text_field($text);
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
            array_splice($new[$language], $after + 1, 0, [['label' => sanitize_text_field($label), 'value' => sanitize_text_field($text)]]);
        }
    }
    return array_filter($new, static fn(array $rows, string $language): bool => $rows !== $tables[$language], ARRAY_FILTER_USE_BOTH);
}

/** The stored meta row (meta_id and value) of one key, read past the object cache; null when there is none. */
function boblift_product_specs_meta_row(int $post_id, string $key): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id LIMIT 1", $post_id, $key), ARRAY_A);
    return is_array($row) ? ['id' => (int) $row['meta_id'], 'value' => (string) $row['meta_value']] : null;
}

/** The text a "replace" item works on, as stored right now (read past the object cache). */
function boblift_product_specs_text(int $post_id, string $in): string
{
    global $wpdb;
    if ($in === 'seo_description') {
        return (string) (boblift_product_specs_meta_row($post_id, '_seo_description')['value'] ?? '');
    }
    $column = $in === 'excerpt' ? 'post_excerpt' : 'post_content';
    return (string) $wpdb->get_var($wpdb->prepare("SELECT {$column} FROM {$wpdb->posts} WHERE ID = %d", $post_id));
}

/** Whether the byte at $offset is page text: not inside a tag or an HTML comment (block comments hold attributes). */
function boblift_product_specs_in_text(string $html, int $offset): bool
{
    $before = substr($html, 0, $offset);
    $comment = strrpos($before, '<!--');
    if ($comment !== false && strpos($before, '-->', $comment) === false) {
        return false;
    }
    $open = strrpos($before, '<');
    return $open === false || (int) strrpos($before, '>') > $open;
}

/**
 * Applies one "replace" item to a text. Returns the new text (unchanged when the item is already done) or an error. The
 * occurrences of "to" are set aside first, so a "to" that contains "from" ("90 kN" to "90 kN·m") is not matched again.
 */
function boblift_product_specs_replace_one(string $text, string $from, string $to, string $where): string|WP_Error
{
    $marker = "\u{E000}";
    $parts = explode($to, $text);
    $rest = implode($marker, $parts);
    $count = substr_count($rest, $from);
    if ($count === 0 && count($parts) > 1) {
        return $text;
    }
    if ($count !== 1) {
        return new WP_Error('boblift_specs_replace', sprintf('In the %s, "%s" occurs %d times outside "%s"; it must occur exactly once.', $where, $from, $count, $to), ['status' => 400]);
    }
    // Neither "to" nor its marker holds "<" or ">", so setting them aside leaves every tag and comment where it was.
    $at = strpos($rest, $from);
    if (!boblift_product_specs_in_text($rest, $at)) {
        return new WP_Error('boblift_specs_replace', sprintf('In the %s, "%s" is inside a tag or comment; only page text can be replaced.', $where, $from), ['status' => 400]);
    }
    return str_replace($marker, $to, substr($rest, 0, $at) . $to . substr($rest, $at + strlen($from)));
}

/** Applies one language's "replace" items to its stored texts. Returns in => [old, new] (only texts that change). */
function boblift_product_specs_plan_texts(int $post_id, string $language, array $items): array|WP_Error
{
    $texts = [];
    foreach ($items as $item) {
        $in = is_array($item) ? (string) ($item['in'] ?? 'content') : '';
        $from = is_array($item) ? (string) ($item['from'] ?? '') : '';
        $to = is_array($item) ? (string) ($item['to'] ?? '') : '';
        if (!in_array($in, ['content', 'excerpt', 'seo_description'], true) || $from === '' || $to === '' || $from === $to || preg_match('/[<>\x{E000}]/u', $from . $to)) {
            return new WP_Error('boblift_specs_replace', sprintf('A %s "replace" item needs plain-text "from" and a different, non-empty "to" (no "<" or ">"), and "in" of content, excerpt or seo_description.', $language), ['status' => 400]);
        }
        $texts[$in] ??= [boblift_product_specs_text($post_id, $in), null];
        $new = boblift_product_specs_replace_one($texts[$in][1] ?? $texts[$in][0], $from, $to, $language . ' ' . $in);
        if (is_wp_error($new)) {
            return $new;
        }
        $texts[$in][1] = $new;
    }
    return array_filter($texts, static fn(array $pair): bool => $pair[1] !== null && $pair[1] !== $pair[0]);
}

/** Takes (true) or releases (false) the per-post lock of each ID; on a failed take, releases what it took. */
function boblift_product_specs_locks(array $ids, bool $take): bool
{
    global $wpdb;
    sort($ids);
    $held = [];
    foreach ($ids as $id) {
        $name = 'boblift_content_insert_' . $id;
        if (!$take) {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $name));
            continue;
        }
        if ((int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', $name)) !== 1) {
            boblift_product_specs_locks($held, false);
            return false;
        }
        $held[] = $id;
    }
    return true;
}

/** Writes every planned change in one transaction. Returns true, or an error after rolling everything back. */
function boblift_product_specs_commit(array $group, array $rows, array $texts, array $spec_meta): bool|WP_Error
{
    global $wpdb;
    $now = current_time('mysql');
    $now_gmt = current_time('mysql', true);
    $queries = [];
    foreach ($rows as $language => $table) {
        $queries[] = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_id = %d", (string) wp_json_encode($table, JSON_UNESCAPED_UNICODE), $spec_meta[$language]['id']);
    }
    foreach ($texts as $language => $changes) {
        $id = $group[$language];
        foreach ($changes as $in => $pair) {
            if ($in === 'seo_description') {
                $meta = boblift_product_specs_meta_row($id, '_seo_description');
                $queries[] = $meta === null
                    ? $wpdb->prepare("INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES (%d, '_seo_description', %s)", $id, $pair[1])
                    : $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_id = %d", $pair[1], $meta['id']);
                continue;
            }
            $column = $in === 'excerpt' ? 'post_excerpt' : 'post_content';
            $queries[] = $wpdb->prepare("UPDATE {$wpdb->posts} SET {$column} = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d", $pair[1], $now, $now_gmt, $id);
        }
    }
    if ($queries === []) {
        return true;
    }
    $wpdb->query('START TRANSACTION');
    foreach ($queries as $query) {
        if ($wpdb->query($query) === false) {
            $error = $wpdb->last_error;
            $wpdb->query('ROLLBACK');
            return new WP_Error('boblift_specs_write', sprintf('Nothing was saved: a write failed (%s).', $error), ['status' => 500]);
        }
    }
    if ($wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('boblift_specs_write', 'Nothing was saved: the transaction could not be committed.', ['status' => 500]);
    }
    return true;
}

/** Locks every language version, plans the whole request from stored data, and writes it all or nothing. */
function boblift_rest_save_product_specs(int $product_id, array $value): bool|WP_Error
{
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
    if (!boblift_product_specs_locks(array_values($group), true)) {
        return new WP_Error('boblift_specs_busy', 'Another save of this product is in progress; nothing was saved, try again.', ['status' => 409]);
    }
    try {
        $spec_meta = [];
        $tables = [];
        foreach ($group as $language => $id) {
            $spec_meta[$language] = boblift_product_specs_meta_row($id, '_specs');
            $tables[$language] = array_values((array) boblift_decode_meta($spec_meta[$language]['value'] ?? '', []));
        }
        $rows = isset($value['set']) || isset($value['insert']) ? boblift_product_specs_plan_rows($tables, $value) : [];
        if (is_wp_error($rows)) {
            return $rows;
        }
        foreach (array_keys($rows) as $language) {
            if ($spec_meta[$language] === null) {
                return new WP_Error('boblift_specs_missing', sprintf('The %s product has no spec table to change.', $language), ['status' => 409]);
            }
        }
        $texts = [];
        foreach ($replace as $language => $items) {
            $planned = boblift_product_specs_plan_texts($group[$language], (string) $language, (array) $items);
            if (is_wp_error($planned)) {
                return $planned;
            }
            $texts[$language] = $planned;
        }
        $saved = boblift_product_specs_commit($group, $rows, $texts, $spec_meta);
        foreach ($group as $language => $id) {
            clean_post_cache($id);
            if (is_wp_error($saved)) {
                continue;
            }
            if (array_intersect_key($texts[$language] ?? [], ['content' => true, 'excerpt' => true]) !== []) {
                wp_save_post_revision($id);
            }
        }
        return $saved;
    } finally {
        boblift_product_specs_locks(array_values($group), false);
    }
}
