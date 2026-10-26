<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inquiries from known spammers, and the read-only inquiry API.
 *
 * Blocklist: 表单安全 → 询盘黑名单 holds email addresses, whole email domains (@example.com) and IP addresses or ranges
 * (203.0.113.7, 203.0.113.0/24), one per line or comma separated. A matching inquiry is still saved, as a draft marked
 * 已拦截 with the reason, so nothing is lost; it sends no email, no DingTalk message, and gets no ?sent= thank-you page,
 * so it is never reported as an ad conversion. The sender sees the usual success message and learns nothing.
 *
 * API: /wp/v2/inquiries (newest first, blocked drafts included), administrators only, read-only. Each inquiry with
 * context=edit carries boblift_inquiry: every saved field (sender, product, message, page, channel, device, ad click IDs,
 * IP) and whether and why it was blocked.
 */

/** The blocklist entries as typed, lower-cased, without blanks or duplicates. */
function boblift_inquiry_blocklist(): array
{
    $options = function_exists('boblift_form_security_options') ? boblift_form_security_options() : [];
    $raw = strtolower((string) ($options['inquiry_blocklist'] ?? ''));
    $entries = preg_split('/[\s,;]+/', $raw) ?: [];
    return array_values(array_unique(array_filter(array_map('trim', $entries), 'strlen')));
}

/** One mailbox as Gmail delivers it: Gmail ignores dots and a +tag, so k.habib+x@gmail.com is khabib@gmail.com. */
function boblift_inquiry_mailbox(string $email): string
{
    $email = strtolower(trim($email));
    $at = strrpos($email, '@');
    if ($at === false || $at === 0) {
        return $email;
    }
    $local = substr($email, 0, $at);
    $domain = substr($email, $at + 1);
    if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
        $local = str_replace('.', '', explode('+', $local, 2)[0]);
        $domain = 'gmail.com';
    }
    return $local . '@' . $domain;
}

/** An address range as typed (203.0.113.0/24, 2001:db8::/32); a mistyped one is ignored rather than read as /0, which matches everyone. */
function boblift_inquiry_valid_range(string $range): bool
{
    $parts = explode('/', $range);
    if (count($parts) !== 2 || !filter_var($parts[0], FILTER_VALIDATE_IP) || !ctype_digit($parts[1])) {
        return false;
    }
    $bits = (int) $parts[1];
    return $bits >= 1 && $bits <= (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32 : 128);
}

/** Why this sender is blocked (e.g. 邮箱 spam@example.com), or '' when nothing on the blocklist matches. */
function boblift_inquiry_block_reason(string $email, string $ip): string
{
    $email = boblift_inquiry_mailbox($email);
    $domain = str_contains($email, '@') ? substr($email, (int) strrpos($email, '@')) : '';
    foreach (boblift_inquiry_blocklist() as $entry) {
        if (str_contains($entry, '@')) {
            // A whole domain is compared the way mailboxes are: @googlemail.com is the same as @gmail.com.
            $entry_domain = str_starts_with($entry, '@') ? substr(boblift_inquiry_mailbox('x' . $entry), 1) : '';
            if ($email !== '' && ($entry_domain !== '' ? $entry_domain === $domain : boblift_inquiry_mailbox($entry) === $email)) {
                return '邮箱 ' . $entry;
            }
            continue;
        }
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            continue;
        }
        if (str_contains($entry, '/') ? boblift_inquiry_valid_range($entry) && boblift_ip_in_range($ip, $entry) : (filter_var($entry, FILTER_VALIDATE_IP) && inet_pton($entry) === inet_pton($ip))) {
            return 'IP ' . $entry;
        }
    }
    return '';
}

/** A blocked inquiry, kept as a draft so the sales team can still look at it; the sender is told it went through. */
function boblift_inquiry_save_blocked(array $fields, string $reason): void
{
    $customer = $fields['name'] !== '' ? $fields['name'] : $fields['email'];
    $post_id = wp_insert_post([
        'post_type' => 'inquiry',
        'post_status' => 'draft',
        'post_title' => sprintf('[已拦截] Inquiry from %s - %s', $customer, current_time('Y-m-d H:i')),
    ], true);
    if (!is_wp_error($post_id)) {
        foreach ($fields as $key => $value) {
            update_post_meta((int) $post_id, '_' . $key, $value);
        }
        update_post_meta((int) $post_id, '_blocked', $reason);
    }
    boblift_inquiry_succeed(0);
}

if (class_exists('WP_REST_Posts_Controller')) {
    /** The posts controller without its writes. Reading needs manage_options: core lets anyone read a published post. */
    final class Boblift_Inquiries_REST_Controller extends WP_REST_Posts_Controller
    {
        public function get_items_permissions_check($request)
        {
            return boblift_inquiries_can_read() ? parent::get_items_permissions_check($request) : boblift_inquiries_forbidden();
        }

        public function get_item_permissions_check($request)
        {
            return boblift_inquiries_can_read() ? parent::get_item_permissions_check($request) : boblift_inquiries_forbidden();
        }

        public function check_read_permission($post)
        {
            return boblift_inquiries_can_read() && parent::check_read_permission($post);
        }

        public function create_item_permissions_check($request)
        {
            return boblift_inquiries_read_only();
        }

        public function update_item_permissions_check($request)
        {
            return boblift_inquiries_read_only();
        }

        public function delete_item_permissions_check($request)
        {
            return boblift_inquiries_read_only();
        }
    }
}

function boblift_inquiries_can_read(): bool
{
    return current_user_can('manage_options');
}

function boblift_inquiries_forbidden(): WP_Error
{
    return new WP_Error('rest_forbidden', 'Only administrators can read inquiries.', ['status' => rest_authorization_required_code()]);
}

function boblift_inquiries_read_only(): WP_Error
{
    return new WP_Error('boblift_inquiries_read_only', 'Inquiries are read-only here; manage them in wp-admin.', ['status' => 403]);
}

/** Every saved field of one inquiry, by name without the leading underscore. */
function boblift_inquiry_record(int $post_id): array
{
    $record = [];
    foreach (get_post_meta($post_id) as $key => $values) {
        if (!str_starts_with((string) $key, '_') || str_starts_with((string) $key, '_edit_') || str_starts_with((string) $key, '_wp_')) {
            continue;
        }
        $record[substr((string) $key, 1)] = (string) ($values[0] ?? '');
    }
    return $record;
}

add_action('rest_api_init', static function (): void {
    register_rest_field('inquiry', 'boblift_inquiry', [
        'get_callback' => static fn(array $post): array => boblift_inquiries_can_read() ? boblift_inquiry_record((int) $post['id']) : [],
        'schema' => ['type' => 'object', 'context' => ['edit'], 'readonly' => true, 'additionalProperties' => true],
    ]);
});

// The default list shows blocked drafts too, so a blocked inquiry can be checked; an explicit status still narrows it.
add_filter('rest_inquiry_query', static function (array $args, $request): array {
    if (!array_key_exists('status', $request->get_query_params())) {
        $args['post_status'] = ['publish', 'draft'];
    }
    return $args;
}, 10, 2);
