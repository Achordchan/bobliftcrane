<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search title and description of products, posts, pages and product/news categories, editable through REST so they can be
 * tuned page by page without the admin screens:
 *   POST /wp/v2/product/<id>, /wp/v2/posts/<id> or /wp/v2/pages/<id> with {"meta": {"_seo_title": "...", "_seo_description": "..."}}
 *   POST /wp/v2/product_category/<id> or /wp/v2/categories/<id> with {"meta": {"_boblift_seo_title": "...", "_boblift_seo_description": "..."}}
 * The title is used as written (no brand is appended). An empty value falls back to the generated one
 * (boblift_seo_post_title() and friends in setup.php). Each Polylang language version holds its own copy.
 */
function boblift_register_seo_rest_meta(): void
{
    $fields = [
        'title' => 'sanitize_text_field',
        'description' => 'sanitize_text_field',
    ];
    foreach (['product', 'post', 'page'] as $post_type) {
        foreach ($fields as $field => $sanitize) {
            register_post_meta($post_type, '_seo_' . $field, [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => $sanitize,
                'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
            ]);
        }
    }
    // The core REST API only exposes registered meta on post types that support custom fields.
    add_post_type_support('product', 'custom-fields');

    foreach (['product_category', 'category'] as $taxonomy) {
        foreach ($fields as $field => $sanitize) {
            register_term_meta($taxonomy, '_boblift_seo_' . $field, [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => $sanitize,
                'auth_callback' => static fn(): bool => current_user_can('manage_categories'),
            ]);
        }
    }
}
add_action('init', 'boblift_register_seo_rest_meta', 20);
