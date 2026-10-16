<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_mls_default_sections(): array
{
    return ['branding', 'contact', 'floating_contact_buttons', 'top_bar', 'nav_items', 'product_nav_groups', 'footer', 'final_cta', 'home', 'about_page', 'social_media_page', 'public_welfare_page', 'privacy_policy_page', 'terms_of_use_page', 'sitemap_page', 'product_archive', 'news_categories', 'site_ui', 'seo_meta'];
}

function boblift_mls_settings(): array
{
    $saved = get_option(BOBLIFT_MLS_SETTINGS_OPTION, []);
    $saved = is_array($saved) ? $saved : [];
    $settings = array_merge([
        'source_language' => 'en',
        'sections' => boblift_mls_default_sections(),
    ], $saved);
    $saved_sections = is_array($settings['sections'] ?? null) ? $settings['sections'] : [];
    $settings['sections'] = array_values(array_unique(array_merge($saved_sections, boblift_mls_default_sections())));
    return $settings;
}

function boblift_mls_languages(): array
{
    if (function_exists('pll_languages_list')) {
        $languages = pll_languages_list(['fields' => 'slug']);
        if (is_array($languages) && !empty($languages)) {
            return array_values(array_unique(array_map('strval', $languages)));
        }
    }
    return ['en'];
}

function boblift_mls_source_language(): string
{
    $language = (string) (boblift_mls_settings()['source_language'] ?? 'en');
    return $language !== '' ? $language : 'en';
}

function boblift_mls_current_language(): string
{
    if (is_admin()) {
        foreach (['boblift_admin_language', 'lang', 'new_lang'] as $key) {
            if (!empty($_REQUEST[$key])) {
                $language = sanitize_key((string) wp_unslash($_REQUEST[$key]));
                if ($language !== '' && $language !== 'all' && in_array($language, boblift_mls_languages(), true)) {
                    return $language;
                }
            }
        }
        $filtered_language = (string) get_user_meta(get_current_user_id(), 'pll_filter_content', true);
        if ($filtered_language !== '' && in_array($filtered_language, boblift_mls_languages(), true)) {
            return $filtered_language;
        }
    }
    if (function_exists('pll_current_language')) {
        $language = (string) pll_current_language('slug');
        if ($language !== '') {
            return $language;
        }
    }
    return boblift_mls_source_language();
}

function boblift_mls_post_language(int|WP_Post $post): string
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if ($post instanceof WP_Post && function_exists('pll_get_post_language')) {
        $language = (string) pll_get_post_language($post->ID, 'slug');
        if ($language !== '') {
            return $language;
        }
    }
    return boblift_mls_source_language();
}

function boblift_mls_storage_key_for_language(string $language, string $source_language): string
{
    return $language === $source_language ? 'boblift_theme_options' : 'boblift_theme_options_' . sanitize_key($language);
}

function boblift_mls_migrate_source_language(string $new_source_language): true|WP_Error
{
    $languages = boblift_mls_languages();
    if (!in_array($new_source_language, $languages, true)) {
        return new WP_Error('boblift_mls_bad_language', '主维护语言不存在。');
    }
    $old_source_language = boblift_mls_source_language();
    if ($new_source_language === $old_source_language) {
        return true;
    }

    $old_base = get_option('boblift_theme_options', []);
    $old_base = is_array($old_base) ? $old_base : [];
    $new_source_key = boblift_mls_storage_key_for_language($new_source_language, $old_source_language);
    $new_source_options = get_option($new_source_key, []);
    $new_source_options = is_array($new_source_options) ? $new_source_options : [];

    update_option('boblift_mls_source_language_backup_' . time(), [
        'old_source_language' => $old_source_language,
        'new_source_language' => $new_source_language,
        'base_options' => $old_base,
        'new_source_options' => $new_source_options,
    ], false);

    update_option(boblift_mls_storage_key_for_language($old_source_language, $new_source_language), $old_base, false);
    update_option('boblift_theme_options', $new_source_options, false);
    return true;
}

function boblift_mls_option_key(?string $language = null): string
{
    $language = $language ?: boblift_mls_current_language();
    return boblift_mls_storage_key_for_language($language, boblift_mls_source_language());
}

function boblift_mls_get_raw_options(?string $language = null): array
{
    $saved = get_option(boblift_mls_option_key($language), []);
    return is_array($saved) ? $saved : [];
}

function boblift_mls_update_language_options(array $options, ?string $language = null): bool
{
    return update_option(boblift_mls_option_key($language), $options, false);
}

function boblift_mls_theme_options(?string $language = null): array
{
    if (!function_exists('boblift_default_options') || !function_exists('boblift_array_replace_recursive')) {
        return [];
    }
    $language = $language ?: boblift_mls_current_language();
    $source_language = boblift_mls_source_language();
    $defaults = boblift_default_options();
    $source = boblift_mls_get_raw_options($source_language);

    if ($language === $source_language) {
        $merged = boblift_array_replace_recursive($defaults, $source);
    } else {
        $localized = boblift_mls_language_can_use_localized_options($language) ? boblift_mls_get_raw_options($language) : [];
        unset($localized['form_security']);
        $merged = boblift_array_replace_recursive($defaults, is_array($localized) ? $localized : []);
    }

    $merged['form_security'] = boblift_array_replace_recursive($defaults['form_security'] ?? [], $source['form_security'] ?? []);
    return $merged;
}

function boblift_mls_section_options(string $section, ?string $language = null): array
{
    $options = boblift_mls_theme_options($language);
    return isset($options[$section]) && is_array($options[$section]) ? $options[$section] : [];
}

function boblift_mls_state(): array
{
    $state = get_option(BOBLIFT_MLS_STATE_OPTION, []);
    return is_array($state) ? $state : [];
}

function boblift_mls_update_state(array $state): bool
{
    return update_option(BOBLIFT_MLS_STATE_OPTION, $state, false);
}

function boblift_mls_language_status(string $language): string
{
    if ($language === boblift_mls_source_language()) {
        return 'published';
    }
    $state = boblift_mls_state();
    $status = (string) ($state['languages'][$language]['status'] ?? '');
    return in_array($status, ['staged', 'translating', 'ready', 'published'], true) ? $status : 'new';
}

function boblift_mls_language_status_label(string $status): string
{
    return [
        'new' => '未初始化',
        'staged' => '已初始化，未翻译',
        'translating' => '翻译中',
        'ready' => '待发布',
        'published' => '已发布',
    ][$status] ?? $status;
}

