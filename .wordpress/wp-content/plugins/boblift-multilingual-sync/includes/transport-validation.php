<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_mls_transport_secret(string $key): bool
{
    return (bool) preg_match('/password|passwd|secret|token|api[_-]?key|private[_-]?key|authorization|credential|polytranslate|form_security/i', $key);
}

function boblift_mls_transport_meta_allowed(string $key): bool
{
    return !boblift_mls_transport_secret($key)
        && !preg_match('/^(_edit_|_wp_old_|_thumbnail_id|_wp_attached_file|_pll|_polylang|_icl_)/', $key);
}

function boblift_mls_transport_safe_value(mixed $value): bool
{
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            if (boblift_mls_transport_secret((string) $key) || !boblift_mls_transport_safe_value($child)) {
                return false;
            }
        }
        return true;
    }
    return $value === null || is_scalar($value);
}

function boblift_mls_transport_clean(array $value): array
{
    foreach ($value as $key => $child) {
        if (boblift_mls_transport_secret((string) $key) || is_object($child) || is_resource($child)) {
            unset($value[$key]);
        } elseif (is_array($child)) {
            $value[$key] = boblift_mls_transport_clean($child);
        }
    }
    return $value;
}

function boblift_mls_transport_unknown(array $value, array $allowed, string $path, array &$warnings): void
{
    foreach (array_diff(array_keys($value), $allowed) as $key) {
        $warnings[] = $path . '.' . $key . '：未知字段已忽略。';
    }
}

