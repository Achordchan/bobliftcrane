<?php
// Checks the boblift_site_text field's planning and permissions without WordPress: php scripts/test-site-text.php
define('ABSPATH', __DIR__);
class WP_Error
{
    public function __construct(public string $code = '', public string $message = '', public array $data = [])
    {
    }
}
function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}
function add_action(...$args): void
{
}
function sanitize_text_field(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', strip_tags($value)));
}
// WordPress's esc_url_raw keeps percent-encoding and drops a protocol outside the list.
function esc_url_raw(string $url, ?array $protocols = null): string
{
    $scheme = (string) parse_url($url, PHP_URL_SCHEME);
    return $scheme !== '' && $protocols !== null && !in_array(strtolower($scheme), $protocols, true) ? '' : str_replace(' ', '%20', trim($url));
}
function wp_kses_post(string $value): string
{
    return preg_replace('#<script\b[^>]*>.*?</script>#is', '', $value);
}
$GLOBALS['logged_in'] = true;
$GLOBALS['can'] = true;
function is_user_logged_in(): bool
{
    return $GLOBALS['logged_in'];
}
function current_user_can(string $cap): bool
{
    return $GLOBALS['can'] && $cap === 'manage_options';
}
// The saved options of one language and the write the field would make (instead of the database).
$GLOBALS['stored'] = [];
function boblift_filter_bins_stored(string $language): array
{
    return ['name' => 'boblift_theme_options_' . $language, 'raw' => serialize($GLOBALS['stored']), 'options' => $GLOBALS['stored']];
}
function maybe_serialize($value)
{
    return serialize($value);
}
function wp_cache_delete(...$args): void
{
}
class FakeDb
{
    public string $options = 'wp_options';
    public int $writes = 0;
    public function prepare(string $sql, ...$args): array
    {
        return $args;
    }
    public function query(array $args): bool
    {
        $this->writes++;
        $GLOBALS['stored'] = unserialize($args[0]);
        return true;
    }
}
$GLOBALS['wpdb'] = new FakeDb();
require dirname(__DIR__) . '/inc/rest-site-text.php';

$fail = 0;
$check = static function (string $name, bool $ok) use (&$fail): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $fail += $ok ? 0 : 1;
};
$fresh = [
    'site_ui' => ['header' => ['quoteLabel' => '申请报价', 'quoteShortLabel' => '报价'], 'productDetail' => ['requestQuoteLabel' => '申请报价', 'intro' => '<p>Hi</p>'], 'productArchive' => ['capacityBins' => ['0-5' => '5 吨以内', '25+' => '25 吨以上']]],
    'final_cta' => ['button_label' => '申请报价', 'button_href' => '/quote', 'link' => ''],
    'home' => ['hero' => ['secondary_label' => '申请报价'], 'product_section' => ['max_category_products' => 8]],
    'floating_contact_buttons' => [['type' => 'quote', 'label' => '报价'], ['type' => 'whatsapp', 'label' => 'WhatsApp', 'href' => 'https://wa.me/8615852499415']],
    'static_text' => ['strings' => ['ee9acf6c3103' => ['original' => 'Get a Quote', 'text' => '获取报价']]],
];

// Partial update: only the named text changes, every other value stays.
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价']]);
$check('partial update returns before and after', !is_wp_error($result) && $result['changes'] === [['path' => 'site_ui.header.quoteLabel', 'before' => '申请报价', 'after' => '获取报价']]);
$expected = $fresh;
$expected['site_ui']['header']['quoteLabel'] = '获取报价';
$check('partial update leaves every other key as it was', $GLOBALS['stored'] === $expected);

// Several groups at once, and a path already holding its value is reported as unchanged.
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['final_cta.button_label' => '获取报价', 'home.hero.secondary_label' => '获取报价', 'floating_contact_buttons.0.label' => '获取报价', 'static_text.strings.ee9acf6c3103.text' => '获取报价']]);
$check('four groups change, the one already right is unchanged', !is_wp_error($result) && count($result['changes']) === 3 && $result['unchanged'] === ['static_text.strings.ee9acf6c3103.text']);
$check('non-text neighbours untouched', $GLOBALS['stored']['home']['product_section'] === $fresh['home']['product_section'] && $GLOBALS['stored']['final_cta']['button_href'] === '/quote' && $GLOBALS['stored']['floating_contact_buttons'][0]['type'] === 'quote');

// dry_run reports the changes and writes nothing.
$GLOBALS['stored'] = $fresh;
$writes = $GLOBALS['wpdb']->writes;
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价'], 'dry_run' => true]);
$check('dry_run lists the change', !is_wp_error($result) && $result['dry_run'] === true && count($result['changes']) === 1);
$check('dry_run writes nothing', $GLOBALS['wpdb']->writes === $writes && $GLOBALS['stored'] === $fresh);

// expect that differs: 409, nothing written.
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价', 'final_cta.button_label' => '获取报价'], 'expect' => ['site_ui.header.quoteLabel' => '申请报价', 'final_cta.button_label' => '索取报价']]);
$check('expect mismatch refuses with 409', is_wp_error($result) && $result->data['status'] === 409 && $result->data['differs'][0]['path'] === 'final_cta.button_label');
$check('expect mismatch writes nothing', $GLOBALS['stored'] === $fresh);
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价'], 'expect' => ['site_ui.header.quoteLabel' => '申请报价']]);
$check('matching expect writes', !is_wp_error($result) && $GLOBALS['stored']['site_ui']['header']['quoteLabel'] === '获取报价');

