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

/**
 * Block markup only for a block body: block comments in a classic body would make has_blocks() true and switch off
 * wpautop(), so its blank-line paragraphs would run together.
 */
function boblift_legacy_product_intro_html(array $intro, bool $as_blocks): string
{
    $heading = '<h3 class="wp-block-heading">' . esc_html((string) $intro['heading']) . '</h3>';
    $html = ($as_blocks ? '<!-- wp:heading {"level":3} -->' . "\n" . $heading . "\n" . '<!-- /wp:heading -->' : $heading) . "\n\n";
    foreach ((array) $intro['paragraphs'] as $paragraph) {
        $text = '<p>' . esc_html((string) $paragraph) . '</p>';
        $html .= ($as_blocks ? '<!-- wp:paragraph -->' . "\n" . $text . "\n" . '<!-- /wp:paragraph -->' : $text) . "\n\n";
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
    if (!has_blocks($content)) {
        $section = boblift_legacy_product_intro_html($intro, false);
        if (preg_match('/<h3\b/i', $content, $match, PREG_OFFSET_CAPTURE)) {
            return substr($content, 0, $match[0][1]) . $section . substr($content, $match[0][1]);
        }
        return rtrim($content) . "\n\n" . $section;
    }
    // A block body is split at top-level blocks only, so an h3 inside a Custom HTML or Group block never cuts that block in
    // two. Each block is written back with serialize_block(), which keeps its saved markup.
    $section = boblift_legacy_product_intro_html($intro, true);
    $before = '';
    $after = '';
    $placed = false;
    foreach (parse_blocks($content) as $block) {
        $is_h3 = ($block['blockName'] ?? '') === 'core/heading' && (int) ($block['attrs']['level'] ?? 2) === 3;
        if (!$placed && $is_h3) {
            $placed = true;
        }
        if ($placed) {
            $after .= serialize_block($block);
        } else {
            $before .= serialize_block($block);
        }
    }
    return rtrim($before) . "\n\n" . $section . ltrim($after);
}

function boblift_restore_legacy_product_intros(): void
{
    if (get_option('boblift_legacy_product_intros_v1') !== false || !post_type_exists('product') || get_transient('boblift_legacy_product_intros_wait')) {
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
    // An unreadable or broken data file counts as a failed run, so the option stays unset and a later request retries.
    $failed = $intros ? 0 : 1;
    $posts = $intros ? get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'post_name__in' => array_keys($intros),
        'numberposts' => -1,
        'suppress_filters' => true,
        'lang' => '',
    ]) : [];

    $updated = 0;
    $found = [];
    // The body is written straight to the posts table with a compare-and-swap, not through wp_update_post(): an anonymous
    // request would run the save-time KSES filters over the whole body (stripping editor markup such as iframes) and clear
    // the slug of a pending product. The swap checks the body and its modified time, so an editor save in the meantime
    // (even a case-only one, which a case-insensitive collation would let through on the body alone) is left alone and
    // retried on a later request. Each product is marked once handled, so a retry never re-adds a section an editor removed.
    foreach ($posts as $post) {
        // Translations share the English slug, so the language decides which post gets the copy.
        if (function_exists('pll_get_post_language') && (string) pll_get_post_language($post->ID) !== 'en') {
            continue;
        }
        $found[$post->post_name] = true;
        if (get_post_meta($post->ID, '_boblift_legacy_intro_v1', true) === '1') {
            continue;
        }
        $row = $wpdb->get_row($wpdb->prepare("SELECT post_content, post_modified_gmt FROM {$wpdb->posts} WHERE ID = %d", $post->ID));
        if (!$row) {
            $failed++;
            continue;
        }
        $current = (string) $row->post_content;
        $content = boblift_insert_legacy_product_intro($current, $intros[$post->post_name]);
        if ($content === null) {
            update_post_meta($post->ID, '_boblift_legacy_intro_v1', '1');
            continue;
        }
        // Whether the body shows depends on it differing from the _legacy_content backup, so a changed body would switch on
        // an unreviewed one (and its translations, which follow it). The current answer is pinned first.
        if (!metadata_exists('post', $post->ID, '_detail_body_ready')) {
            update_post_meta($post->ID, '_detail_body_ready', boblift_product_content_ready($post->ID, '_detail_body_ready') ? '1' : '');
        }
        // The earlier body is kept as a revision, so a product can be rolled back from the editor. Revisions are saved
        // through wp_insert_post(), so KSES is off around them too.
        $kses_was_on = has_filter('content_save_pre', 'wp_filter_post_kses') !== false;
        kses_remove_filters();
        wp_save_post_revision($post->ID);
        $rows = $wpdb->update(
            $wpdb->posts,
            ['post_content' => $content, 'post_modified' => current_time('mysql'), 'post_modified_gmt' => current_time('mysql', true)],
            ['ID' => $post->ID, 'post_content' => $current, 'post_modified_gmt' => $row->post_modified_gmt]
        );
        clean_post_cache($post->ID);
        if ($rows === 1) {
            wp_save_post_revision($post->ID);
        }
        if ($kses_was_on) {
            kses_init_filters();
        }
        if ($rows !== 1) {
            $failed++;
            continue;
        }
        update_post_meta($post->ID, '_boblift_legacy_intro_v1', '1');
        $updated++;
    }
    // Only a clean run that reached every listed product is recorded. After a failure the next request tries again; while
    // some products are missing (not imported yet, or in the trash) it checks again hourly. Finished products are skipped.
    $missing = $intros ? count(array_diff_key($intros, $found)) : 0;
    if ($failed === 0 && $missing === 0) {
        update_option('boblift_legacy_product_intros_v1', $updated, false);
    } elseif ($failed === 0) {
        set_transient('boblift_legacy_product_intros_wait', $missing, HOUR_IN_SECONDS);
    }
    $wpdb->delete($wpdb->options, ['option_name' => $lock, 'option_value' => $token]);
    wp_cache_delete($lock, 'options');
}
add_action('init', 'boblift_restore_legacy_product_intros', 40);
