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
 *              ("seo_description"). "from" and "to" are plain text (no "<", ">" or quotes), "to" is not empty, and "from" must
 *              occur exactly once (not counting occurrences inside an occurrence of "to"), in page text: never inside a
 *              tag, attribute, comment, script or style. When it no longer occurs and "to" does, the item is already
 *              done and is skipped.
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
                // Core checked only the requested product; a translation is shown only to someone who may edit it.
                if (!current_user_can('edit_post', $id)) {
                    continue;
                }
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
    $sent = array_keys(array_merge((array) $request->get_query_params(), (array) $request->get_body_params(), (array) $request->get_json_params()));
    // Parameters that only shape the request or response, never the saved item.
    $other = array_diff($sent, ['boblift_specs', 'id', 'context', '_fields', '_envelope', '_locale', '_method', '_wpnonce']);
    if ($other !== []) {
        return new WP_Error('boblift_specs_alone', sprintf('Send "boblift_specs" on its own; this request also sends: %s.', implode(', ', $other)), ['status' => 400]);
    }
    // Check the whole request against the stored data first, so one that cannot be applied stops before the core update.
    $post_id = (int) ($prepared->ID ?? 0);
    $value = $request['boblift_specs'];
    $plan = boblift_product_specs_plan($post_id, is_array($value) ? $value : []);
    if (is_wp_error($plan)) {
        return $plan;
    }
    // The core update writes the stored row back; keep its body, excerpt and title exactly as stored rather than as
    // re-filtered for this user (an editor without unfiltered_html would otherwise lose an administrator's markup).
    boblift_product_specs_core_guard($post_id);
    return $prepared;
}
add_filter('rest_pre_insert_product', 'boblift_rest_pre_insert_product_specs', 10, 2);
add_action('rest_after_insert_product', static fn() => boblift_product_specs_core_guard(0));
// Also when a later step refuses the request, so the guard never outlives it.
add_filter('rest_request_after_callbacks', static function ($response) {
    boblift_product_specs_core_guard(0);
    return $response;
});

/** The product whose core update this request is protecting, 0 when none; set it by passing an ID. */
function boblift_product_specs_core_guard(?int $set = null): int
{
    static $active = 0;
    if ($set !== null) {
        $active = $set;
    }
    return $active;
}

add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    $post_id = (int) ($postarr['ID'] ?? 0);
    if ($post_id <= 0 || boblift_product_specs_core_guard() !== $post_id) {
        return $data;
    }
    global $wpdb;
    $stored = $wpdb->get_row($wpdb->prepare("SELECT post_content, post_excerpt, post_title FROM {$wpdb->posts} WHERE ID = %d", $post_id), ARRAY_A);
    if (is_array($stored)) {
        foreach ($stored as $column => $text) {
            $data[$column] = wp_slash((string) $text);
        }
    }
    return $data;
}, PHP_INT_MAX - 1, 2);

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
        // Matched and stored in the same sanitized form, so a retry finds the row it added.
        $english_label = sanitize_text_field((string) ($label_map['en'] ?? ''));
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
            $label = sanitize_text_field((string) ($label_map[$language] ?? ''));
            $text = boblift_product_specs_value_for($item['value'] ?? null, $language);
            if ($label === '' || $text === null || trim($text) === '') {
                return new WP_Error('boblift_specs_insert', sprintf('"insert" of "%s" gives no %s label or value.', $english_label, $language), ['status' => 400]);
            }
            array_splice($new[$language], $after + 1, 0, [['label' => $label, 'value' => sanitize_text_field($text)]]);
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

/**
 * Which bytes of an HTML string are page text: a copy of it with every tag (quoted attribute values included), comment
 * and script, style, textarea or title element, opened and up to its close or the end, blanked out with NUL bytes.
 */
function boblift_product_specs_text_mask(string $html): ?string
{
    $mask = preg_replace_callback(
        '~<!--.*?(?:-->|\z)|<(script|style|textarea|title)\b.*?(?:</\1\s*>|\z)|<(?:[^>"\']|"[^"]*"|\'[^\']*\')*(?:>|\z)~is',
        static fn(array $m): string => str_repeat("\0", strlen($m[0])),
        $html
    );
    // A regex failure (PCRE limits on a very long body) gives null: no mask, so nothing counts as page text.
    return is_string($mask) && strlen($mask) === strlen($html) ? $mask : null;
}

