<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_mls_export_language(string $lang, array $args = []): array|WP_Error
{
    $valid = boblift_mls_transport_language($lang);
    if (is_wp_error($valid)) {
        return $valid;
    }
    $package = ['format' => 1, 'source_language' => boblift_mls_source_language(), 'language' => $lang,
        'exported_at' => gmdate('c'), 'site' => home_url(), 'posts' => [], 'terms' => [], 'options' => [], 'warnings' => []];
    boblift_mls_transport_unknown($args, [], 'args', $package['warnings']);
    foreach (['category', 'post_tag', 'product_category'] as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $lang]);
        if (is_wp_error($terms)) {
            return $terms;
        }
        foreach ($terms as $term) {
            $record = boblift_mls_transport_export_term($term, $lang);
            if ($record !== null) {
                $package['terms'][] = $record;
            }
        }
    }
    for ($page = 1; ; $page++) {
        $posts = get_posts(['post_type' => ['post', 'page', 'product', 'case'], 'post_status' => ['publish', 'draft'],
            'posts_per_page' => 100, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $lang,
            'suppress_filters' => false]);
        foreach ($posts as $post) {
            if (boblift_mls_post_language($post) !== $lang) {
                continue;
            }
            $source = get_post((int) pll_get_post($post->ID, boblift_mls_source_language()));
            if (!$source instanceof WP_Post || $source->post_type !== $post->post_type
                || boblift_mls_post_language($source) !== boblift_mls_source_language()) {
                continue;
            }
            $parent = $source->post_parent ? get_post($source->post_parent) : null;
            $record = ['type' => $post->post_type, 'source_slug' => $source->post_name, 'source_title' => $source->post_title,
                'slug' => $post->post_name, 'status' => $post->post_status, 'title' => $post->post_title,
                'excerpt' => $post->post_excerpt, 'content' => $post->post_content, 'date' => $post->post_date,
                'date_gmt' => $post->post_date_gmt, 'parent_source_slug' => $parent instanceof WP_Post ? $parent->post_name : '',
                'menu_order' => (int) $post->menu_order, 'meta' => [], 'thumbnail' => null, 'terms' => []];
            if ($post->post_password !== '') {
                $record['protected'] = true;
            }
            foreach (get_post_meta($post->ID) as $key => $values) {
                if (!boblift_mls_transport_meta_allowed($key)) {
                    continue;
                }
                $values = array_map('maybe_unserialize', $values);
                if (!boblift_mls_transport_safe_value($values)) {
                    $package['warnings'][] = '已忽略不安全的 meta：' . $key;
                    continue;
                }
                $record['meta'][$key] = $values[0] ?? '';
                if (count($values) > 1) {
                    $record['meta_rows'][$key] = $values;
                }
            }
            $thumbnail = (int) get_post_thumbnail_id($post->ID);
            $file = $thumbnail ? get_post_meta($thumbnail, '_wp_attached_file', true) : '';
            if (is_string($file) && $file !== '') {
                $record['thumbnail'] = ['file' => $file, 'alt' => (string) get_post_meta($thumbnail, '_wp_attachment_image_alt', true)];
            }
            foreach (['category', 'post_tag', 'product_category'] as $taxonomy) {
                if (!is_object_in_taxonomy($post->post_type, $taxonomy)) {
                    continue;
                }
                $terms = wp_get_object_terms($post->ID, $taxonomy);
                if (is_wp_error($terms)) {
                    return $terms;
                }
                $record['terms'][$taxonomy] = [];
                foreach ($terms as $term) {
                    $term_record = boblift_mls_transport_export_term($term, $lang);
                    if ($term_record !== null) {
                        $record['terms'][$taxonomy][] = array_intersect_key($term_record, array_flip(['source_slug', 'slug', 'name']));
                    } else {
                        // A term without a source-language link cannot be matched on import, so the list is partial.
                        $record['terms_incomplete'][] = $taxonomy;
                    }
                }
            }
            $package['posts'][] = $record;
        }
        if (count($posts) < 100) {
            break;
        }
    }
    $options = boblift_mls_get_raw_options($lang);
    $package['options'][boblift_mls_option_key($lang)] = boblift_mls_transport_clean(array_intersect_key($options, array_flip(boblift_mls_default_sections())));
    $state = boblift_mls_state();
    $package['options']['languages'] = [$lang => boblift_mls_transport_clean((array) ($state['languages'][$lang] ?? ['status' => 'new']))];
    return $package;
}

function boblift_mls_transport_export_term(WP_Term $term, string $lang): ?array
{
    if (pll_get_term_language($term->term_id, 'slug') !== $lang) {
        return null;
    }
    $source = get_term((int) pll_get_term($term->term_id, boblift_mls_source_language()), $term->taxonomy);
    if (!$source instanceof WP_Term || pll_get_term_language($source->term_id, 'slug') !== boblift_mls_source_language()) {
        return null;
    }
    $parent = $source->parent ? get_term($source->parent, $source->taxonomy) : null;
    $record = ['taxonomy' => $term->taxonomy, 'source_slug' => $source->slug, 'slug' => $term->slug,
        'name' => $term->name, 'description' => $term->description,
        'parent_source_slug' => $parent instanceof WP_Term ? $parent->slug : ''];
    if ($term->taxonomy === 'product_category') {
        foreach (BOBLIFT_MLS_TRANSPORT_TERM_META_KEYS as $key) {
            if (metadata_exists('term', $term->term_id, $key)) {
                $record['meta'][$key] = get_term_meta($term->term_id, $key, true);
            }
        }
    }
    return $record;
}
