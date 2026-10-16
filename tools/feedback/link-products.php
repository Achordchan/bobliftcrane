<?php
/**
 * Link each customer feedback entry to the current English product it shows, so the product page can list it.
 *
 * Usage (from the WordPress root): wp eval-file <path>/link-products.php [dry-run] [force]
 * Feedback folders use the model name at shipping time (SQ6.3ZA4, SQ10ZA4...). Rule agreed with the user:
 * an exact model match wins; otherwise same capacity, same boom (Z = knuckle, S = telescopic) and same use
 * (a trailing T = marine, else truck mounted). Among several candidates the SQ-series model with the closest
 * section count wins. No candidate means no link. Entries already linked (by hand in the admin) are kept
 * unless "force" is passed.
 */

$dry = in_array('dry-run', $args ?? [], true);
$force = in_array('force', $args ?? [], true);

$parse = static function (string $model): ?array {
    if (!preg_match('/^SQ(\d+(?:\.\d+)?)([ZS])[A-Z]*(\d+)/i', $model, $m)) {
        return null;
    }
    $cap = str_starts_with($m[1], '0') && !str_contains($m[1], '.') ? (float) ('0.' . substr($m[1], 1)) : (float) $m[1];
    return ['cap' => $cap, 'boom' => strtoupper($m[2]) === 'Z' ? 'knuckle' : 'telescopic', 'sections' => (int) $m[3]];
};

$products = [];
foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => 'en']) as $post) {
    $model = trim((string) get_post_meta($post->ID, '_model', true));
    $cats = wp_get_post_terms($post->ID, 'product_category', ['fields' => 'slugs']);
    $cats = is_array($cats) ? $cats : [];
    $boom = in_array('knuckle-boom-truck-mounted-cranes', $cats, true) || in_array('knuckle-boom-marine-cranes', $cats, true) ? 'knuckle'
        : (in_array('telescopic-boom-truck-mounted-cranes', $cats, true) || in_array('telescopic-boom-marine-cranes', $cats, true) ? 'telescopic' : '');
    $parsed = $parse($model);
    $products[] = [
        'id' => $post->ID,
        'model' => $model,
        'boom' => $boom,
        'marine' => (bool) array_filter($cats, static fn(string $c): bool => str_contains($c, 'marine')),
        'cap' => $parsed['cap'] ?? (float) get_post_meta($post->ID, '_capacity', true),
        'sections' => $parsed['sections'] ?? 0,
        'sq' => $parsed !== null,
    ];
}

$match = static function (string $model) use ($products, $parse): ?array {
    foreach ($products as $product) {
        if (strcasecmp($product['model'], $model) === 0) {
            return $product;
        }
    }
    $want = $parse($model);
    if (!$want) {
        return null;
    }
    // Marine models end in T, with an optional winch (J) and flip-over outriggers (F) and an optional +1: SQ8ZA5T, SQ8ZA5TJ, SQ08ZA3T+1.
    $marine = (bool) preg_match('/\dT(?:J)?(?:F)?(?:\+\d+)?$/i', $model);
    $candidates = array_values(array_filter($products, static fn(array $p): bool => $p['boom'] === $want['boom'] && $p['marine'] === $marine && abs($p['cap'] - $want['cap']) < 0.01));
    usort($candidates, static fn(array $a, array $b): int => [$b['sq'], abs($a['sections'] - $want['sections']), strlen($a['model'])] <=> [$a['sq'], abs($b['sections'] - $want['sections']), strlen($b['model'])]);
    return $candidates[0] ?? null;
};

$rows = [];
$linked = 0;
foreach (get_posts(['post_type' => 'feedback', 'post_status' => 'any', 'posts_per_page' => -1, 'lang' => '', 'orderby' => 'title', 'order' => 'ASC']) as $feedback) {
    $model = trim((string) get_post_meta($feedback->ID, '_feedback_model', true));
    $existing = get_post_meta($feedback->ID, '_related_product');
    $product = $match($model);
    $rows[$model] = $product ? $product['model'] : '-';
    if (!$product || ($existing && !$force)) {
        continue;
    }
    if (!$dry) {
        delete_post_meta($feedback->ID, '_related_product');
        add_post_meta($feedback->ID, '_related_product', (string) $product['id']);
    }
    $linked++;
}

ksort($rows, SORT_NATURAL);
foreach ($rows as $from => $to) {
    printf("%-12s -> %s\n", $from, $to);
}
printf("%s: %d feedback entries linked\n", $dry ? 'DRY RUN' : 'DONE', $linked);