function boblift_mls_transport_args(array $args): array|WP_Error
{
    $result = ['dry_run' => false, 'overwrite' => true, 'publish' => false, 'only' => ['posts', 'terms', 'options'], 'warnings' => []];
    boblift_mls_transport_unknown($args, ['dry_run', 'overwrite', 'publish', 'only'], 'args', $result['warnings']);
    foreach (['dry_run', 'overwrite', 'publish'] as $key) {
        if (array_key_exists($key, $args)) {
            $value = filter_var($args[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null || is_array($args[$key])) {
                return boblift_mls_transport_error($key . ' 必须是布尔值。');
            }
            $result[$key] = $value;
        }
    }
    if (isset($args['only'])) {
        $only = is_string($args['only']) ? explode(',', $args['only']) : $args['only'];
        if (!is_array($only) || count(array_filter($only, 'is_string')) !== count($only)
            || array_diff($only, ['posts', 'terms', 'options'])) {
            return boblift_mls_transport_error('only 只能包含 posts、terms、options。');
        }
        $result['only'] = array_values(array_unique($only));
    }
    return $result;
}

function boblift_mls_transport_validate(array $package, array &$warnings): true|WP_Error
{
    if (($package['format'] ?? null) !== 1 || !is_string($package['language'] ?? null)
        || ($package['source_language'] ?? null) !== boblift_mls_source_language()) {
        return boblift_mls_transport_error('数据包格式、目标语言或主维护语言不匹配。');
    }
    $valid = boblift_mls_transport_language($package['language']);
    if (is_wp_error($valid)) {
        return $valid;
    }
    boblift_mls_transport_unknown($package, ['format', 'source_language', 'language', 'exported_at', 'site', 'posts', 'terms', 'options', 'warnings'], 'package', $warnings);
    foreach (['posts', 'terms', 'options'] as $key) {
        if (isset($package[$key]) && !is_array($package[$key])) {
            return boblift_mls_transport_error($key . ' 必须是数组或对象。');
        }
    }
    foreach ($package['posts'] ?? [] as $post) {
        if (!is_array($post) || !in_array($post['type'] ?? null, ['post', 'page', 'product', 'case'], true)
            || !is_string($post['source_slug'] ?? null) || $post['source_slug'] === '' || !is_string($post['title'] ?? null)) {
            return boblift_mls_transport_error('每篇内容必须包含有效的 type、source_slug 和 title。');
        }
        boblift_mls_transport_unknown($post, ['type', 'source_slug', 'source_title', 'slug', 'status', 'title', 'excerpt', 'content', 'date', 'date_gmt', 'parent_source_slug', 'menu_order', 'meta', 'meta_rows', 'thumbnail', 'terms', 'terms_incomplete', 'protected'], 'posts', $warnings);
        foreach (['source_title', 'slug', 'status', 'excerpt', 'content', 'date', 'date_gmt', 'parent_source_slug'] as $key) {
            if (isset($post[$key]) && !is_string($post[$key])) {
                return boblift_mls_transport_error('内容字段 ' . $key . ' 必须是字符串。');
            }
        }
        if (isset($post['status']) && !in_array($post['status'], ['publish', 'draft'], true)) {
            return boblift_mls_transport_error('内容状态只能是 publish 或 draft。');
        }
        if (isset($post['protected']) && !is_bool($post['protected'])) {
            return boblift_mls_transport_error('protected 必须是布尔值。');
        }
        if (isset($post['terms_incomplete']) && (!is_array($post['terms_incomplete']) || !array_is_list($post['terms_incomplete'])
            || array_filter($post['terms_incomplete'], static fn($taxonomy): bool => !is_string($taxonomy)) !== [])) {
            return boblift_mls_transport_error('terms_incomplete 必须是分类名列表。');
        }
        if (isset($post['menu_order']) && !is_int($post['menu_order'])) {
            return boblift_mls_transport_error('menu_order 必须是整数。');
        }
        foreach (['meta', 'meta_rows', 'terms'] as $key) {
            if (isset($post[$key]) && !is_array($post[$key])) {
                return boblift_mls_transport_error($key . ' 必须是对象。');
            }
        }
        foreach ($post['meta_rows'] ?? [] as $key => $rows) {
            if (!is_array($rows) || !array_is_list($rows) || count($rows) < 2
                || !array_key_exists($key, $post['meta'] ?? []) || $post['meta'][$key] !== $rows[0]) {
                return boblift_mls_transport_error('meta_rows 必须与 meta 首值一致且包含至少两个值。');
            }
        }
        if (isset($post['thumbnail'])) {
            if (!is_array($post['thumbnail']) || !is_string($post['thumbnail']['file'] ?? null)
                || !is_string($post['thumbnail']['alt'] ?? '')) {
                return boblift_mls_transport_error('thumbnail 必须包含 file 和可选 alt。');
            }
            boblift_mls_transport_unknown($post['thumbnail'], ['file', 'alt'], 'thumbnail', $warnings);
        }
        foreach ($post['terms'] ?? [] as $taxonomy => $terms) {
            if (!in_array($taxonomy, ['category', 'post_tag', 'product_category'], true)) {
                $warnings[] = '未知 taxonomy：' . $taxonomy;
                continue;
            }
            if (!is_array($terms)) {
                return boblift_mls_transport_error('内容分类必须是数组。');
            }
            foreach ($terms as $term) {
                $valid = boblift_mls_transport_validate_term($term, false, $warnings);
                if (is_wp_error($valid)) {
                    return $valid;
                }
            }
        }
    }
    foreach ($package['terms'] ?? [] as $term) {
        $valid = boblift_mls_transport_validate_term($term, true, $warnings);
        if (is_wp_error($valid)) {
            return $valid;
        }
    }
    $options = $package['options'] ?? [];
    foreach ($options as $key => $value) {
        if ($key === boblift_mls_option_key($package['language']) || in_array($key, boblift_mls_default_sections(), true)) {
            if (!is_array($value)) {
                return boblift_mls_transport_error('主题选项必须是对象或数组。');
            }
            if ($key === boblift_mls_option_key($package['language'])) {
                foreach ($value as $section => $section_value) {
                    if (in_array($section, boblift_mls_default_sections(), true) && !is_array($section_value)) {
                        return boblift_mls_transport_error('主题选项分区必须是数组。');
                    }
                }
            }
        } elseif ($key === 'languages') {
            if (!is_array($value) || !is_array($value[$package['language']] ?? null)) {
                return boblift_mls_transport_error('languages 必须包含目标语言状态对象。');
            }
            $status = $value[$package['language']]['status'] ?? 'new';
            if (!in_array($status, ['new', 'staged', 'translating', 'ready', 'published'], true)) {
                return boblift_mls_transport_error('语言状态无效。');
            }
            boblift_mls_transport_unknown($value, [$package['language']], 'languages', $warnings);
        } else {
            $warnings[] = 'options.' . $key . '：未知选项已忽略。';
        }
    }
    return true;
}

function boblift_mls_transport_validate_term(mixed $term, bool $top_level, array &$warnings): true|WP_Error
{
    if (!is_array($term) || !is_string($term['source_slug'] ?? null) || $term['source_slug'] === ''
        || !is_string($term['slug'] ?? null) || !is_string($term['name'] ?? null)
        || ($top_level && !in_array($term['taxonomy'] ?? null, ['category', 'post_tag', 'product_category'], true))) {
        return boblift_mls_transport_error('分类字段无效。');
    }
    foreach (['description', 'parent_source_slug'] as $key) {
        if (isset($term[$key]) && !is_string($term[$key])) {
            return boblift_mls_transport_error('分类字段 ' . $key . ' 必须是字符串。');
        }
    }
    $allowed = $top_level ? ['taxonomy', 'source_slug', 'slug', 'name', 'description', 'parent_source_slug'] : ['source_slug', 'slug', 'name'];
    if ($top_level && $term['taxonomy'] === 'product_category') {
        $allowed[] = 'meta';
        if (array_key_exists('meta', $term)) {
            if (!is_array($term['meta'])) {
                return boblift_mls_transport_error('分类 meta 必须是对象。');
            }
            boblift_mls_transport_unknown($term['meta'], BOBLIFT_MLS_TRANSPORT_TERM_META_KEYS, 'terms.meta', $warnings);
            foreach (array_intersect_key($term['meta'], array_flip(BOBLIFT_MLS_TRANSPORT_TERM_META_KEYS)) as $key => $value) {
                if (!is_string($value)) {
                    return boblift_mls_transport_error('分类 meta ' . $key . ' 必须是字符串。');
                }
                $length = preg_match_all('/./us', $value);
                if ($length === false || $length > 20000) {
                    return boblift_mls_transport_error('分类 meta ' . $key . ' 必须是有效 UTF-8 且不超过 20000 个字符。');
                }
            }
        }
    }
    boblift_mls_transport_unknown($term, $allowed, 'terms', $warnings);
    return true;
}
