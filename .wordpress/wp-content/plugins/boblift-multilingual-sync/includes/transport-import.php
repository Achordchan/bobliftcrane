<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/transport-objects.php';

function boblift_mls_import_language(array $package, array $args = []): array|WP_Error
{
    $args = boblift_mls_transport_args($args);
    if (is_wp_error($args)) {
        return $args;
    }
    $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'terms_meta_written' => 0,
        'errors' => [], 'missing_sources' => [], 'warnings' => $args['warnings']];
    $valid = boblift_mls_transport_validate($package, $report['warnings']);
    if (is_wp_error($valid)) {
        return $valid;
    }
    $lang = $package['language'];
    $term_map = [];
    if (in_array('terms', $args['only'], true)) {
        foreach ($package['terms'] ?? [] as $term) {
            $target_id = boblift_mls_transport_import_term($term, $term['taxonomy'], $lang, $args, $term_map, $report);
            if ($target_id !== 0 && ($term_map[$term['taxonomy'] . ':' . $term['source_slug']]['write'] ?? false)) {
                boblift_mls_transport_term_meta($term, $target_id, $args['dry_run'], $report);
            }
        }
        foreach ($package['terms'] ?? [] as $term) {
            boblift_mls_transport_term_parent($term, $lang, $term_map, $args['dry_run'], $report);
        }
    }
    $parents = [];
    if (in_array('posts', $args['only'], true)) {
        foreach ($package['posts'] ?? [] as $post) {
            boblift_mls_transport_import_post($post, $lang, $args, $term_map, $parents, $report);
        }
        $planned_pages = array_fill_keys(array_column($parents, 1), true);
        foreach ($parents as [$target_id, $target_slug, $parent_slug]) {
            $parent = $parent_slug !== '' ? boblift_mls_transport_source_post('page', $parent_slug) : null;
            $parent_id = $parent ? (int) pll_get_post($parent->ID, $lang) : 0;
            if (!$parent_id && $args['dry_run'] && $parent && isset($planned_pages[$parent_slug])) {
                continue;
            }
            if ($parent_slug !== '' && !$parent_id) {
                $report['warnings'][] = '缺少目标语言父页面：' . $parent_slug;
                continue;
            }
            if (!$args['dry_run']) {
                $result = wp_update_post(wp_slash(['ID' => $target_id, 'post_parent' => $parent_id,
                    'post_name' => $target_slug]), true);
                if (is_wp_error($result)) {
                    $report['errors'][] = $result->get_error_message();
                }
            }
        }
    }
    if (in_array('options', $args['only'], true)) {
        boblift_mls_transport_import_options($package['options'] ?? [], $lang, $args, $report);
    }
    if ($args['publish'] && !$args['dry_run'] && $report['errors'] === [] && $report['missing_sources'] === []
        && in_array('options', $args['only'], true)) {
        boblift_mls_set_language_status($lang, 'published', ['published_at' => time()]);
    }
    return $report;
}

