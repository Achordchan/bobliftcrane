<?php
/**
 * Previous models: configurations no longer built as standard (outside the technical department's current drawings)
 * but still built to order. Each keeps its own page and URL; it leaves the product listing, counts, menus, related
 * models and range comparisons, and is shown instead in the archive's model library and beside its current
 * equivalent on the product page.
 *
 * Marked with the product meta _boblift_lifecycle = "legacy" (empty or "current" is a current model). It is set on the
 * product edit screen or through REST (the boblift_lifecycle field of wp/v2/product); either way all translations follow.
 */

function boblift_register_product_lifecycle_meta(): void
{
    register_post_meta('product', '_boblift_lifecycle', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'description' => 'current (or empty) for a current model, legacy for a previous model built to order only.',
        'sanitize_callback' => static fn($value): string => in_array($value, ['current', 'legacy'], true) ? (string) $value : '',
        'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
    ]);
}
add_action('init', 'boblift_register_product_lifecycle_meta');

/**
 * REST: boblift_lifecycle on wp/v2/product reads "legacy" or "current" for any language version and, on update, marks
 * the product and all its translations. A field rather than show_in_rest meta, which needs custom-fields support.
 */
function boblift_register_product_lifecycle_rest_field(): void
{
    register_rest_field('product', 'boblift_lifecycle', [
        'get_callback' => static fn(array $post): string => boblift_product_is_legacy((int) $post['id']) ? 'legacy' : 'current',
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('edit_post', $post->ID)) {
                return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit this product.', ['status' => rest_authorization_required_code()]);
            }
            boblift_save_product_lifecycle($post->ID, $value === 'legacy' ? 'legacy' : '');
            return true;
        },
        'schema' => [
            'description' => 'current for a current model, legacy for a previous model listed only in the model library (built to order).',
            'type' => 'string',
            'enum' => ['current', 'legacy'],
            'context' => ['view', 'edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_product_lifecycle_rest_field');

/**
 * IDs of every previous model in any language and status: the marked products and all their translations.
 * Loaded once per request; a change to the mark starts again.
 */
function boblift_legacy_product_ids(bool $reset = false): array
{
    static $ids = null;
    if ($reset) {
        $ids = null;
        return [];
    }
    if ($ids !== null) {
        return $ids;
    }
    global $wpdb;
    $marked = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_boblift_lifecycle', 'legacy')));
    $ids = $marked;
    if (function_exists('pll_get_post_translations')) {
        foreach ($marked as $id) {
            $ids = array_merge($ids, array_map('intval', array_values(pll_get_post_translations($id))));
        }
    }
    $ids = array_values(array_unique($ids));
    return $ids;
}

function boblift_reset_legacy_product_ids($meta_id, $object_id, string $meta_key): void
{
    if ($meta_key === '_boblift_lifecycle') {
        boblift_legacy_product_ids(true);
    }
}
add_action('added_post_meta', 'boblift_reset_legacy_product_ids', 10, 3);
add_action('updated_post_meta', 'boblift_reset_legacy_product_ids', 10, 3);
add_action('deleted_post_meta', 'boblift_reset_legacy_product_ids', 10, 3);

function boblift_product_is_legacy(int $post_id): bool
{
    return in_array($post_id, boblift_legacy_product_ids(), true);
}

/** Product query arguments without the previous models (an explicit post__in list keeps only its current models). */
function boblift_without_legacy(array $args): array
{
    $legacy = boblift_legacy_product_ids();
    if ($legacy === []) {
        return $args;
    }
    if (!empty($args['post__in'])) {
        $kept = array_values(array_diff(array_map('intval', (array) $args['post__in']), $legacy));
        $args['post__in'] = $kept !== [] ? $kept : [0];
        return $args;
    }
    $args['post__not_in'] = array_values(array_unique(array_merge(array_map('intval', (array) ($args['post__not_in'] ?? [])), $legacy)));
    return $args;
}

/** The edit screen marks the product in every language at once (the mark is read for all translations anyway). */
function boblift_save_product_lifecycle(int $post_id, string $value): void
{
    $value = in_array($value, ['current', 'legacy'], true) ? $value : '';
    $ids = function_exists('pll_get_post_translations') ? array_map('intval', array_values(pll_get_post_translations($post_id))) : [];
    foreach (array_unique(array_merge([$post_id], $ids)) as $id) {
        if ($value === '') {
            delete_post_meta($id, '_boblift_lifecycle');
        } else {
            update_post_meta($id, '_boblift_lifecycle', $value);
        }
    }
}

/**
 * The models to set side by side on a product page: every model of the same capacity in its category, current ones
 * first. A previous model with no current model of its capacity is set beside the current models nearest to it.
 * Empty when no previous model is involved.
 */
function boblift_product_model_history(int $post_id, WP_Term $term): array
{
    $models = boblift_product_range_models($term, 'all');
    $own = null;
    foreach ($models as $item) {
        if ($item['id'] === $post_id) {
            $own = $item;
        }
    }
    if ($own === null) {
        return [];
    }
    // Models without a capacity figure have no class to compare in.
    if ($own['capacity'] <= 0) {
        return [];
    }
    $same = array_values(array_filter($models, static fn(array $item): bool => $item['capacity'] > 0 && abs($item['capacity'] - $own['capacity']) < 0.001));
    if ($own['legacy'] && !array_filter($same, static fn(array $item): bool => !$item['legacy'])) {
        $current = array_values(array_filter($models, static fn(array $item): bool => !$item['legacy']));
        $same = array_merge(boblift_nearest_capacity_models($current, $own['capacity']), $same);
    }
    if (count($same) < 2 || !array_filter($same, static fn(array $item): bool => $item['legacy'])) {
        return [];
    }
    usort($same, static fn(array $a, array $b): int => ($a['legacy'] <=> $b['legacy']) ?: ($a['capacity'] <=> $b['capacity']) ?: ($a['sections'] <=> $b['sections']) ?: strnatcasecmp($a['model'], $b['model']));
    return $same;
}

/** The models whose capacity is nearest to $capacity (all of them on a tie). */
function boblift_nearest_capacity_models(array $models, float $capacity): array
{
    if ($models === [] || $capacity <= 0) {
        return [];
    }
    $models = array_values(array_filter($models, static fn(array $item): bool => $item['capacity'] > 0));
    if ($models === []) {
        return [];
    }
    $gaps = array_map(static fn(array $item): float => abs($item['capacity'] - $capacity), $models);
    $nearest = min($gaps);
    return array_values(array_filter($models, static fn(array $item): bool => abs(abs($item['capacity'] - $capacity) - $nearest) < 0.001));
}

/**
 * The archive's model library: each category's previous models, with the current models of their capacity (or the
 * nearest) to point to. Categories in archive order; only those with previous models.
 */
function boblift_legacy_model_library(array $categories): array
{
    if (boblift_legacy_product_ids() === []) {
        return [];
    }
    $groups = [];
    $seen = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        $term = $slug !== '' ? get_term_by('slug', $slug, 'product_category') : null;
        if (!$term instanceof WP_Term) {
            continue;
        }
        // The models filed under this category itself; one filed under two listed categories appears in the first only.
        $models = boblift_product_range_models($term, 'all', false);
        $legacy = array_values(array_filter($models, static fn(array $item): bool => $item['legacy'] && !isset($seen[$item['id']])));
        foreach ($legacy as $item) {
            $seen[$item['id']] = true;
        }
        if ($legacy === []) {
            continue;
        }
        $current = array_values(array_filter($models, static fn(array $item): bool => !$item['legacy']));
        foreach ($legacy as $index => $item) {
            $same = $item['capacity'] > 0 ? array_values(array_filter($current, static fn(array $other): bool => $other['capacity'] > 0 && abs($other['capacity'] - $item['capacity']) < 0.001)) : [];
            $legacy[$index]['current'] = $same !== [] ? $same : boblift_nearest_capacity_models($current, $item['capacity']);
        }
        $groups[] = ['term' => $term, 'title' => (string) ($category['title'] ?? $term->name), 'models' => $legacy];
    }
    return $groups;
}