function boblift_mls_set_language_status(string $language, string $status, array $extra = []): void
{
    if (!in_array($status, ['staged', 'translating', 'ready', 'published'], true)) {
        return;
    }
    $state = boblift_mls_state();
    $state['languages'][$language] = array_merge((array) ($state['languages'][$language] ?? []), [
        'status' => $status,
        'updated_at' => time(),
    ], $extra);
    boblift_mls_update_state($state);
}

function boblift_mls_language_can_use_localized_options(string $language): bool
{
    if ($language === boblift_mls_source_language()) {
        return true;
    }
    if ((is_admin() && !wp_doing_ajax()) || current_user_can('manage_options')) {
        return true;
    }
    return boblift_mls_language_status($language) === 'published';
}

function boblift_mls_language_visible_in_switcher(string $language): bool
{
    return $language === boblift_mls_source_language() || boblift_mls_language_status($language) === 'published';
}

function boblift_mls_stable_hash(mixed $value): string
{
    return hash('sha256', wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function boblift_mls_source_section_hash(string $section): string
{
    if ($section === 'seo_meta') {
        return boblift_mls_stable_hash(boblift_mls_collect_source_seo_snapshot());
    }
    return boblift_mls_stable_hash(boblift_mls_section_options($section, boblift_mls_source_language()));
}

function boblift_mls_source_section_snapshot(string $section): array
{
    if ($section === 'seo_meta') {
        return boblift_mls_collect_source_seo_snapshot();
    }
    return boblift_mls_section_options($section, boblift_mls_source_language());
}

function boblift_mls_flatten_snapshot_paths(mixed $value, string $prefix = ''): array
{
    if (!is_array($value)) {
        return [$prefix => $value];
    }
    $paths = [];
    foreach ($value as $key => $item) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($item)) {
            $paths += boblift_mls_flatten_snapshot_paths($item, $path);
            continue;
        }
        $paths[$path] = $item;
    }
    return $paths;
}

function boblift_mls_source_section_change_paths(string $language, string $section, int $limit = 8): array
{
    $state = boblift_mls_state();
    $previous = $state['sync'][$language][$section]['source_snapshot'] ?? null;
    if (!is_array($previous)) {
        return [];
    }
    $old = boblift_mls_flatten_snapshot_paths($previous);
    $current = boblift_mls_flatten_snapshot_paths(boblift_mls_source_section_snapshot($section));
    $changed = [];
    foreach ($current as $path => $value) {
        if (!array_key_exists($path, $old) || boblift_mls_stable_hash($old[$path]) !== boblift_mls_stable_hash($value)) {
            $changed[] = $path;
        }
        if (count($changed) >= $limit) {
            break;
        }
    }
    foreach ($old as $path => $value) {
        if (!array_key_exists($path, $current)) {
            $changed[] = $path;
        }
        if (count($changed) >= $limit) {
            break;
        }
    }
    return $changed;
}

function boblift_mls_last_synced_hash(string $language, string $section): string
{
    $state = boblift_mls_state();
    return (string) ($state['sync'][$language][$section]['source_hash'] ?? '');
}

function boblift_mls_translation_available(): bool
{
    return function_exists('ptai_call_translation_ai');
}

function boblift_mls_is_text_field(string|int $key, array $path, string $value): bool
{
    $key = (string) $key;
    $trimmed = trim($value);
    if ($trimmed === '') {
        return false;
    }
    if (preg_match('#^(https?:)?//#i', $trimmed) || str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || preg_match('#^(mailto|tel|javascript):#i', $trimmed)) {
        return false;
    }
    if (preg_match('#^[\d\s.,+\-/%°×x]+$#u', $trimmed)) {
        return false;
    }
    if (($path[0] ?? '') === 'site_ui') {
        $site_ui_blocked_keys = ['key', 'id', 'type', 'target', 'path', 'href', 'url', 'src', 'slug', 'iconName', 'icon', 'year', 'number', 'video_file', 'youtube_url', 'heroImage', 'productLinkHref', 'relatedArticleCount', 'galleryImageCount', 'maxCategoryProducts', 'image'];
        return !in_array($key, $site_ui_blocked_keys, true);
    }
    if ($key === 'name' && count($path) >= 4 && ($path[0] ?? '') === 'home' && ($path[1] ?? '') === 'trust_bar' && ($path[2] ?? '') === 'marks') {
        return true;
    }
    $blocked_keys = ['id', 'path', 'href', 'url', 'src', 'slug', 'email', 'phone', 'whatsapp', 'iconName', 'icon', 'year', 'value', 'number', 'video_file', 'youtube_url', 'heroImage', 'productLinkHref', 'relatedArticleCount', 'galleryImageCount', 'maxCategoryProducts'];
    if (in_array($key, $blocked_keys, true)) {
        return false;
    }
    if (preg_match('/(_|[a-z])(label|title|text|description|placeholder|alt|note|suffix|prefix|heading|body|value)$/i', $key)) {
        return true;
    }
    $text_keys = ['label', 'title', 'text', 'paragraph', 'headline', 'description', 'image_alt', 'alt', 'intro', 'copyright', 'button_label', 'cta_label', 'highlight', 'background_word', 'role', 'seo_title', 'seo_description', 'contact_title', 'address', 'placeholder', 'messagePlaceholder', 'heroLabel', 'heroTitle', 'heroDescription', 'backLabel', 'relatedLabel', 'button', 'buttonLabel', 'system_value', 'home', 'news', 'cases', 'summary', 'category', 'published', 'source', 'sourceValue', 'defaultTypeLabel'];
    $parent = '';
    for ($i = count($path) - 1; $i >= 0; $i--) {
        if (!is_numeric((string) $path[$i])) {
            $parent = (string) $path[$i];
            break;
        }
    }
    $text_parent_keys = ['timeline', 'fields', 'messages', 'buttons', 'cardLabels', 'inquiryLineLabels', 'selectOptions', 'labels', 'panel', 'items', 'legal_links'];
    return in_array($key, $text_keys, true) || in_array($parent, $text_parent_keys, true);
}

function boblift_mls_collect_texts(mixed $value, array $path, array &$texts, array &$paths): void
{
    if (is_string($value)) {
        $key = end($path);
        if (boblift_mls_is_text_field($key === false ? '' : $key, $path, $value)) {
            $texts[] = $value;
            $paths[] = $path;
        }
        return;
    }
    if (!is_array($value)) {
        return;
    }
    foreach ($value as $key => $item) {
        $next = $path;
        $next[] = $key;
        boblift_mls_collect_texts($item, $next, $texts, $paths);
    }
}

