<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_mls_transport_import_term(array $term, string $taxonomy, string $lang, array $args, array &$map, array &$report): int
{
    $key = $taxonomy . ':' . $term['source_slug'];
    if (isset($map[$key])) {
        return $map[$key]['id'];
    }
    $source = boblift_mls_transport_source_term($taxonomy, $term['source_slug']);
    if (!$source) {
        $report['missing_sources'][] = $key;
        $report['skipped']++;
        $map[$key] = ['id' => 0, 'write' => false];
        return 0;
    }
    $target_id = (int) pll_get_term($source->term_id, $lang);
    if ($target_id > 0) {
        $target = get_term($target_id, $taxonomy);
        if (!$target instanceof WP_Term || pll_get_term_language($target_id, 'slug') !== $lang) {
            $report['errors'][] = '译文分类关联无效：' . $key;
            return 0;
        }
    }
    $created = $target_id === 0;
    if (!$created && !$args['overwrite']) {
        $report['skipped']++;
        $map[$key] = ['id' => $target_id, 'write' => false];
        return $target_id;
    }
    if (!$args['dry_run']) {
        // Only a new translation takes the package slug; an existing one keeps its deployed slug (terms get no old-slug redirects).
        $data = $created ? ['slug' => $term['slug']] : [];
        if (array_key_exists('description', $term)) {
            $data['description'] = $term['description'];
        }
        if ($created) {
            $result = wp_insert_term(wp_slash(boblift_mls_temp_term_name($source, $lang)), $taxonomy, wp_slash($data));
        } else {
            $data['name'] = $term['name'];
            $result = wp_update_term($target_id, $taxonomy, wp_slash($data));
        }
        if (is_wp_error($result)) {
            $report['errors'][] = $key . '：' . $result->get_error_message();
            return 0;
        }
        $target_id = (int) $result['term_id'];
        boblift_mls_link_term_translation($source->term_id, $target_id, $lang);
        if ($created) {
            $result = wp_update_term($target_id, $taxonomy, wp_slash(['name' => $term['name'], 'slug' => $term['slug']]));
            if (is_wp_error($result)) {
                $report['errors'][] = $key . '：' . $result->get_error_message();
            }
        }
    }
    $report[$created ? 'created' : 'updated']++;
    $map[$key] = ['id' => $args['dry_run'] && $created ? -1 : $target_id, 'write' => true];
    return $map[$key]['id'];
}

function boblift_mls_transport_term_meta(array $term, int $target_id, bool $dry_run, array &$report): void
{
    if ($term['taxonomy'] !== 'product_category') {
        return;
    }
    foreach (array_intersect_key($term['meta'] ?? [], array_flip(BOBLIFT_MLS_TRANSPORT_TERM_META_KEYS)) as $key => $value) {
        if (!$dry_run) {
            $result = update_term_meta($target_id, $key, wp_slash($value));
            if (is_wp_error($result) || ($result === false && get_term_meta($target_id, $key, true) !== $value)) {
                $report['errors'][] = $term['source_slug'] . '.' . $key . '：'
                    . (is_wp_error($result) ? $result->get_error_message() : '分类元数据写入失败。');
                continue;
            }
        }
        $report['terms_meta_written']++;
    }
}

function boblift_mls_transport_term_parent(array $term, string $lang, array $map, bool $dry_run, array &$report): void
{
    $target = $map[$term['taxonomy'] . ':' . $term['source_slug']] ?? null;
    if (!$target || !$target['write'] || $target['id'] === 0) {
        return;
    }
    if (!array_key_exists('parent_source_slug', $term)) {
        return;
    }
    $slug = (string) $term['parent_source_slug'];
    $parent = $slug !== '' ? boblift_mls_transport_source_term($term['taxonomy'], $slug) : null;
    $parent_id = $parent ? (int) pll_get_term($parent->term_id, $lang) : 0;
    if (!$parent_id && $dry_run && $parent) {
        $parent_id = $map[$term['taxonomy'] . ':' . $slug]['id'] ?? 0;
    }
    if ($slug !== '' && !$parent_id) {
        $report['warnings'][] = '缺少目标语言父分类：' . $slug;
        return;
    }
    if (!$dry_run) {
        $result = wp_update_term($target['id'], $term['taxonomy'], ['parent' => $parent_id]);
        if (is_wp_error($result)) {
            $report['errors'][] = $result->get_error_message();
        }
    }
}

function boblift_mls_transport_post_meta(array $post, int $target_id, bool $dry_run, array &$report): void
{
    foreach ($post['meta'] ?? [] as $key => $value) {
        if (!boblift_mls_transport_meta_allowed((string) $key) || !boblift_mls_transport_safe_value($value)
            || !boblift_mls_transport_safe_value($post['meta_rows'][$key] ?? [])) {
            $report['warnings'][] = '已忽略受保护或敏感 meta：' . $key;
            continue;
        }
        if (!$dry_run) {
            $expected = isset($post['meta_rows'][$key]) ? array_values((array) $post['meta_rows'][$key]) : [$value];
            if (isset($post['meta_rows'][$key]) || count((array) get_post_meta($target_id, $key)) > 1) {
                // Multiple rows, or a single value over several existing rows: replace every row.
                delete_post_meta($target_id, $key);
                foreach ($expected as $row) {
                    add_post_meta($target_id, $key, wp_slash($row));
                }
            } else {
                update_post_meta($target_id, $key, wp_slash($value));
            }
            // Write calls return false for unchanged values too, so the stored rows decide whether the write landed.
            if (array_values((array) get_post_meta($target_id, $key)) != $expected) {
                $report['errors'][] = '元数据写入失败：' . $target_id . ':' . $key;
            }
        }
    }
}

function boblift_mls_transport_thumbnail(?array $thumbnail, int $target_id, bool $dry_run, array &$report): void
{
    if ($thumbnail === null) {
        if (!$dry_run) {
            delete_post_thumbnail($target_id);
        }
        return;
    }
    $attachments = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 2,
        'meta_key' => '_wp_attached_file', 'meta_value' => $thumbnail['file'], 'lang' => '', 'suppress_filters' => true]);
    $attachments = array_values(array_filter($attachments, static fn($post): bool => get_post_meta($post->ID, '_wp_attached_file', true) === $thumbnail['file']));
    if (count($attachments) !== 1) {
        $report['warnings'][] = '特色图片未找到或路径不唯一：' . $thumbnail['file'];
        return;
    }
    if (!$dry_run) {
        set_post_thumbnail($target_id, $attachments[0]->ID);
        update_post_meta($attachments[0]->ID, '_wp_attachment_image_alt', wp_slash($thumbnail['alt'] ?? ''));
    }
}