/** Byte offsets of every occurrence of $needle in $haystack, overlapping ones included. */
function boblift_product_specs_offsets(string $haystack, string $needle): array
{
    $found = [];
    for ($at = strpos($haystack, $needle); $at !== false; $at = strpos($haystack, $needle, $at + 1)) {
        $found[] = $at;
    }
    return $found;
}

/**
 * Applies one "replace" item to a text. Returns the new text (unchanged when the item is already done) or an error. An
 * occurrence of "from" that lies inside an occurrence of "to" ("90 kN" inside "90 kN·m") is the replacement already
 * made, so it is not counted again; when none is left and "to" is there, the item is done.
 */
function boblift_product_specs_replace_one(string $text, string $from, string $to, string $where): string|WP_Error
{
    $targets = boblift_product_specs_offsets($text, $to);
    $pending = boblift_product_specs_pending($text, $from, $to);
    if ($pending === [] && $targets !== []) {
        return $text;
    }
    if (count($pending) !== 1) {
        return new WP_Error('boblift_specs_replace', sprintf('In the %s, "%s" occurs %d times (not counting those inside "%s"); it must occur exactly once.', $where, $from, count($pending), $to), ['status' => 400]);
    }
    // Only page text: never a tag, an attribute value, a comment (block attributes) or a script or style body.
    $mask = boblift_product_specs_text_mask($text);
    if ($mask === null || str_contains(substr($mask, $pending[0], strlen($from)), "\0")) {
        return new WP_Error('boblift_specs_replace', sprintf('In the %s, "%s" is inside a tag, comment, script or style; only page text can be replaced.', $where, $from), ['status' => 400]);
    }
    return substr($text, 0, $pending[0]) . $to . substr($text, $pending[0] + strlen($from));
}

/** Offsets of the occurrences of "from" still to replace: those not lying inside an occurrence of "to". */
function boblift_product_specs_pending(string $text, string $from, string $to): array
{
    $targets = boblift_product_specs_offsets($text, $to);
    return array_values(array_filter(
        boblift_product_specs_offsets($text, $from),
        static function (int $at) use ($targets, $from, $to): bool {
            foreach ($targets as $target) {
                if ($at >= $target && $at + strlen($from) <= $target + strlen($to)) {
                    return false;
                }
            }
            return true;
        }
    ));
}

