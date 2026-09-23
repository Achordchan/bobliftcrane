<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_upsert_post(string $post_type, string $slug, array $args): int
{
    $existing = get_page_by_path($slug, OBJECT, $post_type);
    $payload = array_merge([
        'post_type' => $post_type,
        'post_name' => $slug,
        'post_status' => 'publish',
    ], $args);

    if ($existing instanceof WP_Post) {
        $payload['ID'] = $existing->ID;
        $result = wp_update_post($payload, true);
        return is_wp_error($result) ? 0 : (int) $result;
    }
    $result = wp_insert_post($payload, true);
    return is_wp_error($result) ? 0 : (int) $result;
}

function boblift_import_asset_to_media(string $asset, int $parent_id = 0): int
{
    $source = boblift_asset_path($asset);
    if (!is_readable($source)) {
        return 0;
    }

    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => '_boblift_asset_source',
        'meta_value' => $asset,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);
    if ($existing) {
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = wp_tempnam(basename($source));
    copy($source, $tmp);
    $file = ['name' => basename($source), 'tmp_name' => $tmp];
    $attachment_id = media_handle_sideload($file, $parent_id);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }
    update_post_meta((int) $attachment_id, '_boblift_asset_source', $asset);
    return (int) $attachment_id;
}


function boblift_remove_default_content(): void
{
    foreach ([['post', 'hello-world'], ['page', 'sample-page'], ['page', 'privacy-policy']] as [$type, $slug]) {
        $post = get_page_by_path($slug, OBJECT, $type);
        if ($post instanceof WP_Post) {
            wp_delete_post($post->ID, true);
        }
    }
}