function boblift_mls_set_path(array &$array, array $path, mixed $value): void
{
    $cursor =& $array;
    foreach ($path as $index => $key) {
        if ($index === count($path) - 1) {
            $cursor[$key] = $value;
            return;
        }
        if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
            $cursor[$key] = [];
        }
        $cursor =& $cursor[$key];
    }
}

function boblift_mls_get_path(mixed $array, array $path): mixed
{
    $cursor = $array;
    foreach ($path as $key) {
        if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
            return null;
        }
        $cursor = $cursor[$key];
    }
    return $cursor;
}

function boblift_mls_restore_placeholders(string $source, string $translated): string
{
    if (!preg_match_all('/\{[A-Za-z0-9_]+\}/', $source, $matches)) {
        return $translated;
    }
    $source_placeholders = array_unique($matches[0]);
    foreach ($source_placeholders as $placeholder) {
        $pattern = '/\{\s*' . preg_quote(trim($placeholder, '{}'), '/') . '\s*\}/u';
        if (preg_match($pattern, $translated)) {
            $translated = preg_replace($pattern, $placeholder, $translated) ?? $translated;
        } elseif (!str_contains($translated, $placeholder)) {
            $translated .= ' ' . $placeholder;
        }
    }
    if (preg_match_all('/\{[^}]+\}/u', $translated, $translated_matches)) {
        foreach (array_unique($translated_matches[0]) as $candidate) {
            if (!in_array($candidate, $source_placeholders, true)) {
                $translated = str_replace($candidate, '', $translated);
            }
        }
    }
    return trim(preg_replace('/\s{2,}/u', ' ', $translated) ?? $translated);
}

function boblift_mls_translate_texts(array $texts, string $target_language, string $source_language): array|WP_Error
{
    if (empty($texts)) {
        return [];
    }
    if (!boblift_mls_translation_available()) {
        return new WP_Error('boblift_mls_no_translation_engine', 'Polytranslate AI 翻译函数不可用。');
    }
    $translated = ptai_call_translation_ai(array_values($texts), $target_language, $source_language);
    if (is_array($translated) && isset($translated['success']) && $translated['success'] === false) {
        return new WP_Error('boblift_mls_translation_failed', (string) ($translated['error'] ?? '翻译失败。'));
    }
    if (!is_array($translated)) {
        return new WP_Error('boblift_mls_translation_invalid', '翻译接口返回格式不正确。');
    }
    $translated = array_values($translated);
    foreach ($translated as $index => $text) {
        $translated[$index] = boblift_mls_restore_placeholders((string) ($texts[$index] ?? ''), (string) $text);
    }
    return $translated;
}