// Paths that do not exist or are outside the allowed groups are refused, and nothing is written.
$GLOBALS['stored'] = $fresh;
foreach (['site_ui.header.quoteLabell' => 'x', 'contact.quote_path' => '/x', 'site_ui.header' => 'x', 'static_text.strings.ee9acf6c3103.original' => 'x', 'home.hero.secondary_label.0' => 'x'] as $path => $value) {
    $result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价', $path => $value]]);
    $check('refuses ' . $path, is_wp_error($result) && $result->data['status'] === 400 && $GLOBALS['stored'] === $fresh);
}
$check('refuses an empty set', is_wp_error(boblift_site_text_apply('zh', ['set' => []])));
$check('refuses unknown request keys', is_wp_error(boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => 'x'], 'force' => true])));
$check('refuses a non-text value', is_wp_error(boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => ['x']]])));

// Keys with - and + under site_ui (the capacity bins) can be changed; other characters stay refused (#252 review).
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.productArchive.capacityBins.0-5' => '0–5 吨', 'site_ui.productArchive.capacityBins.25+' => '25 吨及以上']]);
$check('capacity bin keys 0-5 and 25+ change', !is_wp_error($result) && count($result['changes']) === 2 && $GLOBALS['stored']['site_ui']['productArchive']['capacityBins'] === ['0-5' => '0–5 吨', '25+' => '25 吨及以上']);
$check('other site_ui keys untouched by the bin change', $GLOBALS['stored']['site_ui']['header'] === $fresh['site_ui']['header']);
$GLOBALS['stored'] = $fresh;
foreach (['site_ui.productArchive.capacityBins.0 5', 'site_ui.productArchive.capacityBins.25%', 'site_ui.header.quote/Label', 'final_cta.button-label', 'home.hero.secondary+label'] as $path) {
    $check('still refuses ' . $path, !boblift_site_text_path_allowed($path) && is_wp_error(boblift_site_text_apply('zh', ['set' => [$path => 'x']])) && $GLOBALS['stored'] === $fresh);
}

// Addresses keep their encoding and must use an allowed protocol (#252 review).
$GLOBALS['stored'] = $fresh;
$wa = 'https://wa.me/8615852499415?text=%E4%BD%A0%E5%A5%BD%20BOB-LIFT%20quote';
$result = boblift_site_text_apply('zh', ['set' => ['floating_contact_buttons.1.href' => $wa, 'final_cta.button_href' => '/contact.html?product=SQ12ZA5%20T#inquiry']]);
$check('wa.me link with %20 and encoded Chinese kept as sent', !is_wp_error($result) && $GLOBALS['stored']['floating_contact_buttons'][1]['href'] === $wa);
$check('site path with encoding kept', $GLOBALS['stored']['final_cta']['button_href'] === '/contact.html?product=SQ12ZA5%20T#inquiry');
foreach (['javascript:alert(1)', 'JavaScript:alert(1)', 'data:text/html,x', 'ftp://example.com/x', ''] as $bad) {
    $GLOBALS['stored'] = $fresh;
    $result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价', 'floating_contact_buttons.1.href' => $bad]]);
    $check('refuses address ' . ($bad === '' ? '(empty)' : $bad), is_wp_error($result) && $result->data['status'] === 400 && $GLOBALS['stored'] === $fresh);
}
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['floating_contact_buttons.1.href' => 'mailto:info@boblift.com', 'final_cta.link' => 'tel:+8615852499415']]);
$check('mailto and tel accepted', !is_wp_error($result) && count($result['changes']) === 2);
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '获取报价%20 <b>x</b>']]);
$check('text fields still sanitized as text', !is_wp_error($result) && $GLOBALS['stored']['site_ui']['header']['quoteLabel'] === '获取报价%20 x');
$check('url keys recognised', boblift_site_text_is_url('final_cta.button_href') && boblift_site_text_is_url('floating_contact_buttons.0.href') && boblift_site_text_is_url('site_ui.newsPage.ctaUrl') === false && boblift_site_text_is_url('site_ui.x.image_url') && !boblift_site_text_is_url('final_cta.button_label'));

// Cleaning follows the saved value: plain text is stripped, HTML keeps its safe tags.
$GLOBALS['stored'] = $fresh;
$result = boblift_site_text_apply('zh', ['set' => ['site_ui.header.quoteLabel' => '<b>获取报价</b>  ', 'site_ui.productDetail.intro' => '<p>Hello</p><script>x()</script>']]);
$check('plain text is sanitized', $GLOBALS['stored']['site_ui']['header']['quoteLabel'] === '获取报价');
$check('html text keeps tags, loses script', $GLOBALS['stored']['site_ui']['productDetail']['intro'] === '<p>Hello</p>');

// Permissions: signed out 401, without manage_options 403.
$GLOBALS['logged_in'] = false;
$denied = boblift_site_text_permission();
$check('signed out gets 401', is_wp_error($denied) && $denied->data['status'] === 401);
$GLOBALS['logged_in'] = true;
$GLOBALS['can'] = false;
$denied = boblift_site_text_permission();
$check('without manage_options gets 403', is_wp_error($denied) && $denied->data['status'] === 403);
$GLOBALS['can'] = true;
$check('an administrator passes', boblift_site_text_permission() === true);

// Read returns the groups this field covers.
$values = boblift_site_text_values($fresh);
$check('read covers the five groups', array_keys($values) === ['site_ui', 'static_text', 'final_cta', 'home.hero', 'floating_contact_buttons'] && $values['home.hero'] === $fresh['home']['hero']);

echo $fail ? "$fail failed\n" : "all passed\n";
exit($fail ? 1 : 0);