function boblift_mls_transport_import_post(array $post, string $lang, array $args, array &$term_map, array &$parents, array &$report): void
{
    $source = boblift_mls_transport_source_post($post['type'], $post['source_slug']);
    if (!$source) {
        $report['missing_sources'][] = $post['type'] . ':' . $post['source_slug'];
        $report['skipped']++;
        return;
    }
    $target_id = (int) pll_get_post($source->ID, $lang);
    if ($target_id > 0) {
        $target = get_post($target_id);
        if (!$target instanceof WP_Post || $target->post_type !== $source->post_type || boblift_mls_post_language($target) !== $lang) {
            $report['errors'][] = '译文关联无效：' . $post['source_slug'];
            return;
        }
    }
    if ($target_id && !$args['overwrite']) {
        $report['skipped']++;
        return;
    }
    if (!post_type_exists($post['type'])) {
        $report['errors'][] = '站点未注册内容类型：' . $post['type'];
        return;
    }
    $created = $target_id === 0;
    // A new translation takes the source slug; an existing one keeps its own slug, so updates never move deployed URLs.
    $existing_slug = $created ? '' : (string) get_post($target_id)->post_name;
    $slug = $existing_slug !== '' ? $existing_slug : $source->post_name;
    if (!$args['dry_run']) {
        $data = ['post_type' => $post['type'], 'post_title' => $post['title'], 'post_content' => $post['content'] ?? '',
            'post_excerpt' => $post['excerpt'] ?? '', 'post_status' => $args['publish'] ? ($post['status'] ?? 'draft') : 'draft',
            'post_name' => $slug, 'menu_order' => $post['menu_order'] ?? 0];
        foreach (['date' => 'post_date', 'date_gmt' => 'post_date_gmt'] as $key => $field) {
            if (!empty($post[$key])) {
                $data[$field] = $post[$key];
            }
        }
        if ($target_id) {
            $data['ID'] = $target_id;
        }
        $result = $target_id ? wp_update_post(wp_slash($data), true) : wp_insert_post(wp_slash($data), true);
        if (is_wp_error($result)) {
            $report['errors'][] = $post['source_slug'] . '：' . $result->get_error_message();
            return;
        }
        $target_id = (int) $result;
        if ($created) {
            boblift_mls_copy_post_base_meta($source->ID, $target_id);
            foreach (get_post_meta($source->ID) as $key => $values) {
                $values = array_map('maybe_unserialize', $values);
                if (!boblift_mls_transport_meta_allowed($key) || !boblift_mls_transport_safe_value($values)) {
                    delete_post_meta($target_id, $key);
                } else {
                    delete_post_meta($target_id, $key);
                    foreach ($values as $value) {
                        add_post_meta($target_id, $key, wp_slash($value));
                    }
                }
            }
        }
        boblift_mls_link_post_translation($source->ID, $target_id, $lang);
        if ($created) {
            boblift_mls_copy_post_media_and_terms($source, $target_id, $lang);
        }
    }
    $report[$created ? 'created' : 'updated']++;
    if ($post['type'] === 'page') {
        $parents[] = [$target_id, $slug, $post['parent_source_slug'] ?? ''];
    } elseif (!$args['dry_run'] && $created) {
        $result = wp_update_post(wp_slash(['ID' => $target_id, 'post_name' => $slug]), true);
        if (is_wp_error($result)) {
            $report['errors'][] = $result->get_error_message();
        }
    }
    boblift_mls_transport_post_meta($post, $target_id, $args['dry_run'], $report);
    boblift_mls_transport_thumbnail($post['thumbnail'] ?? null, $target_id, $args['dry_run'], $report);
    foreach ($post['terms'] ?? [] as $taxonomy => $terms) {
        if (!in_array($taxonomy, ['category', 'post_tag', 'product_category'], true)) {
            continue;
        }
        if (!is_object_in_taxonomy($post['type'], $taxonomy)) {
            $report['warnings'][] = $post['type'] . ' 不支持分类：' . $taxonomy;
            continue;
        }
        $ids = [];
        $complete = true;
        foreach ($terms as $term) {
            if (in_array('terms', $args['only'], true)) {
                $id = boblift_mls_transport_import_term($term, $taxonomy, $lang, $args, $term_map, $report);
            } else {
                $source_term = boblift_mls_transport_source_term($taxonomy, $term['source_slug']);
                $id = $source_term ? (int) pll_get_term($source_term->term_id, $lang) : 0;
            }
            if ($id !== 0) {
                $ids[] = $id;
            } else {
                $complete = false;
                $report['warnings'][] = '未能关联分类：' . $taxonomy . ':' . $term['source_slug'];
            }
        }
        if (!$args['dry_run'] && $complete) {
            $result = wp_set_object_terms($target_id, $ids, $taxonomy, false);
            if (is_wp_error($result)) {
                $report['errors'][] = $result->get_error_message();
            }
        }
    }
}

function boblift_mls_transport_import_options(array $options, string $lang, array $args, array &$report): void
{
    $incoming = $options[boblift_mls_option_key($lang)] ?? [];
    foreach (boblift_mls_default_sections() as $section) {
        if (array_key_exists($section, $options)) {
            $incoming[$section] = $options[$section];
        }
    }
    $existing = boblift_mls_get_raw_options($lang);
    $write_options = false;
    boblift_mls_transport_unknown($incoming, boblift_mls_default_sections(), 'options.sections', $report['warnings']);
    foreach ($incoming as $section => $value) {
        if (!in_array($section, boblift_mls_default_sections(), true)) {
            continue;
        }
        if (!is_array($value)) {
            $report['errors'][] = '选项分区必须是数组：' . $section;
            continue;
        }
        if (array_key_exists($section, $existing) && !$args['overwrite']) {
            $report['skipped']++;
            continue;
        }
        $safe = boblift_mls_transport_clean($value);
        if ($safe !== $value) {
            $report['warnings'][] = '已过滤敏感选项：' . $section;
        }
        $report[array_key_exists($section, $existing) ? 'updated' : 'created']++;
        $existing[$section] = $safe;
        $write_options = true;
    }
    if (!$args['dry_run'] && $write_options) {
        // update_option() also returns false for an unchanged value, so a read-back decides whether the write landed.
        if (!boblift_mls_update_language_options($existing, $lang) && boblift_mls_get_raw_options($lang) !== $existing) {
            $report['errors'][] = '语言选项写入失败：' . $lang;
            return;
        }
    }
    if (isset($options['languages'][$lang])) {
        $state = boblift_mls_state();
        if (isset($state['languages'][$lang]) && !$args['overwrite']) {
            $report['skipped']++;
            return;
        }
        $report[isset($state['languages'][$lang]) ? 'updated' : 'created']++;
        $record = boblift_mls_transport_clean($options['languages'][$lang]);
        if ($record !== $options['languages'][$lang]) {
            $report['warnings'][] = '已过滤敏感语言状态字段。';
        }
        if (!$args['dry_run']) {
            $state['languages'][$lang] = array_merge($state['languages'][$lang] ?? [], $record);
            if (!boblift_mls_update_state($state) && boblift_mls_state() !== $state) {
                $report['errors'][] = '语言状态写入失败：' . $lang;
            }
        }
    }
}