function boblift_mls_static_title_glossary(): array
{
    $titles = [
        'Home' => ['zh' => '首页', 'ja' => 'ホーム', 'ko' => '홈', 'es' => 'Inicio', 'fr' => 'Accueil', 'de' => 'Startseite', 'ru' => 'Главная', 'pt' => 'Início', 'ar' => 'الرئيسية', 'vi' => 'Trang chủ', 'th' => 'หน้าแรก', 'id' => 'Beranda'],
        'About BOB-LIFT' => ['zh' => '关于 BOB-LIFT', 'ja' => 'BOB-LIFTについて', 'ko' => 'BOB-LIFT 소개', 'es' => 'Acerca de BOB-LIFT', 'fr' => 'À propos de BOB-LIFT', 'de' => 'Über BOB-LIFT', 'ru' => 'О BOB-LIFT', 'pt' => 'Sobre a BOB-LIFT', 'ar' => 'عن BOB-LIFT', 'vi' => 'Giới thiệu BOB-LIFT', 'th' => 'เกี่ยวกับ BOB-LIFT', 'id' => 'Tentang BOB-LIFT'],
        'About Us' => ['zh' => '关于我们', 'ja' => '会社概要', 'ko' => '회사 소개', 'es' => 'Sobre nosotros', 'fr' => 'À propos de nous', 'de' => 'Über uns', 'ru' => 'О нас', 'pt' => 'Sobre nós', 'ar' => 'من نحن', 'vi' => 'Về chúng tôi', 'th' => 'เกี่ยวกับเรา', 'id' => 'Tentang Kami'],
        'Global Customer Visits' => ['zh' => '全球客户来访', 'ja' => 'グローバル顧客来訪', 'ko' => '글로벌 고객 방문', 'es' => 'Visitas de clientes globales', 'fr' => 'Visites de clients internationaux', 'de' => 'Globale Kundenbesuche', 'ru' => 'Визиты клиентов со всего мира', 'pt' => 'Visitas de clientes globais', 'ar' => 'زيارات العملاء العالمية', 'vi' => 'Khách hàng toàn cầu đến thăm', 'th' => 'การเยี่ยมชมของลูกค้าทั่วโลก', 'id' => 'Kunjungan Pelanggan Global'],
        'Social Media' => ['zh' => '社交媒体', 'ja' => 'ソーシャルメディア', 'ko' => '소셜 미디어', 'es' => 'Redes sociales', 'fr' => 'Réseaux sociaux', 'de' => 'Soziale Medien', 'ru' => 'Социальные сети', 'pt' => 'Redes sociais', 'ar' => 'وسائل التواصل الاجتماعي', 'vi' => 'Mạng xã hội', 'th' => 'โซเชียลมีเดีย', 'id' => 'Media Sosial'],
        'Public Welfare' => ['zh' => '公益', 'ja' => '社会貢献', 'ko' => '공익 활동', 'es' => 'Bienestar público', 'fr' => 'Engagement social', 'de' => 'Gemeinwohl', 'ru' => 'Общественная ответственность', 'pt' => 'Responsabilidade social', 'ar' => 'المسؤولية المجتمعية', 'vi' => 'Phúc lợi công cộng', 'th' => 'สาธารณประโยชน์', 'id' => 'Kepedulian Sosial'],
        'Contact Us' => ['zh' => '联系我们', 'ja' => 'お問い合わせ', 'ko' => '문의하기', 'es' => 'Contáctenos', 'fr' => 'Contactez-nous', 'de' => 'Kontakt', 'ru' => 'Свяжитесь с нами', 'pt' => 'Fale conosco', 'ar' => 'اتصل بنا', 'vi' => 'Liên hệ', 'th' => 'ติดต่อเรา', 'id' => 'Hubungi Kami'],
        'Products' => ['zh' => '产品', 'ja' => '製品', 'ko' => '제품', 'es' => 'Productos', 'fr' => 'Produits', 'de' => 'Produkte', 'ru' => 'Продукция', 'pt' => 'Produtos', 'ar' => 'المنتجات', 'vi' => 'Sản phẩm', 'th' => 'ผลิตภัณฑ์', 'id' => 'Produk'],
        'Cases' => ['zh' => '案例', 'ja' => '導入事例', 'ko' => '사례', 'es' => 'Casos', 'fr' => 'Cas', 'de' => 'Referenzen', 'ru' => 'Кейсы', 'pt' => 'Casos', 'ar' => 'الحالات', 'vi' => 'Dự án', 'th' => 'กรณีศึกษา', 'id' => 'Studi Kasus'],
        'News' => ['zh' => '新闻', 'ja' => 'ニュース', 'ko' => '뉴스', 'es' => 'Noticias', 'fr' => 'Actualités', 'de' => 'Nachrichten', 'ru' => 'Новости', 'pt' => 'Notícias', 'ar' => 'الأخبار', 'vi' => 'Tin tức', 'th' => 'ข่าวสาร', 'id' => 'Berita'],
        'Request a Quote' => ['zh' => '请求报价', 'ja' => '見積依頼', 'ko' => '견적 요청', 'es' => 'Solicitar cotización', 'fr' => 'Demander un devis', 'de' => 'Angebot anfordern', 'ru' => 'Запросить предложение', 'pt' => 'Solicitar cotação', 'ar' => 'طلب عرض سعر', 'vi' => 'Yêu cầu báo giá', 'th' => 'ขอใบเสนอราคา', 'id' => 'Minta Penawaran'],
        'Inquiry Submitted' => ['zh' => '询价已提交', 'ja' => 'お問い合わせ送信完了', 'ko' => '문의가 제출되었습니다', 'es' => 'Consulta enviada', 'fr' => 'Demande envoyée', 'de' => 'Anfrage gesendet', 'ru' => 'Запрос отправлен', 'pt' => 'Consulta enviada', 'ar' => 'تم إرسال الاستفسار', 'vi' => 'Đã gửi yêu cầu', 'th' => 'ส่งคำขอแล้ว', 'id' => 'Permintaan Terkirim'],
        'Privacy Policy' => ['zh' => '隐私政策', 'ja' => 'プライバシーポリシー', 'ko' => '개인정보 처리방침', 'es' => 'Política de privacidad', 'fr' => 'Politique de confidentialité', 'de' => 'Datenschutzerklärung', 'ru' => 'Политика конфиденциальности', 'pt' => 'Política de privacidade', 'ar' => 'سياسة الخصوصية', 'vi' => 'Chính sách bảo mật', 'th' => 'นโยบายความเป็นส่วนตัว', 'id' => 'Kebijakan Privasi'],
        'Terms of Use' => ['zh' => '使用条款', 'ja' => '利用規約', 'ko' => '이용 약관', 'es' => 'Términos de uso', 'fr' => 'Conditions d’utilisation', 'de' => 'Nutzungsbedingungen', 'ru' => 'Условия использования', 'pt' => 'Termos de uso', 'ar' => 'شروط الاستخدام', 'vi' => 'Điều khoản sử dụng', 'th' => 'ข้อกำหนดการใช้งาน', 'id' => 'Ketentuan Penggunaan'],
        'Sitemap' => ['zh' => '网站地图', 'ja' => 'サイトマップ', 'ko' => '사이트맵', 'es' => 'Mapa del sitio', 'fr' => 'Plan du site', 'de' => 'Sitemap', 'ru' => 'Карта сайта', 'pt' => 'Mapa do site', 'ar' => 'خريطة الموقع', 'vi' => 'Sơ đồ trang web', 'th' => 'แผนผังเว็บไซต์', 'id' => 'Peta Situs'],
        'Company News' => ['zh' => '公司新闻', 'ja' => '企業ニュース', 'ko' => '회사 뉴스', 'es' => 'Noticias de la empresa', 'fr' => 'Actualités de l’entreprise', 'de' => 'Unternehmensnachrichten', 'ru' => 'Новости компании', 'pt' => 'Notícias da empresa', 'ar' => 'أخبار الشركة', 'vi' => 'Tin công ty', 'th' => 'ข่าวบริษัท', 'id' => 'Berita Perusahaan'],
        'Product News' => ['zh' => '产品新闻', 'ja' => '製品ニュース', 'ko' => '제품 뉴스', 'es' => 'Noticias de productos', 'fr' => 'Actualités produits', 'de' => 'Produktneuheiten', 'ru' => 'Новости продукции', 'pt' => 'Notícias de produtos', 'ar' => 'أخبار المنتجات', 'vi' => 'Tin sản phẩm', 'th' => 'ข่าวผลิตภัณฑ์', 'id' => 'Berita Produk'],
        'Industry Insight' => ['zh' => '行业洞察', 'ja' => '業界インサイト', 'ko' => '업계 인사이트', 'es' => 'Perspectiva de la industria', 'fr' => 'Analyse du secteur', 'de' => 'Brancheneinblick', 'ru' => 'Отраслевые обзоры', 'pt' => 'Insights do setor', 'ar' => 'رؤى الصناعة', 'vi' => 'Góc nhìn ngành', 'th' => 'ข้อมูลเชิงลึกอุตสาหกรรม', 'id' => 'Wawasan Industri'],
        'Marine Solution' => ['zh' => '船用解决方案', 'ja' => '船舶ソリューション', 'ko' => '해양 솔루션', 'es' => 'Solución marina', 'fr' => 'Solution marine', 'de' => 'Marinelösung', 'ru' => 'Морское решение', 'pt' => 'Solução marítima', 'ar' => 'حلول بحرية', 'vi' => 'Giải pháp hàng hải', 'th' => 'โซลูชันทางทะเล', 'id' => 'Solusi Kelautan'],
        'Customer Case' => ['zh' => '客户案例', 'ja' => '顧客事例', 'ko' => '고객 사례', 'es' => 'Caso de cliente', 'fr' => 'Cas client', 'de' => 'Kundenfall', 'ru' => 'Кейс клиента', 'pt' => 'Caso de cliente', 'ar' => 'حالة عميل', 'vi' => 'Trường hợp khách hàng', 'th' => 'กรณีลูกค้า', 'id' => 'Kasus Pelanggan'],
    ];
    return $titles;
}

function boblift_mls_normalize_language_slug(string $language): string
{
    $language = strtolower(trim($language));
    return preg_replace('/[_-].*$/', '', $language) ?: $language;
}

function boblift_mls_normalize_static_title(string $text): string
{
    return trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)) ?? $text);
}

