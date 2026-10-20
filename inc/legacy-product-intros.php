<?php
/**
 * The old site's product pages rank on their "Why ...?" heading and the paragraphs around it; the English bodies written
 * from the technical drawings dropped them. That copy goes back into each English product body once, word for word, as one
 * h3 section after the lead. Sentences whose figures disagree with the drawings, or that promise things nobody has
 * confirmed (lifetime replacement, 48-hour delivery, a patent), were left out of legacy-product-intros.json.
 * Translations keep their own bodies: the old site's language pages were never matched to products.
 */
function boblift_legacy_product_intros(): array
{
    $file = get_template_directory() . '/inc/legacy-product-intros.json';
    $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data['products'] ?? null) ? $data['products'] : [];
}

function boblift_legacy_product_intro_html(array $intro): string
{
    $html = '<!-- wp:heading {"level":3} -->' . "\n" . '<h3 class="wp-block-heading">' . esc_html((string) $intro['heading']) . '</h3>' . "\n" . '<!-- /wp:heading -->' . "\n\n";
    foreach ((array) $intro['paragraphs'] as $paragraph) {
        $html .= '<!-- wp:paragraph -->' . "\n" . '<p>' . esc_html((string) $paragraph) . '</p>' . "\n" . '<!-- /wp:paragraph -->' . "\n\n";
    }
    return $html;
}

/**
 * Puts the section before the body's first h3 (so it follows the lead, ahead of attachments and certification), or at
 * the end when the body has no h3. Returns null when the body already carries the heading.
 */
function boblift_insert_legacy_product_intro(string $content, array $intro): ?string
{
    $heading = (string) $intro['heading'];
    if ($heading === '' || stripos(html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES), $heading) !== false) {
        return null;
    }
    $section = boblift_legacy_product_intro_html($intro);
    if (preg_match('/(?:<!-- wp:heading\b[^>]*-->\s*)?<h3\b/i', $content, $match, PREG_OFFSET_CAPTURE)) {
        return substr($content, 0, $match[0][1]) . $section . substr($content, $match[0][1]);
    }
    return rtrim($content) . "\n\n" . $section;
}

function boblift_restore_legacy_product_intros(): void
{
    if (get_option('boblift_legacy_product_intros_v1') !== false || !post_type_exists('product')) {
        return;
    }
    // Same single-runner lock as boblift_retire_old_marine_crane_models(): add_option() lets one request in, and a lock
    // left by a request that died is taken over after ten minutes with a compare-and-swap on its old value.
    global $wpdb;
    $lock = 'boblift_legacy_product_intros_v1_lock';
    $token = time() . '|' . wp_generate_password(12, false);
    if (!add_option($lock, $token, '', false)) {
        $held = (string) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $lock));
        if (time() - (int) $held < 600) {
            return;
        }
        $taken = $wpdb->update($wpdb->options, ['option_value' => $token], ['option_name' => $lock, 'option_value' => $held]);
        wp_cache_delete($lock, 'options');
        if ($taken !== 1) {
            return;
        }
    }

    $intros = boblift_legacy_product_intros();
    $posts = $intros ? get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'post_name__in' => array_keys($intros),
        'numberposts' => -1,
        'suppress_filters' => true,
        'lang' => '',
    ]) : [];

    $failed = 0;
    $updated = 0;
    foreach ($posts as $post) {
        // Translations share the English slug, so the language decides which post gets the copy.
        if (function_exists('pll_get_post_language') && (string) pll_get_post_language($post->ID) !== 'en') {
            continue;
        }
        $content = boblift_insert_legacy_product_intro((string) $post->post_content, $intros[$post->post_name]);
        if ($content === null) {
            continue;
        }
        // A revision is kept, so a product can be rolled back from the editor.
        $result = wp_update_post(wp_slash(['ID' => $post->ID, 'post_content' => $content]), true);
        if (is_wp_error($result) || !$result) {
            $failed++;
            continue;
        }
        $updated++;
    }
    // Only a clean run is recorded; after a failure the next request tries again (finished products are skipped).
    if ($failed === 0) {
        update_option('boblift_legacy_product_intros_v1', $updated, false);
    }
    $wpdb->delete($wpdb->options, ['option_name' => $lock, 'option_value' => $token]);
    wp_cache_delete($lock, 'options');
}
add_action('init', 'boblift_restore_legacy_product_intros', 40);
