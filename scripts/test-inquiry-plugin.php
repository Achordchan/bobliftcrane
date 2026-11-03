<?php
/**
 * What the theme hands the inquiry plugin (inc/inquiry-plugin.php): its configuration, translated messages, extra fields,
 * discontinued models, product cards, the catalog request's words, the old settings, and the mail and DingTalk built from
 * them. Needs a checkout of achord-wp-inquiry: php scripts/test-inquiry-plugin.php [path/to/achord-wp-inquiry]
 */
define('ABSPATH', __DIR__ . '/');
$plugin = rtrim($argv[1] ?? dirname(__DIR__, 2) . '/achord-wp-inquiry', '/');
if (!is_file($plugin . '/includes/config.php')) {
    fwrite(STDERR, "achord-wp-inquiry not found at {$plugin}\n");
    exit(2);
}

$GLOBALS['filters'] = $GLOBALS['actions'] = $GLOBALS['options'] = $GLOBALS['meta'] = $GLOBALS['mails'] = $GLOBALS['dingtalk'] = [];
$GLOBALS['theme_support'] = [];
class WP_Error { public function __construct(public string $code = '', public string $message = '', public $data = null) {} }
class WP_Post { public int $ID = 0; }
function add_filter($h, $cb, $p = 10, $n = 1) { $GLOBALS['filters'][$h][$p][] = $cb; return true; }
function add_action($h, $cb, $p = 10, $n = 1) { return add_filter($h, $cb, $p, $n); }
function remove_filter($h, $cb, $p = 10) { foreach ($GLOBALS['filters'][$h][$p] ?? [] as $i => $c) if ($c === $cb) unset($GLOBALS['filters'][$h][$p][$i]); return true; }
function remove_action($h, $cb, $p = 10) { return remove_filter($h, $cb, $p); }
function apply_filters($h, $v, ...$a) { $list = $GLOBALS['filters'][$h] ?? []; ksort($list); foreach ($list as $cbs) foreach ($cbs as $cb) $v = $cb($v, ...$a); return $v; }
function do_action($h, ...$a) { $list = $GLOBALS['filters'][$h] ?? []; ksort($list); foreach ($list as $cbs) foreach ($cbs as $cb) $cb(...$a); }
function add_theme_support($f, ...$args) { $GLOBALS['theme_support'][$f] = $args ?: true; }
function get_theme_support($f) { return $GLOBALS['theme_support'][$f] ?? false; }
function current_theme_supports($f) { return isset($GLOBALS['theme_support'][$f]); }
function get_option($k, $d = false) { return $GLOBALS['options'][$k] ?? $d; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function home_url($p = '') { return 'https://bobliftcrane.com' . $p; }
function admin_url($p = '') { return 'https://bobliftcrane.com/wp-admin/' . $p; }
function untrailingslashit($s) { return rtrim($s, '/'); }
function sanitize_text_field($s) { return trim(preg_replace('/\s+/', ' ', strip_tags((string) $s))); }
function sanitize_email($s) { return filter_var(trim((string) $s), FILTER_VALIDATE_EMAIL) ?: ''; }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $s)); }
function is_email($s) { return (bool) filter_var((string) $s, FILTER_VALIDATE_EMAIL); }
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_url($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function wp_unslash($v) { return $v; }
function wp_specialchars_decode($s, $q = 0) { return htmlspecialchars_decode((string) $s, ENT_QUOTES); }
function get_bloginfo($k) { return 'BOB-LIFT'; }
function wp_date($f, $t = null) { return gmdate($f, $t ?? 1767225600); }
function get_transient($k) { return false; }
function set_transient($k, $v, $t = 0) { return true; }
function wp_remote_get($u, $a = []) { return ['code' => 500, 'body' => '']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_remote_retrieve_response_code($r) { return $r['code']; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function update_post_meta($id, $k, $v) { $GLOBALS['meta'][$id][$k] = (string) $v; return true; }
function get_post_meta($id, $k = '', $s = false) { return $GLOBALS['meta'][$id][$k] ?? ''; }
function wp_mail($to, $subject, $body, $headers = []) { $GLOBALS['mails'][] = compact('to', 'subject', 'body'); return true; }
function achord_dingtalk_dispatch($message, $source) { $GLOBALS['dingtalk'][] = [$message, $source]; }
function taxonomy_exists($t) { return true; }
// The theme's side, as far as inc/inquiry-plugin.php calls it.
function boblift_inquiry_success_url() { return 'https://bobliftcrane.com/es/success.html'; }
function boblift_url($p) { return 'https://bobliftcrane.com/es' . $p; }
function boblift_asset($p) { return '/wp-content/themes/bobliftcrane/assets/' . $p; }
function boblift_switch_to_posted_language() { $GLOBALS['switched'] = true; }
function boblift_site_ui($k) { return $k === 'quoteForm' ? ['successInlineText' => 'Consulta enviada.', 'messages' => ['tooMany' => 'Demasiadas consultas.', 'saveFailed' => '']] : []; }
function boblift_inquiry_legacy_models($slugs) { return str_contains($slugs, 'sq5sa3') ? 'SQ5SA3' : ''; }
function boblift_inquiry_mail_products($fields) { return ($fields['product'] ?? '') !== '' ? [['title' => $fields['product']]] : []; }
function boblift_inquiry_mail_product_card($p) { return '<div class="card">' . esc_html($p['title']) . '</div>'; }
function boblift_inquiry_mail_catalog_card($fields) { return '<div class="catalog">' . esc_html($fields['catalog_url'] ?? '') . '</div>'; }
function boblift_inquiry_recipient() { return 'sales@boblift.com'; }
function boblift_form_security_options() { return ['turnstile_enabled' => '1', 'turnstile_site_key' => 'site-k', 'turnstile_secret_key' => 'secret-k', 'inquiry_blocklist' => '@spam.test', 'inquiry_store_ip' => '1']; }

require $plugin . '/includes/config.php';
require $plugin . '/includes/context.php';
require $plugin . '/includes/network.php';
require $plugin . '/includes/guard.php';
require $plugin . '/includes/store.php';
require $plugin . '/includes/notify.php';
require $plugin . '/includes/submit.php';
require dirname(__DIR__) . '/inc/inquiry-plugin.php';

$failures = 0;
$check = static function (bool $ok, string $what) use (&$failures): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $what . "\n";
    $failures += $ok ? 0 : 1;
};

$check(!boblift_inquiry_plugin(), 'nothing handed over before the theme declares support');
do_action('after_setup_theme');
$check(boblift_inquiry_plugin(), 'the theme hands inquiries to the plugin once it is loaded');
$config = achord_inquiry_config();
$check($config['prefix'] === 'boblift_' && $config['post_type'] === 'inquiry' && $config['rest_base'] === 'inquiries' && $config['rest_record'] === 'boblift_inquiry', 'old posted names, post type, route and record kept');
$check($config['dingtalk_source'] === 'boblift:inquiry' && $config['site_label'] === '起重机站' && $config['attribution_key'] === 'boblift_attribution', 'DingTalk source, site label and stored ad record kept');
$check($config['success_url'] === 'https://bobliftcrane.com/es/success.html' && $config['fallback_url'] === 'https://bobliftcrane.com/es/contact.html', 'thank-you and fallback pages in the page language');
$check($config['logo_url'] === 'https://bobliftcrane.com/wp-content/themes/bobliftcrane/assets/images/bob-lift-logo.png', 'mail logo as an absolute address');
$check(achord_inquiry_message('success') === 'Consulta enviada.' && achord_inquiry_message('tooMany') === 'Demasiadas consultas.', 'messages translated by the theme');
$check(achord_inquiry_message('saveFailed') === 'The inquiry could not be sent. Please try again.', 'an empty translation falls back to English');
do_action('achord_inquiry_before_handle');
$check(!empty($GLOBALS['switched']), 'the posted language is switched to before handling');

$fields = achord_inquiry_fields();
$names = array_keys($fields);
$check(array_search('quantity', $names, true) < array_search('truck_available', $names, true) && end($names) === 'other_requirements', 'extra questions after the standard fields, in the form order');
$check($fields['truck_model']['label'] === '卡车型号 / 底盘品牌' && $fields['truck_model']['short'] === '卡车型号' && $fields['truck_specs']['long'], 'extra questions keep the mail and DingTalk labels');

$sent = apply_filters('achord_inquiry_submission_fields', ['product_slug' => 'sq5sa3,sq8sa3']);
$check($sent['legacy_models'] === 'SQ5SA3', 'discontinued models worked out from the chosen products');

$options = achord_inquiry_options();
$check($options['turnstile_site_key'] === 'site-k' && $options['turnstile_enabled'] === '1' && $options['blocklist'] === '@spam.test' && $options['store_ip'] === '1', '表单安全 settings stand in until 询盘设置 is saved');
$check(achord_inquiry_recipient() === 'sales@boblift.com', 'the 联系方式 email stays the recipient');

$inquiry = ['name' => 'Ana', 'email' => 'ana@example.com', 'product' => 'SQ5SA3 Boom Crane', 'product_slug' => 'sq5sa3', 'legacy_models' => 'SQ5SA3', 'quantity' => '2', 'truck_model' => 'Isuzu', 'message' => 'Price please', 'source_page' => 'https://bobliftcrane.com/es/product/sq5sa3/'];
[$subject, $body] = achord_inquiry_admin_mail($inquiry, 12);
$check(str_contains($body, '<div class="card">SQ5SA3 Boom Crane</div>') && str_contains($body, '老型号') && str_contains($body, 'SQ5SA3（需定制生产）') && str_contains($body, '卡车型号 / 底盘品牌'), 'mail has the product card, the discontinued model and the extra answers');
$check($subject === '【起重机站】询盘飞奔而来，请及时跟进：ana@example.com', 'mail subject as before');
$GLOBALS['options']['boblift_theme_options'] = ['site_ui' => ['quoteForm' => ['messages' => ['mailSubject' => 'Nueva consulta web']]]];
$check(achord_inquiry_admin_mail($inquiry, 12)[0] === 'Nueva consulta web', 'a subject saved in 站点文案 still wins');
$GLOBALS['options']['boblift_theme_options'] = ['site_ui' => ['quoteForm' => ['messages' => ['mailSubject' => 'New BOB-LIFT inquiry']]]];
$check(str_starts_with(achord_inquiry_admin_mail($inquiry, 12)[0], '【起重机站】'), 'the old built-in subject does not override');

$ding = achord_inquiry_dingtalk_message(12, $inquiry);
$labels = array_column($ding['fields'], 'label');
$check($ding['title'] === '🏗️ 起重机站新询盘' && array_search('老型号', $labels, true) === array_search('产品', $labels, true) + 1, 'DingTalk title, and the discontinued model right after the product');

$catalog = ['name' => '', 'email' => 'buyer@example.com', 'product' => 'Marine cranes', 'message' => 'PDF catalog request', 'lead_type' => 'catalog_request', 'catalog_url' => 'https://bobliftcrane.com/catalog.pdf'];
[$subject, $body] = achord_inquiry_admin_mail($catalog, 13);
$check($subject === '【起重机站】客户索取目录，请及时跟进：buyer@example.com' && str_contains($body, '新目录下载请求') && str_contains($body, '<div class="catalog">https://bobliftcrane.com/catalog.pdf</div>') && !str_contains($body, 'PDF catalog request'), 'catalog request mail: its own words, the catalog card, no message');
$ding = achord_inquiry_dingtalk_message(13, $catalog);
$labels = array_column($ding['fields'], 'label');
$check($ding['title'] === '📘 起重机站目录下载' && in_array('目录文件', $labels, true) && !in_array('留言', $labels, true), 'catalog request DingTalk: its title, the file, no message');
$check(achord_inquiry_lead_type($catalog)['title'] === 'Catalog request from %s - %s', 'catalog request title as before');

$GLOBALS['meta'][12]['_legacy_models'] = 'SQ5SA3';
$check(str_starts_with(apply_filters('achord_inquiry_product_column', 'SQ5SA3 Boom Crane', 12), '<span') && apply_filters('achord_inquiry_product_column', 'X', 99) === 'X', 'the 老型号 badge in the inquiry list');

// The theme's own inquiry admin steps aside for the plugin's.
add_filter('manage_inquiry_posts_columns', 'boblift_inquiry_columns');
add_action('boblift_inquiry_submitted', 'boblift_dingtalk_inquiry', 10, 2);
do_action('achord_inquiry_booted');
$check(empty(array_filter($GLOBALS['filters']['manage_inquiry_posts_columns'] ?? [])) && empty(array_filter($GLOBALS['filters']['boblift_inquiry_submitted'] ?? [])), 'theme list columns and DingTalk bridge removed');

echo $failures === 0 ? "all inquiry plugin checks passed\n" : "{$failures} failed\n";
exit($failures === 0 ? 0 : 1);