/** Applies one language's "replace" items to its stored texts. Returns in => [old, new] (only texts that change). */
function boblift_product_specs_plan_texts(int $post_id, string $language, array $items): array|WP_Error
{
    $texts = [];
    $spans = [];
    foreach ($items as $item) {
        $in = is_array($item) ? (string) ($item['in'] ?? 'content') : '';
        $from = is_array($item) ? (string) ($item['from'] ?? '') : '';
        $to = is_array($item) ? (string) ($item['to'] ?? '') : '';
        if (!in_array($in, ['content', 'excerpt', 'seo_description'], true) || $from === '' || $to === '' || $from === $to || preg_match('/[<>"\'\x00]/', $from . $to)) {
            return new WP_Error('boblift_specs_replace', sprintf('A %s "replace" item needs plain-text "from" and a different, non-empty "to" (no "<", ">" or quotes), and "in" of content, excerpt or seo_description.', $language), ['status' => 400]);
        }
        $texts[$in] ??= [boblift_product_specs_text($post_id, $in), null];
        // Each item must also stand on the stored text alone, on a stretch no earlier item touches: an item whose
        // "from" appears only through, or overlaps, an earlier item's text (a chain) could not tell on a retry that it
        // was already done.
        $alone = boblift_product_specs_replace_one($texts[$in][0], $from, $to, $language . ' ' . $in);
        if (is_wp_error($alone)) {
            return $alone;
        }
        // Only items still to do claim their stretch (the "from" occurrence); a done item touches nothing.
        foreach (boblift_product_specs_pending($texts[$in][0], $from, $to) as $at) {
            foreach ($spans[$in] ?? [] as [$start, $end]) {
                if ($at < $end && $at + strlen($from) > $start) {
                    return new WP_Error('boblift_specs_replace', sprintf('In the %s %s, "%s" overlaps the text of an earlier item; send one item for that stretch.', $language, $in, $from), ['status' => 400]);
                }
            }
            $spans[$in][] = [$at, $at + strlen($from)];
        }
        $new = boblift_product_specs_replace_one($texts[$in][1] ?? $texts[$in][0], $from, $to, $language . ' ' . $in);
        if (is_wp_error($new)) {
            return $new;
        }
        $texts[$in][1] = $new;
    }
    // Sending the same request again must change nothing: run every item once more over its planned result.
    foreach ($texts as $in => $pair) {
        $again = $pair[1] ?? $pair[0];
        foreach ($items as $item) {
            if ((string) ($item['in'] ?? 'content') === $in) {
                $again = boblift_product_specs_replace_one($again, (string) $item['from'], (string) $item['to'], $language . ' ' . $in);
                if (is_wp_error($again)) {
                    break;
                }
            }
        }
        if ($again !== ($pair[1] ?? $pair[0])) {
            return new WP_Error('boblift_specs_replace', sprintf('In the %s %s, sending these items again would change the text again (one item makes text another one replaces); split them into separate requests.', $language, $in), ['status' => 400]);
        }
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
    // Every write is a compare-and-set on the value this request read and planned from (byte for byte; the collation
    // ignores case and accents), so a save that slipped in between, such as a REST meta update that runs after its
    // post's lock is released, makes the write match nothing and the whole request roll back.
    $queries = [];
    foreach ($rows as $language => $table) {
        $queries[] = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_id = %d AND BINARY meta_value = BINARY %s", (string) wp_json_encode($table, JSON_UNESCAPED_UNICODE), $spec_meta[$language]['id'], $spec_meta[$language]['value']);
    }
    foreach ($texts as $language => $changes) {
        $id = $group[$language];
        foreach ($changes as $in => $pair) {
            if ($in === 'seo_description') {
                $queries[] = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = '_seo_description' AND BINARY meta_value = BINARY %s ORDER BY meta_id LIMIT 1", $pair[1], $id, $pair[0]);
                continue;
            }
            $column = $in === 'excerpt' ? 'post_excerpt' : 'post_content';
            $queries[] = $wpdb->prepare("UPDATE {$wpdb->posts} SET {$column} = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d AND BINARY {$column} = BINARY %s", $pair[1], $now, $now_gmt, $id, $pair[0]);
        }
    }
    if ($queries === []) {
        return true;
    }
    $wpdb->query('START TRANSACTION');
    foreach ($queries as $query) {
        $result = $wpdb->query($query);
        if ($result !== 1) {
            $error = $result === false ? $wpdb->last_error : 'it changed while the request was being worked out';
            $wpdb->query('ROLLBACK');
            return new WP_Error('boblift_specs_write', sprintf('Nothing was saved: a write failed (%s); try again.', $error), ['status' => $result === false ? 500 : 409]);
        }
    }
    if ($wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('boblift_specs_write', 'Nothing was saved: the transaction could not be committed.', ['status' => 500]);
    }
    return true;
}

/** Locks every language version, plans the whole request from stored data, and writes it all or nothing. */
/**
 * Works out a whole request from the stored data: ['group', 'rows', 'texts', 'spec_meta'], or an error when any part of it
 * cannot be applied. Run once as a check before the core update and again, under the locks, for the write.
 */
function boblift_product_specs_plan(int $product_id, array $value): array|WP_Error
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
    return ['group' => $group, 'rows' => $rows, 'texts' => $texts, 'spec_meta' => $spec_meta];
}

/** Locks every language version, plans the whole request again from stored data, and writes it all or nothing. */
function boblift_rest_save_product_specs(int $product_id, array $value): bool|WP_Error
{
    $group = boblift_product_specs_plan($product_id, $value);
    if (is_wp_error($group)) {
        return $group;
    }
    $group = $group['group'];
    if (!boblift_product_specs_locks(array_values($group), true)) {
        return new WP_Error('boblift_specs_busy', 'Another save of this product is in progress; nothing was saved, try again.', ['status' => 409]);
    }
    try {
        $plan = boblift_product_specs_plan($product_id, $value);
        if (is_wp_error($plan)) {
            return $plan;
        }
        if ($plan['group'] !== $group) {
            return new WP_Error('boblift_specs_busy', 'The product\'s translations changed meanwhile; nothing was saved, try again.', ['status' => 409]);
        }
        $saved = boblift_product_specs_commit($group, $plan['rows'], $plan['texts'], $plan['spec_meta']);
        foreach ($group as $language => $id) {
            clean_post_cache($id);
            if (!is_wp_error($saved) && array_intersect_key($plan['texts'][$language] ?? [], ['content' => true, 'excerpt' => true]) !== []) {
                wp_save_post_revision($id);
            }
        }
        return $saved;
    } finally {
        boblift_product_specs_locks(array_values($group), false);
    }
}