function boblift_mls_apply_static_title_fallback(string $source_text, string $translated_text, string $target_language, string $source_language = ''): string|WP_Error
{
    $source = boblift_mls_normalize_static_title($source_text);
    $translated = trim($translated_text);
    $target = boblift_mls_normalize_language_slug($target_language);
    $source_lang = boblift_mls_normalize_language_slug($source_language);
    $source = $source === 'BOB-LIFT Crane Solutions' ? 'About BOB-LIFT' : $source;
    if ($source === '' || $target === '' || $target === $source_lang || $target === 'en') {
        return $translated;
    }

    $seo_suffix = '';
    $base_source = $source;
    if (preg_match('/^(.+?)\s*\|\s*(BOB-LIFT)$/i', $source, $matches)) {
        $base_source = boblift_mls_normalize_static_title($matches[1]);
        $seo_suffix = ' | ' . $matches[2];
    }

    $glossary = boblift_mls_static_title_glossary();
    if (!isset($glossary[$base_source])) {
        return $translated;
    }

    $same_as_source = strcasecmp(boblift_mls_normalize_static_title($translated), $source) === 0;
    if ($translated !== '' && !$same_as_source) {
        return $translated;
    }
    if (isset($glossary[$base_source][$target])) {
        return $glossary[$base_source][$target] . $seo_suffix;
    }

    return new WP_Error(
        'boblift_mls_static_title_untranslated',
        sprintf('静态页面标题“%s”没有被翻译为目标语言“%s”，已停止写入英文标题。', $source, $target_language)
    );
}

function boblift_mls_skip_marker(): string
{
    return '__BOBLIFT_MLS_SKIP__';
}

function boblift_mls_is_skip_marker(mixed $value): bool
{
    return is_string($value) && $value === boblift_mls_skip_marker();
}

function boblift_mls_params_only_value(mixed $source, mixed $target, array $path): mixed
{
    if (is_string($source)) {
        $key = end($path);
        if (boblift_mls_is_text_field($key === false ? '' : $key, $path, $source)) {
            return is_string($target) && trim($target) !== '' ? $target : boblift_mls_skip_marker();
        }
        return $source;
    }
    if (!is_array($source)) {
        return $source;
    }
    $result = [];
    foreach ($source as $key => $item) {
        $next = $path;
        $next[] = $key;
        $target_item = is_array($target) && array_key_exists($key, $target) ? $target[$key] : null;
        $synced_item = boblift_mls_params_only_value($item, $target_item, $next);
        if (!boblift_mls_is_skip_marker($synced_item)) {
            $result[$key] = $synced_item;
        }
    }
    return $result;
}

function boblift_mls_build_synced_section(string $section, string $target_language, string $mode): array|WP_Error
{
    $source_language = boblift_mls_source_language();
    $source_section = boblift_mls_section_options($section, $source_language);
    $target_section = boblift_mls_section_options($section, $target_language);
    if ($mode === 'params') {
        return boblift_mls_params_only_value($source_section, $target_section, [$section]);
    }

    $result = $source_section;
    $texts = [];
    $paths = [];
    boblift_mls_collect_texts($source_section, [$section], $texts, $paths);
    $translations = boblift_mls_translate_texts($texts, $target_language, $source_language);
    if (is_wp_error($translations)) {
        return $translations;
    }
    foreach ($paths as $index => $path) {
        if (array_key_exists($index, $translations)) {
            $relative = array_slice($path, 1);
            boblift_mls_set_path($result, $relative, (string) $translations[$index]);
        }
    }
    return $result;
}

function boblift_mls_source_post_for_target(int $target_post_id): ?WP_Post
{
    $source_language = boblift_mls_source_language();
    if (function_exists('pll_get_post')) {
        $source_id = (int) pll_get_post($target_post_id, $source_language);
        $source = $source_id > 0 ? get_post($source_id) : null;
        if ($source instanceof WP_Post) {
            return $source;
        }
    }
    $post = get_post($target_post_id);
    if ($post instanceof WP_Post && boblift_mls_post_language($post) === $source_language) {
        return $post;
    }
    return null;
}

function boblift_mls_page_bundle_source_hash(int $target_post_id, string $section): string
{
    $source = boblift_mls_source_post_for_target($target_post_id);
    $snapshot = [
        'section' => boblift_mls_section_options($section, boblift_mls_source_language()),
        'post' => $source instanceof WP_Post ? boblift_mls_post_seo_values($source) : [],
    ];
    return boblift_mls_stable_hash($snapshot);
}

function boblift_mls_last_page_bundle_hash(string $language, int $post_id, string $section): string
{
    $state = boblift_mls_state();
    return (string) ($state['page_sync'][$language][$post_id][$section]['source_hash'] ?? '');
}

function boblift_mls_is_known_page_bundle_section(string $section): bool
{
    return array_key_exists($section, boblift_mls_page_bundle_map());
}

function boblift_mls_page_bundle_map(): array
{
    return [
        'home' => ['slugs' => ['home'], 'public_url' => '/'],
        'about_page' => ['slugs' => ['about'], 'public_url' => '/about.html'],
        'social_media_page' => ['slugs' => ['social-media'], 'public_url' => '/social-media.html'],
        'public_welfare_page' => ['slugs' => ['public-welfare'], 'public_url' => '/public-welfare.html'],
        'privacy_policy_page' => ['slugs' => ['privacy-policy'], 'public_url' => '/privacy-policy.html'],
        'terms_of_use_page' => ['slugs' => ['terms-of-use'], 'public_url' => '/terms-of-use.html'],
        'sitemap_page' => ['slugs' => ['sitemap'], 'public_url' => '/sitemap.html'],
    ];
}

function boblift_mls_target_page_matches_bundle(int $target_post_id, string $target_language, string $section): bool
{
    if ($target_post_id <= 0 || !boblift_mls_is_known_page_bundle_section($section)) {
        return false;
    }
    $target = get_post($target_post_id);
    if (!$target instanceof WP_Post || $target->post_type !== 'page' || boblift_mls_post_language($target) !== $target_language) {
        return false;
    }
    $source = boblift_mls_find_source_page_for_bundle($section);
    if (!$source instanceof WP_Post) {
        return false;
    }
    if (function_exists('pll_get_post')) {
        $mapped_id = (int) pll_get_post($source->ID, $target_language);
        if ($mapped_id > 0) {
            return $mapped_id === $target_post_id;
        }
    }
    $source_for_target = boblift_mls_source_post_for_target($target_post_id);
    return $source_for_target instanceof WP_Post && (int) $source_for_target->ID === (int) $source->ID;
}