function boblift_import_current_content(): void
{
    boblift_remove_default_content();
    update_option('blogname', 'BOB-LIFT');
    update_option('blogdescription', 'Engineered Lifting Power for Global Projects');
    update_option('boblift_theme_options', boblift_default_options());

    $parent_labels = array_column(boblift_product_category_group_definitions(), 'title', 'slug');
    foreach (boblift_seed_product_categories() as $category) {
        $category_slug = (string) ($category['slug'] ?? '');
        $category_title = $parent_labels[$category_slug] ?? ($category['title'] ?? $category_slug);
        $term = term_exists($category['slug'], 'product_category');
        if (!$term) {
            $term = wp_insert_term($category_title, 'product_category', ['slug' => $category_slug, 'description' => $category['description'] ?? '']);
        }
        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
            wp_update_term($term_id, 'product_category', ['name' => $category_title, 'description' => $category['description'] ?? '']);
            update_term_meta($term_id, '_boblift_icon', $category['icon'] ?? 'truck-crane');
            update_term_meta($term_id, '_boblift_image', $category['image'] ?? '');
            update_term_meta($term_id, '_boblift_sort', $category['sort'] ?? 100);
        }
    }


    $parent_map = boblift_product_parent_category_map();
    foreach ($parent_map as $child_slug => $parent_slug) {
        $child = term_exists($child_slug, 'product_category');
        $parent = term_exists($parent_slug, 'product_category');
        if ($child && $parent && !is_wp_error($child) && !is_wp_error($parent)) {
            $child_id = is_array($child) ? (int) $child['term_id'] : (int) $child;
            $parent_id = is_array($parent) ? (int) $parent['term_id'] : (int) $parent;
            wp_update_term($child_id, 'product_category', ['parent' => $parent_id]);
        }
    }

    foreach (boblift_legacy_landing_categories() as $category) {
        $term = term_exists($category['slug'], 'legacy_landing');
        if (!$term) {
            $term = wp_insert_term($category['title'], 'legacy_landing', ['slug' => $category['slug'], 'description' => $category['description'] ?? '']);
        }
        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
            wp_update_term($term_id, 'legacy_landing', ['name' => $category['title'], 'description' => $category['description'] ?? '']);
            update_term_meta($term_id, '_boblift_icon', $category['icon'] ?? 'truck-crane');
            update_term_meta($term_id, '_boblift_image', $category['image'] ?? '');
            update_term_meta($term_id, '_boblift_sort', $category['sort'] ?? 100);
        }
        $old_product_term = get_term_by('slug', (string) ($category['slug'] ?? ''), 'product_category');
        if ($old_product_term instanceof WP_Term && (int) $old_product_term->count === 0) {
            wp_delete_term((int) $old_product_term->term_id, 'product_category');
        }
    }

    $page_ids = [];
    foreach ([
        ['Home', 'home', '', ''],
        ['Products', 'products', '', '/products.html'],
        ['Cases', 'cases', '', '/cases.html'],
        ['About BOB-LIFT', 'about', 'page-about.php', '/about.html'],
        ['Global Customer Visits', 'global-customer-visits', 'page-global-customer-visits.php', '/global-customer-visits.html'],
        ['Social Media', 'social-media', 'page-social-media.php', '/social-media.html'],
        ['Public Welfare', 'public-welfare', 'page-public-welfare.php', '/public-welfare.html'],
        ['News', 'news', 'page-news.php', '/news.html'],
        ['Contact Us', 'contact', 'page-contact.php', '/contact.html'],
        ['Privacy Policy', 'privacy-policy', 'page-privacy-policy.php', '/privacy-policy.html'],
        ['Terms of Use', 'terms-of-use', 'page-terms-of-use.php', '/terms-of-use.html'],
        ['Sitemap', 'sitemap', 'page-sitemap.php', '/sitemap.html'],
        ['Request a Quote', 'quote', 'page-quote.php', '/quote.html'],
        ['Inquiry Submitted', 'success', 'page-success.php', '/success.html'],
    ] as [$title, $slug, $template, $public_url]) {
        $id = boblift_upsert_post('page', $slug, ['post_title' => $title, 'post_content' => '']);
        if ($id && !is_wp_error($id) && $template) {
            update_post_meta($id, '_wp_page_template', $template);
        }
        if ($id && !is_wp_error($id) && $public_url !== '' && get_post_meta($id, '_boblift_public_url', true) === '') {
            update_post_meta($id, '_boblift_public_url', $public_url);
        }
        $page_ids[$slug] = $id;
    }
    if (!empty($page_ids['about']) && !is_wp_error($page_ids['about'])) {
        foreach (['global-customer-visits', 'social-media', 'public-welfare', 'contact'] as $child_slug) {
            if (!empty($page_ids[$child_slug]) && !is_wp_error($page_ids[$child_slug])) {
                wp_update_post([
                    'ID' => (int) $page_ids[$child_slug],
                    'post_parent' => (int) $page_ids['about'],
                ]);
            }
        }
    }
    if (!empty($page_ids['home'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_ids['home']);
    }

    foreach (boblift_products_seed() as $product) {
        $id = boblift_upsert_post('product', $product['slug'], [
            'post_title' => $product['title'],
            'post_excerpt' => $product['short_description'],
            'post_content' => boblift_product_content_from_seed($product),
            'menu_order' => (int) (1000 - ($product['popularity'] ?? 0)),
        ]);
        if (!$id || is_wp_error($id)) {
            continue;
        }
        wp_set_object_terms($id, $product['category'], 'product_category');
        foreach (['model', 'capacity', 'capacity_value', 'status', 'short_description'] as $key) {
            update_post_meta($id, '_' . $key, (string) ($product[$key] ?? ''));
        }
        update_post_meta($id, '_boom_type', boblift_normalize_product_boom_type((string) ($product['boom_type'] ?? '')));
        update_post_meta($id, '_applications', implode(',', $product['applications'] ?? []));
        update_post_meta($id, '_tags', implode(',', $product['tags'] ?? []));
        update_post_meta($id, '_specs', wp_json_encode($product['specs'] ?? [], JSON_UNESCAPED_UNICODE));
        update_post_meta($id, '_features', wp_json_encode($product['features'] ?? [], JSON_UNESCAPED_UNICODE));
        update_post_meta($id, '_gallery', wp_json_encode($product['gallery'] ?? [], JSON_UNESCAPED_UNICODE));
        update_post_meta($id, '_detail_images', wp_json_encode($product['detail_images'] ?? [], JSON_UNESCAPED_UNICODE));
        $image_id = boblift_import_asset_to_media($product['image'], $id);
        if ($image_id) {
            set_post_thumbnail($id, $image_id);
        }
    }

    foreach (boblift_articles_seed() as $article) {
        $id = boblift_upsert_post('post', $article['slug'], [
            'post_title' => $article['title'],
            'post_excerpt' => $article['excerpt'],
            'post_date' => $article['date'] . ' 09:00:00',
            'post_content' => boblift_sections_to_html($article['sections'] ?? []),
        ]);
        if ($id && !is_wp_error($id)) {
            wp_set_post_categories($id, [boblift_import_category($article['category'])]);
            $image_id = boblift_import_asset_to_media($article['image'], $id);
            if ($image_id) {
                set_post_thumbnail($id, $image_id);
            }
        }
    }

    foreach (boblift_cases_seed() as $case) {
        $id = boblift_upsert_post('case', $case['slug'], [
            'post_title' => $case['title'],
            'post_excerpt' => $case['summary'],
            'post_content' => boblift_sections_to_html($case['sections'] ?? []),
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, '_type', $case['type']);
            update_post_meta($id, '_summary', $case['summary']);
            update_post_meta($id, '_facts', wp_json_encode($case['facts'] ?? [], JSON_UNESCAPED_UNICODE));
            update_post_meta($id, '_sections', wp_json_encode($case['sections'] ?? [], JSON_UNESCAPED_UNICODE));
            $image_id = boblift_import_asset_to_media($case['image'], $id);
            if ($image_id) {
                set_post_thumbnail($id, $image_id);
            }
        }
    }

    flush_rewrite_rules();
}

function boblift_import_category(string $name): int
{
    $term = term_exists($name, 'category');
    if (!$term) {
        $term = wp_insert_term($name, 'category');
    }
    if (is_wp_error($term)) {
        return 1;
    }
    return is_array($term) ? (int) $term['term_id'] : (int) $term;
}

function boblift_sections_to_html(array $sections): string
{
    $html = '';
    foreach ($sections as $section) {
        $html .= '<h2>' . esc_html($section['heading'] ?? '') . '</h2><p>' . esc_html($section['body'] ?? '') . '</p>';
    }
    return $html;
}

function boblift_product_content_from_seed(array $product): string
{
    $html = '<p>' . esc_html($product['short_description'] ?? '') . '</p>';
    foreach ($product['features'] ?? [] as $feature) {
        $html .= '<h3>' . esc_html($feature['title'] ?? '') . '</h3><p>' . esc_html($feature['text'] ?? '') . '</p>';
    }
    return $html;
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('boblift import-current', static function (): void {
        boblift_import_current_content();
        WP_CLI::success('BOB-LIFT content imported. Refresh permalinks if your server still shows old routes.');
    });
}
