<?php
// One-time move of two product categories, in every language (2026-11): the electric mini cranes become a family of
// their own (out of Other Cranes) and aerial work platforms go under Other Cranes until their new drawings arrive.
// Families then list as truck, marine, forklift fly jib, electric mini, other (their English sort orders).

const BOBLIFT_FAMILY_LAYOUT_OPTION = 'boblift_family_layout_2026_11';

/**
 * Moves the categories once and records that it ran. The parent is written to the term_taxonomy row directly, as
 * wp_update_term() would re-check the slug, which every language's copy shares (/zh/electric-mini-cranes): slugs,
 * addresses and the products filed under each category stay as they are. Only a category still where it was before is
 * moved, so a later change made in the backend is never undone.
 */
function boblift_migrate_family_layout_2026_11(): void
{
    if (get_option(BOBLIFT_FAMILY_LAYOUT_OPTION) || !taxonomy_exists('product_category')) {
        return;
    }
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare("SELECT term_id, parent FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", 'product_category'));
    if (!$rows) {
        return;
    }
    $language_of = static fn(int $id): string => function_exists('pll_get_term_language') ? (string) pll_get_term_language($id) : '';
    $by_source = [];
    $parent_of = [];
    foreach ($rows as $row) {
        $term = get_term((int) $row->term_id, 'product_category');
        if (!$term instanceof WP_Term) {
            continue;
        }
        $source = function_exists('boblift_source_term_slug') ? boblift_source_term_slug($term) : $term->slug;
        $by_source[$source][$language_of($term->term_id)] = (int) $term->term_id;
        $parent_of[(int) $term->term_id] = (int) $row->parent;
    }
    // A failed write leaves the run unrecorded, so the next request tries again; moves already made no longer match.
    $failed = false;
    $set_parent = static function (int $term_id, int $parent) use ($wpdb, &$parent_of, &$failed): void {
        if ($wpdb->update($wpdb->term_taxonomy, ['parent' => $parent], ['term_id' => $term_id, 'taxonomy' => 'product_category']) === false) {
            $failed = true;
            return;
        }
        $parent_of[$term_id] = $parent;
        clean_term_cache($term_id, 'product_category');
    };
    $other = $by_source['other-cranes'] ?? [];
    foreach ($by_source['electric-mini-cranes'] ?? [] as $language => $term_id) {
        if (($other[$language] ?? 0) > 0 && $parent_of[$term_id] === $other[$language]) {
            $set_parent($term_id, 0);
        }
    }
    foreach ($by_source['aerial-work-platform'] ?? [] as $language => $term_id) {
        if (($other[$language] ?? 0) > 0 && $parent_of[$term_id] === 0) {
            $set_parent($term_id, $other[$language]);
        }
    }
    // Translations take the English category's sort order (boblift_source_term_sort()).
    foreach (['electric-mini-cranes' => 70, 'aerial-work-platform' => 130] as $source => $sort) {
        $english = (int) ($by_source[$source]['en'] ?? $by_source[$source][''] ?? 0);
        if ($english > 0) {
            update_term_meta($english, '_boblift_sort', (string) $sort);
            if ((string) get_term_meta($english, '_boblift_sort', true) !== (string) $sort) {
                $failed = true;
            }
        }
    }
    delete_option('product_category_children');
    if ($failed) {
        return;
    }
    update_option(BOBLIFT_FAMILY_LAYOUT_OPTION, gmdate('c'), false);
}
// After Polylang has loaded its term languages.
add_action('init', 'boblift_migrate_family_layout_2026_11', 99);