function boblift_mls_section_for_source_page_bundle(WP_Post $source): string
{
    foreach (array_keys(boblift_mls_page_bundle_map()) as $section) {
        $known_source = boblift_mls_find_source_page_for_bundle($section);
        if ($known_source instanceof WP_Post && (int) $known_source->ID === (int) $source->ID) {
            return $section;
        }
    }
    return '';
}

function boblift_mls_record_page_bundle_sync(string $target_language, int $target_post_id, string $section, string $mode): bool
{
    if (!boblift_mls_target_page_matches_bundle($target_post_id, $target_language, $section)) {
        return false;
    }
    $state = boblift_mls_state();
    $state['page_sync'][$target_language][$target_post_id][$section] = [
        'source_hash' => boblift_mls_page_bundle_source_hash($target_post_id, $section),
        'mode' => $mode,
        'time' => time(),
    ];
    boblift_mls_update_state($state);
    return true;
}

function boblift_mls_record_known_page_bundle_sync_for_section(string $target_language, string $section, string $mode, bool $sync_seo = false): true|WP_Error
{
    if (!boblift_mls_is_known_page_bundle_section($section) || !function_exists('pll_get_post')) {
        return true;
    }
    $source = boblift_mls_find_source_page_for_bundle($section);
    if (!$source instanceof WP_Post) {
        return true;
    }
    $target_id = (int) pll_get_post($source->ID, $target_language);
    if ($target_id <= 0) {
        return true;
    }
    if ($sync_seo && $mode === 'translate') {
        $seo_result = boblift_mls_sync_post_meta_seo($source, $target_id, $target_language, 'translate', true);
        if (is_wp_error($seo_result)) {
            return $seo_result;
        }
    }
    boblift_mls_record_page_bundle_sync($target_language, $target_id, $section, $mode);
    return true;
}

function boblift_mls_page_bundle_status_label(string $language, int $post_id, string $section): string
{
    if (!boblift_mls_target_page_matches_bundle($post_id, $language, $section)) {
        return '未记录单页同步';
    }
    $current_hash = boblift_mls_page_bundle_source_hash($post_id, $section);
    $page_hash = boblift_mls_last_page_bundle_hash($language, $post_id, $section);
    if ($page_hash !== '') {
        return hash_equals($current_hash, $page_hash) ? '已同步' : '源语言已更新，需要同步';
    }
    $section_hash = boblift_mls_last_synced_hash($language, $section);
    if ($section_hash === '') {
        return '未记录单页同步';
    }
    return hash_equals(boblift_mls_source_section_hash($section), $section_hash)
        ? '已同步（未记录单页同步）'
        : '源语言已更新，需要同步';
}

function boblift_mls_translate_assoc(array $values, string $target_language, string $source_language): array|WP_Error
{
    $keys = [];
    $texts = [];
    foreach ($values as $key => $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $keys[] = $key;
        $texts[] = $value;
    }
    if ($texts === []) {
        return [];
    }
    $translated = boblift_mls_translate_texts($texts, $target_language, $source_language);
    if (is_wp_error($translated)) {
        return $translated;
    }
    $result = [];
    foreach ($keys as $index => $key) {
        $value = (string) ($translated[$index] ?? $values[$key]);
        if (in_array($key, ['post_title', 'seo_title'], true)) {
            $value = boblift_mls_apply_static_title_fallback((string) $values[$key], $value, $target_language, $source_language);
            if (is_wp_error($value)) {
                return $value;
            }
        }
        $result[$key] = $value;
    }
    return $result;
}

function boblift_mls_post_seo_values(WP_Post $post): array
{
    $title = trim((string) get_post_meta($post->ID, '_seo_title', true));
    $description = trim((string) get_post_meta($post->ID, '_seo_description', true));
    if ($title === '' && function_exists('boblift_seo_default_title_for_post')) {
        $title = boblift_seo_default_title_for_post($post);
    }
    if ($description === '' && function_exists('boblift_seo_default_description_for_post')) {
        $description = boblift_seo_default_description_for_post($post);
    }
    return ['post_title' => $post->post_title, 'seo_title' => $title, 'seo_description' => $description];
}

function boblift_mls_term_seo_values(WP_Term $term): array
{
    $title = trim((string) get_term_meta($term->term_id, '_boblift_seo_title', true));
    $description = trim((string) get_term_meta($term->term_id, '_boblift_seo_description', true));
    if ($title === '' && function_exists('boblift_seo_default_title_for_term')) {
        $title = boblift_seo_default_title_for_term($term);
    }
    if ($description === '' && function_exists('boblift_seo_default_description_for_term')) {
        $description = boblift_seo_default_description_for_term($term);
    }
    return ['seo_title' => $title, 'seo_description' => $description];
}

function boblift_mls_post_target_seo_values(WP_Post $post, bool $include_title): array
{
    $values = [
        'seo_title' => trim((string) get_post_meta($post->ID, '_seo_title', true)),
        'seo_description' => trim((string) get_post_meta($post->ID, '_seo_description', true)),
    ];
    if ($include_title) {
        $values['post_title'] = $post->post_title;
    }
    return $values;
}

function boblift_mls_term_target_seo_values(WP_Term $term): array
{
    return [
        'seo_title' => trim((string) get_term_meta($term->term_id, '_boblift_seo_title', true)),
        'seo_description' => trim((string) get_term_meta($term->term_id, '_boblift_seo_description', true)),
    ];
}

function boblift_mls_target_seo_has_synced_hash(string $language, string $type, int $id): bool
{
    $state = boblift_mls_state();
    return (string) ($state['seo'][$language][$type][$id]['target_hash'] ?? '') !== '';
}

function boblift_mls_target_seo_can_overwrite(string $language, string $type, int $id, array $current): bool
{
    $state = boblift_mls_state();
    $last_hash = (string) ($state['seo'][$language][$type][$id]['target_hash'] ?? '');
    if ($last_hash === '') {
        foreach ($current as $key => $value) {
            if ($key === 'post_title') {
                continue;
            }
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
    return hash_equals($last_hash, boblift_mls_stable_hash($current));
}

function boblift_mls_record_target_seo_hash(string $language, string $type, int $id, array $current): void
{
    $state = boblift_mls_state();
    $state['seo'][$language][$type][$id]['target_hash'] = boblift_mls_stable_hash($current);
    $state['seo'][$language][$type][$id]['time'] = time();
    boblift_mls_update_state($state);
}

function boblift_mls_sync_post_meta_seo(int|WP_Post $source_post, int|WP_Post $target_post, string $target_language, string $mode, bool $sync_title = false, bool $force_overwrite = false): true|WP_Error
{
    if ($mode !== 'translate') {
        return true;
    }
    $source = $source_post instanceof WP_Post ? $source_post : get_post($source_post);
    $target = $target_post instanceof WP_Post ? $target_post : get_post($target_post);
    if (!$source instanceof WP_Post || !$target instanceof WP_Post) {
        return new WP_Error('boblift_mls_bad_post', '无法找到需要同步 SEO 的页面或文章。');
    }
    $values = boblift_mls_post_seo_values($source);
    if (!$sync_title) {
        unset($values['post_title']);
    }
    $translated = boblift_mls_translate_assoc($values, $target_language, boblift_mls_source_language());
    if (is_wp_error($translated)) {
        return $translated;
    }
    $current_values = boblift_mls_post_target_seo_values($target, $sync_title);
    if (!$force_overwrite && !boblift_mls_target_seo_can_overwrite($target_language, 'posts', $target->ID, $current_values)) {
        return true;
    }
    update_post_meta($target->ID, '_boblift_mls_seo_backup', ['time' => time(), 'title' => $target->post_title, 'seo_title' => get_post_meta($target->ID, '_seo_title', true), 'seo_description' => get_post_meta($target->ID, '_seo_description', true)]);
    if ($sync_title && isset($translated['post_title'])) {
        wp_update_post(['ID' => $target->ID, 'post_title' => $translated['post_title']]);
    }
    if (isset($translated['seo_title'])) {
        update_post_meta($target->ID, '_seo_title', $translated['seo_title']);
    }
    if (isset($translated['seo_description'])) {
        update_post_meta($target->ID, '_seo_description', $translated['seo_description']);
    }
    $updated = get_post($target->ID);
    if ($updated instanceof WP_Post) {
        boblift_mls_record_target_seo_hash($target_language, 'posts', $target->ID, boblift_mls_post_target_seo_values($updated, $sync_title));
    }
    return true;
}

function boblift_mls_sync_term_parameter_meta(WP_Term $source_term, WP_Term $target_term): void
{
    $keys_by_taxonomy = [
        'category' => ['_boblift_public_url'],
        'product_category' => ['_boblift_icon', '_boblift_image', '_boblift_image_id', '_boblift_sort', '_boblift_public_url'],
    ];
    $keys = $keys_by_taxonomy[$source_term->taxonomy] ?? [];
    foreach ($keys as $key) {
        $value = get_term_meta($source_term->term_id, $key, true);
        if ($value !== '') {
            update_term_meta($target_term->term_id, $key, $value);
        }
    }
}

function boblift_mls_sync_term_meta_seo(WP_Term $source_term, WP_Term $target_term, string $target_language, string $mode, bool $force_overwrite = false): true|WP_Error
{
    boblift_mls_sync_term_parameter_meta($source_term, $target_term);
    if ($mode !== 'translate') {
        return true;
    }
    $values = boblift_mls_term_seo_values($source_term);
    $translated = boblift_mls_translate_assoc($values, $target_language, boblift_mls_source_language());
    if (is_wp_error($translated)) {
        return $translated;
    }
    $current_values = boblift_mls_term_target_seo_values($target_term);
    if (!$force_overwrite && !boblift_mls_target_seo_can_overwrite($target_language, 'terms', $target_term->term_id, $current_values)) {
        return true;
    }
    update_term_meta($target_term->term_id, '_boblift_mls_seo_backup', ['time' => time(), 'seo_title' => get_term_meta($target_term->term_id, '_boblift_seo_title', true), 'seo_description' => get_term_meta($target_term->term_id, '_boblift_seo_description', true)]);
    if (isset($translated['seo_title'])) {
        update_term_meta($target_term->term_id, '_boblift_seo_title', $translated['seo_title']);
    }
    if (isset($translated['seo_description'])) {
        update_term_meta($target_term->term_id, '_boblift_seo_description', $translated['seo_description']);
    }
    boblift_mls_record_target_seo_hash($target_language, 'terms', $target_term->term_id, boblift_mls_term_target_seo_values($target_term));
    return true;
}

function boblift_mls_collect_source_seo_snapshot(): array
{
    $source_language = boblift_mls_source_language();
    $snapshot = ['posts' => [], 'terms' => []];
    foreach (['page', 'post', 'product', 'case'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        foreach ($posts as $post) {
            if ($post instanceof WP_Post) {
                $snapshot['posts'][$post_type][$post->ID] = boblift_mls_post_seo_values($post);
            }
        }
    }
    foreach (['category', 'product_category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_language]);
        if (is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $snapshot['terms'][$taxonomy][$term->term_id] = boblift_mls_term_seo_values($term);
            }
        }
    }
    return $snapshot;
}

function boblift_mls_sync_all_seo_meta(string $target_language, string $mode): true|WP_Error
{
    if ($mode !== 'translate') {
        return true;
    }
    $source_language = boblift_mls_source_language();
    foreach (['page', 'post', 'product', 'case'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        foreach ($posts as $source) {
            if (!$source instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            $target_id = (int) pll_get_post($source->ID, $target_language);
            if ($target_id <= 0) {
                continue;
            }
            $result = boblift_mls_sync_post_meta_seo($source, $target_id, $target_language, 'translate', false);
            if (is_wp_error($result)) {
                return $result;
            }
        }
    }
    foreach (['category', 'product_category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_language]);
        if (is_wp_error($terms) || !function_exists('pll_get_term')) {
            continue;
        }
        foreach ($terms as $source_term) {
            if (!$source_term instanceof WP_Term) {
                continue;
            }
            $target_id = (int) pll_get_term($source_term->term_id, $target_language);
            $target_term = $target_id > 0 ? get_term($target_id, $taxonomy) : null;
            if (!$target_term instanceof WP_Term || is_wp_error($target_term)) {
                continue;
            }
            $result = boblift_mls_sync_term_meta_seo($source_term, $target_term, $target_language, 'translate');
            if (is_wp_error($result)) {
                return $result;
            }
        }
    }
    return true;
}

function boblift_mls_find_source_page_for_bundle(string $section): ?WP_Post
{
    $bundle = boblift_mls_page_bundle_map()[$section] ?? null;
    if (!is_array($bundle)) {
        return null;
    }
    $paths = (array) ($bundle['slugs'] ?? []);
    if ($section === 'home') {
        $paths[] = '';
    }
    foreach ($paths as $path) {
        $page = $path === '' ? null : get_page_by_path($path, OBJECT, 'page');
        if ($page instanceof WP_Post) {
            return $page;
        }
    }
    $public_url = (string) ($bundle['public_url'] ?? '');
    $pages = get_posts(['post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'lang' => boblift_mls_source_language()]);
    foreach ($pages as $page) {
        if ($page instanceof WP_Post && (string) get_post_meta($page->ID, '_boblift_public_url', true) === $public_url) {
            return $page;
        }
    }
    return null;
}

function boblift_mls_sync_known_page_bundle_seo(string $target_language, string $mode): true|WP_Error
{
    if ($mode !== 'translate') {
        return true;
    }
    foreach (array_keys(boblift_mls_page_bundle_map()) as $section) {
        $result = boblift_mls_record_known_page_bundle_sync_for_section($target_language, $section, $mode, true);
        if (is_wp_error($result)) {
            return $result;
        }
    }
    return true;
}

function boblift_mls_sync_page_bundle(int $target_post_id, string $section, string $target_language, string $mode): true|WP_Error
{
    $result = boblift_mls_sync_section($section, $target_language, $mode);
    if (is_wp_error($result) || $mode !== 'translate') {
        return $result;
    }
    $source = boblift_mls_source_post_for_target($target_post_id);
    $target = get_post($target_post_id);
    if (!$source instanceof WP_Post || !$target instanceof WP_Post) {
        return new WP_Error('boblift_mls_no_source_page', '无法找到当前页面对应的主语言页面。');
    }
    $seo_result = boblift_mls_sync_post_meta_seo($source, $target, $target_language, 'translate', true);
    if (is_wp_error($seo_result)) {
        return $seo_result;
    }
    $state = boblift_mls_state();
    $state['page_sync'][$target_language][$target_post_id][$section] = [
        'source_hash' => boblift_mls_page_bundle_source_hash($target_post_id, $section),
        'mode' => $mode,
        'time' => time(),
    ];
    boblift_mls_update_state($state);
    return true;
}



function boblift_mls_restore_language_backup(string $target_language): true|WP_Error
{
    if (!in_array($target_language, boblift_mls_languages(), true)) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不存在。');
    }
    if ($target_language === boblift_mls_source_language()) {
        return new WP_Error('boblift_mls_bad_language', '不能恢复主维护语言备份。');
    }
    $backup = get_option('boblift_mls_backup_' . sanitize_key($target_language), []);
    if (!is_array($backup) || !is_array($backup['options'] ?? null)) {
        return new WP_Error('boblift_mls_no_backup', '没有找到该语言的同步前备份。');
    }
    $options = $backup['options'];
    unset($options['form_security']);
    boblift_mls_update_language_options($options, $target_language);
    return true;
}

function boblift_mls_sync_all_sections(string $target_language, string $mode): array|WP_Error
{
    if (!in_array($target_language, boblift_mls_languages(), true)) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不存在。');
    }
    if ($target_language === boblift_mls_source_language()) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不能等于源语言。');
    }
    if ($mode === 'translate' && !boblift_mls_translation_available()) {
        return new WP_Error('boblift_mls_no_translation_engine', 'Polytranslate AI 翻译函数不可用。');
    }

    $sections = boblift_mls_settings()['sections'] ?? boblift_mls_default_sections();
    $synced = [];
    $skipped = [];
    foreach ($sections as $section) {
        $section = (string) $section;
        if ($section === 'seo_meta' && $mode !== 'translate') {
            $skipped[] = $section;
            continue;
        }
        $result = boblift_mls_sync_section($section, $target_language, $mode);
        if (is_wp_error($result)) {
            $label = function_exists('boblift_mls_section_label') ? boblift_mls_section_label($section) : $section;
            return new WP_Error($result->get_error_code(), sprintf('%s：%s', $label, $result->get_error_message()));
        }
        $synced[] = $section;
    }
    $page_result = boblift_mls_sync_known_page_bundle_seo($target_language, $mode);
    if (is_wp_error($page_result)) {
        return $page_result;
    }

    return ['synced' => $synced, 'skipped' => $skipped];
}

function boblift_mls_sync_section(string $section, string $target_language, string $mode): true|WP_Error
{
    $allowed_sections = boblift_mls_settings()['sections'] ?? boblift_mls_default_sections();
    if (!in_array($section, $allowed_sections, true)) {
        return new WP_Error('boblift_mls_bad_section', '不支持的同步区块。');
    }
    if (!in_array($target_language, boblift_mls_languages(), true)) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不存在。');
    }
    if ($target_language === boblift_mls_source_language()) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不能等于源语言。');
    }
    if ($mode === 'translate' && !boblift_mls_translation_available()) {
        return new WP_Error('boblift_mls_no_translation_engine', 'Polytranslate AI 翻译函数不可用。');
    }
    if ($section === 'seo_meta' && $mode !== 'translate') {
        return new WP_Error('boblift_mls_seo_params_unavailable', 'SEO 标题和描述没有参数同步模式，请使用同步并翻译。');
    }
    if ($section === 'seo_meta') {
        $seo_result = boblift_mls_sync_all_seo_meta($target_language, $mode);
        if (is_wp_error($seo_result)) {
            return $seo_result;
        }
        $state = boblift_mls_state();
        $state['sync'][$target_language][$section] = ['source_hash' => boblift_mls_source_section_hash($section), 'source_snapshot' => boblift_mls_source_section_snapshot($section), 'mode' => $mode, 'time' => time()];
        boblift_mls_update_state($state);
        return true;
    }
    $synced_section = boblift_mls_build_synced_section($section, $target_language, $mode);
    if (is_wp_error($synced_section)) {
        return $synced_section;
    }

    $existing = boblift_mls_get_raw_options($target_language);
    update_option('boblift_mls_backup_' . sanitize_key($target_language), ['time' => time(), 'options' => $existing], false);
    $existing[$section] = $synced_section;
    unset($existing['form_security']);
    boblift_mls_update_language_options($existing, $target_language);

    $state = boblift_mls_state();
    $state['sync'][$target_language][$section] = [
        'source_hash' => boblift_mls_source_section_hash($section),
        'source_snapshot' => boblift_mls_source_section_snapshot($section),
        'mode' => $mode,
        'time' => time(),
    ];
    boblift_mls_update_state($state);
    if ($mode === 'translate') {
        $page_result = boblift_mls_record_known_page_bundle_sync_for_section($target_language, $section, $mode, true);
        if (is_wp_error($page_result)) {
            return $page_result;
        }
    }
    return true;
}
