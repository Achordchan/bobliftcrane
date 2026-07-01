<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

function ptai_boblift_static_title_glossary(): array {
    $titles = [
        'Home'                   => ['zh' => '首页', 'ja' => 'ホーム', 'ko' => '홈', 'es' => 'Inicio', 'fr' => 'Accueil', 'de' => 'Startseite', 'ru' => 'Главная', 'pt' => 'Início', 'ar' => 'الرئيسية', 'vi' => 'Trang chủ', 'th' => 'หน้าแรก', 'id' => 'Beranda'],
        'About BOB-LIFT'         => ['zh' => '关于 BOB-LIFT', 'ja' => 'BOB-LIFTについて', 'ko' => 'BOB-LIFT 소개', 'es' => 'Acerca de BOB-LIFT', 'fr' => 'À propos de BOB-LIFT', 'de' => 'Über BOB-LIFT', 'ru' => 'О BOB-LIFT', 'pt' => 'Sobre a BOB-LIFT', 'ar' => 'عن BOB-LIFT', 'vi' => 'Giới thiệu BOB-LIFT', 'th' => 'เกี่ยวกับ BOB-LIFT', 'id' => 'Tentang BOB-LIFT'],
        'About Us'               => ['zh' => '关于我们', 'ja' => '会社概要', 'ko' => '회사 소개', 'es' => 'Sobre nosotros', 'fr' => 'À propos de nous', 'de' => 'Über uns', 'ru' => 'О нас', 'pt' => 'Sobre nós', 'ar' => 'من نحن', 'vi' => 'Về chúng tôi', 'th' => 'เกี่ยวกับเรา', 'id' => 'Tentang Kami'],
        'Global Customer Visits' => ['zh' => '全球客户来访', 'ja' => 'グローバル顧客来訪', 'ko' => '글로벌 고객 방문', 'es' => 'Visitas de clientes globales', 'fr' => 'Visites de clients internationaux', 'de' => 'Globale Kundenbesuche', 'ru' => 'Визиты клиентов со всего мира', 'pt' => 'Visitas de clientes globais', 'ar' => 'زيارات العملاء العالمية', 'vi' => 'Khách hàng toàn cầu đến thăm', 'th' => 'การเยี่ยมชมของลูกค้าทั่วโลก', 'id' => 'Kunjungan Pelanggan Global'],
        'Social Media'           => ['zh' => '社交媒体', 'ja' => 'ソーシャルメディア', 'ko' => '소셜 미디어', 'es' => 'Redes sociales', 'fr' => 'Réseaux sociaux', 'de' => 'Soziale Medien', 'ru' => 'Социальные сети', 'pt' => 'Redes sociais', 'ar' => 'وسائل التواصل الاجتماعي', 'vi' => 'Mạng xã hội', 'th' => 'โซเชียลมีเดีย', 'id' => 'Media Sosial'],
        'Public Welfare'         => ['zh' => '公益', 'ja' => '社会貢献', 'ko' => '공익 활동', 'es' => 'Bienestar público', 'fr' => 'Engagement social', 'de' => 'Gemeinwohl', 'ru' => 'Общественная ответственность', 'pt' => 'Responsabilidade social', 'ar' => 'المسؤولية المجتمعية', 'vi' => 'Phúc lợi công cộng', 'th' => 'สาธารณประโยชน์', 'id' => 'Kepedulian Sosial'],
        'Contact Us'             => ['zh' => '联系我们', 'ja' => 'お問い合わせ', 'ko' => '문의하기', 'es' => 'Contáctenos', 'fr' => 'Contactez-nous', 'de' => 'Kontakt', 'ru' => 'Свяжитесь с нами', 'pt' => 'Fale conosco', 'ar' => 'اتصل بنا', 'vi' => 'Liên hệ', 'th' => 'ติดต่อเรา', 'id' => 'Hubungi Kami'],
        'Products'               => ['zh' => '产品', 'ja' => '製品', 'ko' => '제품', 'es' => 'Productos', 'fr' => 'Produits', 'de' => 'Produkte', 'ru' => 'Продукция', 'pt' => 'Produtos', 'ar' => 'المنتجات', 'vi' => 'Sản phẩm', 'th' => 'ผลิตภัณฑ์', 'id' => 'Produk'],
        'Cases'                  => ['zh' => '案例', 'ja' => '導入事例', 'ko' => '사례', 'es' => 'Casos', 'fr' => 'Cas', 'de' => 'Referenzen', 'ru' => 'Кейсы', 'pt' => 'Casos', 'ar' => 'الحالات', 'vi' => 'Dự án', 'th' => 'กรณีศึกษา', 'id' => 'Studi Kasus'],
        'News'                   => ['zh' => '新闻', 'ja' => 'ニュース', 'ko' => '뉴스', 'es' => 'Noticias', 'fr' => 'Actualités', 'de' => 'Nachrichten', 'ru' => 'Новости', 'pt' => 'Notícias', 'ar' => 'الأخبار', 'vi' => 'Tin tức', 'th' => 'ข่าวสาร', 'id' => 'Berita'],
        'Request a Quote'        => ['zh' => '请求报价', 'ja' => '見積依頼', 'ko' => '견적 요청', 'es' => 'Solicitar cotización', 'fr' => 'Demander un devis', 'de' => 'Angebot anfordern', 'ru' => 'Запросить предложение', 'pt' => 'Solicitar cotação', 'ar' => 'طلب عرض سعر', 'vi' => 'Yêu cầu báo giá', 'th' => 'ขอใบเสนอราคา', 'id' => 'Minta Penawaran'],
        'Inquiry Submitted'      => ['zh' => '询价已提交', 'ja' => 'お問い合わせ送信完了', 'ko' => '문의가 제출되었습니다', 'es' => 'Consulta enviada', 'fr' => 'Demande envoyée', 'de' => 'Anfrage gesendet', 'ru' => 'Запрос отправлен', 'pt' => 'Consulta enviada', 'ar' => 'تم إرسال الاستفسار', 'vi' => 'Đã gửi yêu cầu', 'th' => 'ส่งคำขอแล้ว', 'id' => 'Permintaan Terkirim'],
        'Privacy Policy'         => ['zh' => '隐私政策', 'ja' => 'プライバシーポリシー', 'ko' => '개인정보 처리방침', 'es' => 'Política de privacidad', 'fr' => 'Politique de confidentialité', 'de' => 'Datenschutzerklärung', 'ru' => 'Политика конфиденциальности', 'pt' => 'Política de privacidade', 'ar' => 'سياسة الخصوصية', 'vi' => 'Chính sách bảo mật', 'th' => 'นโยบายความเป็นส่วนตัว', 'id' => 'Kebijakan Privasi'],
        'Terms of Use'           => ['zh' => '使用条款', 'ja' => '利用規約', 'ko' => '이용 약관', 'es' => 'Términos de uso', 'fr' => 'Conditions d’utilisation', 'de' => 'Nutzungsbedingungen', 'ru' => 'Условия использования', 'pt' => 'Termos de uso', 'ar' => 'شروط الاستخدام', 'vi' => 'Điều khoản sử dụng', 'th' => 'ข้อกำหนดการใช้งาน', 'id' => 'Ketentuan Penggunaan'],
        'Sitemap'                => ['zh' => '网站地图', 'ja' => 'サイトマップ', 'ko' => '사이트맵', 'es' => 'Mapa del sitio', 'fr' => 'Plan du site', 'de' => 'Sitemap', 'ru' => 'Карта сайта', 'pt' => 'Mapa do site', 'ar' => 'خريطة الموقع', 'vi' => 'Sơ đồ trang web', 'th' => 'แผนผังเว็บไซต์', 'id' => 'Peta Situs'],
        'Company News'           => ['zh' => '公司新闻', 'ja' => '企業ニュース', 'ko' => '회사 뉴스', 'es' => 'Noticias de la empresa', 'fr' => 'Actualités de l’entreprise', 'de' => 'Unternehmensnachrichten', 'ru' => 'Новости компании', 'pt' => 'Notícias da empresa', 'ar' => 'أخبار الشركة', 'vi' => 'Tin công ty', 'th' => 'ข่าวบริษัท', 'id' => 'Berita Perusahaan'],
        'Product News'           => ['zh' => '产品新闻', 'ja' => '製品ニュース', 'ko' => '제품 뉴스', 'es' => 'Noticias de productos', 'fr' => 'Actualités produits', 'de' => 'Produktneuheiten', 'ru' => 'Новости продукции', 'pt' => 'Notícias de produtos', 'ar' => 'أخبار المنتجات', 'vi' => 'Tin sản phẩm', 'th' => 'ข่าวผลิตภัณฑ์', 'id' => 'Berita Produk'],
        'Industry Insight'       => ['zh' => '行业洞察', 'ja' => '業界インサイト', 'ko' => '업계 인사이트', 'es' => 'Perspectiva de la industria', 'fr' => 'Analyse du secteur', 'de' => 'Brancheneinblick', 'ru' => 'Отраслевые обзоры', 'pt' => 'Insights do setor', 'ar' => 'رؤى الصناعة', 'vi' => 'Góc nhìn ngành', 'th' => 'ข้อมูลเชิงลึกอุตสาหกรรม', 'id' => 'Wawasan Industri'],
        'Marine Solution'        => ['zh' => '船用解决方案', 'ja' => '船舶ソリューション', 'ko' => '해양 솔루션', 'es' => 'Solución marina', 'fr' => 'Solution marine', 'de' => 'Marinelösung', 'ru' => 'Морское решение', 'pt' => 'Solução marítima', 'ar' => 'حلول بحرية', 'vi' => 'Giải pháp hàng hải', 'th' => 'โซลูชันทางทะเล', 'id' => 'Solusi Kelautan'],
        'Customer Case'          => ['zh' => '客户案例', 'ja' => '顧客事例', 'ko' => '고객 사례', 'es' => 'Caso de cliente', 'fr' => 'Cas client', 'de' => 'Kundenfall', 'ru' => 'Кейс клиента', 'pt' => 'Caso de cliente', 'ar' => 'حالة عميل', 'vi' => 'Trường hợp khách hàng', 'th' => 'กรณีลูกค้า', 'id' => 'Kasus Pelanggan'],
    ];
    return $titles;
}

function ptai_boblift_normalize_language_slug( string $language ): string {
    $language = strtolower( trim( $language ) );
    return preg_replace( '/[_-].*$/', '', $language ) ?: $language;
}

function ptai_boblift_normalize_static_title( string $text ): string {
    return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) ?? $text );
}

function ptai_boblift_apply_static_title_fallback( string $sourceText, string $translatedText, string $targetLang, string $sourceLang = '' ) {
    $source = ptai_boblift_normalize_static_title( $sourceText );
    $translated = trim( $translatedText );
    $target = ptai_boblift_normalize_language_slug( $targetLang );
    $source = $source === 'BOB-LIFT Crane Solutions' ? 'About BOB-LIFT' : $source;
    $sourceLanguage = ptai_boblift_normalize_language_slug( $sourceLang );
    if ( $source === '' || $target === '' || $target === $sourceLanguage || $target === 'en' ) {
        return $translated;
    }

    $seoSuffix = '';
    $baseSource = $source;
    if ( preg_match( '/^(.+?)\s*\|\s*(BOB-LIFT)$/i', $source, $matches ) ) {
        $baseSource = ptai_boblift_normalize_static_title( $matches[1] );
        $seoSuffix = ' | ' . $matches[2];
    }

    $glossary = ptai_boblift_static_title_glossary();
    if ( !isset( $glossary[$baseSource] ) ) {
        return $translated;
    }

    $sameAsSource = strcasecmp( ptai_boblift_normalize_static_title( $translated ), $source ) === 0;
    if ( $translated !== '' && !$sameAsSource ) {
        return $translated;
    }
    if ( isset( $glossary[$baseSource][$target] ) ) {
        return $glossary[$baseSource][$target] . $seoSuffix;
    }

    return new WP_Error(
        'ptai_boblift_static_title_untranslated',
        sprintf( '静态页面标题“%s”没有被翻译为目标语言“%s”，已停止写入英文标题。', $source, $targetLang )
    );
}

function ptai_boblift_translate_post_meta( int $sourceId, int $targetId, string $targetLang, string $sourceLang, string $postType ) {
    $map = ptai_boblift_post_meta_map( $postType );
    if ( empty( $map ) ) {
        return true;
    }

    foreach ( $map as $key => $strategy ) {
        $values = get_post_meta( $sourceId, $key, false );
        if ( empty( $values ) ) {
            continue;
        }

        $translatedValues = [];
        foreach ( $values as $value ) {
            $translated = ptai_boblift_translate_meta_value( maybe_unserialize( $value ), $strategy, $targetLang, $sourceLang );
            if ( is_wp_error( $translated ) ) {
                return $translated;
            }
            $translatedValues[] = $translated;
        }

        delete_post_meta( $targetId, $key );
        foreach ( $translatedValues as $translatedValue ) {
            add_post_meta( $targetId, $key, $translatedValue );
        }
    }

    return true;
}

function ptai_boblift_translate_term_meta( int $sourceTermId, int $targetTermId, string $targetLang, string $sourceLang, string $taxonomy ) {
    $map = ptai_boblift_term_meta_map( $taxonomy );
    if ( empty( $map ) ) {
        return true;
    }

    foreach ( $map as $key => $strategy ) {
        $values = get_term_meta( $sourceTermId, $key, false );
        if ( empty( $values ) ) {
            continue;
        }

        $translatedValues = [];
        foreach ( $values as $value ) {
            $translated = ptai_boblift_translate_meta_value( maybe_unserialize( $value ), $strategy, $targetLang, $sourceLang );
            if ( is_wp_error( $translated ) ) {
                return $translated;
            }
            $translatedValues[] = $translated;
        }

        delete_term_meta( $targetTermId, $key );
        foreach ( $translatedValues as $translatedValue ) {
            add_term_meta( $targetTermId, $key, $translatedValue );
        }
    }

    return true;
}

function ptai_boblift_post_meta_map( string $postType ): array {
    $sharedSeo = [
        '_seo_title'       => 'text',
        '_seo_description' => 'text',
    ];

    if ( $postType === 'product' ) {
        return [
            '_model'             => 'copy',
            '_capacity'          => 'text',
            '_capacity_value'    => 'copy',
            '_boom_type'         => 'copy',
            '_catalog_pdf_url'   => 'copy',
            '_applications'      => 'copy',
            '_status'            => 'copy',
            '_short_description' => 'text',
            '_tags'              => 'csv',
            '_specs'             => 'json',
            '_features'          => 'json',
            '_detail_images'     => 'json',
            '_gallery'           => 'json',
            '_color_variants'    => 'json',
            '_hotspots'          => 'json',
        ] + $sharedSeo;
    }

    if ( $postType === 'case' ) {
        return [
            '_type'                => 'text',
            '_summary'             => 'text',
            '_country'             => 'text',
            '_product_name'        => 'text',
            '_application'         => 'text',
            '_feedback_quote'      => 'text',
            '_facts'               => 'json',
            '_sections'            => 'json',
            '_case_kind'           => 'copy',
            '_feedback_media_type' => 'copy',
            '_feedback_media_url'  => 'copy',
        ] + $sharedSeo;
    }

    if ( $postType === 'page' || $postType === 'post' ) {
        return $sharedSeo;
    }

    return [];
}

function ptai_boblift_term_meta_map( string $taxonomy ): array {
    if ( $taxonomy === 'product_category' ) {
        return [
            '_boblift_banner_label'   => 'text',
            '_boblift_banner_title'   => 'text',
            '_boblift_seo_title'      => 'text',
            '_boblift_seo_description'=> 'text',
        ];
    }

    if ( $taxonomy === 'category' ) {
        return [
            '_boblift_seo_title'       => 'text',
            '_boblift_seo_description' => 'text',
        ];
    }

    return [];
}

function ptai_boblift_translate_meta_value( mixed $value, string $strategy, string $targetLang, string $sourceLang ) {
    if ( $strategy === 'copy' ) {
        return $value;
    }

    if ( $strategy === 'json' ) {
        return ptai_boblift_translate_json_value( $value, $targetLang, $sourceLang );
    }

    if ( $strategy === 'csv' ) {
        return ptai_boblift_translate_csv_value( (string) $value, $targetLang, $sourceLang );
    }

    return ptai_boblift_translate_plain_text( (string) $value, $targetLang, $sourceLang );
}

function ptai_boblift_translate_plain_text( string $text, string $targetLang, string $sourceLang ) {
    if ( !ptai_boblift_should_translate_string( $text ) ) {
        return $text;
    }

    $result = ptai_call_translation_ai( [$text], $targetLang, $sourceLang );
    if ( is_array( $result ) && isset( $result['success'] ) && $result['success'] === false ) {
        return new WP_Error( 'boblift_meta_translation_failed', (string) ( $result['error'] ?? 'Unknown translation error' ) );
    }

    $translated = is_array( $result ) && isset( $result[0] ) ? (string) $result[0] : $text;
    return ptai_boblift_apply_static_title_fallback( $text, $translated, $targetLang, $sourceLang );
}

function ptai_boblift_translate_csv_value( string $value, string $targetLang, string $sourceLang ) {
    if ( trim( $value ) === '' ) {
        return $value;
    }

    $parts = array_map( 'trim', explode( ',', $value ) );
    $texts = array_values( array_filter( $parts, 'ptai_boblift_should_translate_string' ) );
    if ( empty( $texts ) ) {
        return $value;
    }

    $translations = ptai_call_translation_ai( $texts, $targetLang, $sourceLang );
    if ( is_array( $translations ) && isset( $translations['success'] ) && $translations['success'] === false ) {
        return new WP_Error( 'boblift_csv_translation_failed', (string) ( $translations['error'] ?? 'Unknown translation error' ) );
    }
    if ( !is_array( $translations ) ) {
        return $value;
    }

    $cursor = 0;
    foreach ( $parts as $index => $part ) {
        if ( ptai_boblift_should_translate_string( $part ) && isset( $translations[$cursor] ) ) {
            $parts[$index] = (string) $translations[$cursor];
            $cursor++;
        }
    }

    return implode( ',', $parts );
}

function ptai_boblift_translate_json_value( mixed $value, string $targetLang, string $sourceLang ) {
    if ( is_string( $value ) ) {
        $decoded = json_decode( $value, true );
        if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $decoded ) ) {
            return ptai_boblift_translate_plain_text( $value, $targetLang, $sourceLang );
        }
    } elseif ( is_array( $value ) ) {
        $decoded = $value;
    } else {
        return $value;
    }

    $texts = [];
    ptai_boblift_collect_translatable_strings( $decoded, $texts );
    if ( empty( $texts ) ) {
        return is_string( $value ) ? $value : $decoded;
    }

    $translations = ptai_call_translation_ai( $texts, $targetLang, $sourceLang );
    if ( is_array( $translations ) && isset( $translations['success'] ) && $translations['success'] === false ) {
        return new WP_Error( 'boblift_json_translation_failed', (string) ( $translations['error'] ?? 'Unknown translation error' ) );
    }
    if ( !is_array( $translations ) ) {
        return is_string( $value ) ? $value : $decoded;
    }

    $cursor = 0;
    $translated = ptai_boblift_apply_translatable_strings( $decoded, $translations, $cursor );
    return is_string( $value ) ? wp_json_encode( $translated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : $translated;
}

function ptai_boblift_collect_translatable_strings( mixed $value, array &$texts, array $path = [] ): void {
    if ( is_string( $value ) ) {
        $key = end( $path );
        if ( ptai_boblift_should_translate_string( $value, $key === false ? '' : (string) $key, $path ) ) {
            $texts[] = $value;
        }
        return;
    }

    if ( !is_array( $value ) ) {
        return;
    }

    foreach ( $value as $key => $item ) {
        $nextPath = $path;
        $nextPath[] = $key;
        ptai_boblift_collect_translatable_strings( $item, $texts, $nextPath );
    }
}

function ptai_boblift_apply_translatable_strings( mixed $value, array $translations, int &$cursor, array $path = [] ): mixed {
    if ( is_string( $value ) ) {
        $key = end( $path );
        if ( ptai_boblift_should_translate_string( $value, $key === false ? '' : (string) $key, $path ) && isset( $translations[$cursor] ) ) {
            $value = (string) $translations[$cursor];
            $cursor++;
        }
        return $value;
    }

    if ( !is_array( $value ) ) {
        return $value;
    }

    foreach ( $value as $key => $item ) {
        $nextPath = $path;
        $nextPath[] = $key;
        $value[$key] = ptai_boblift_apply_translatable_strings( $item, $translations, $cursor, $nextPath );
    }

    return $value;
}

function ptai_boblift_should_translate_string( string $value, string $key = '', array $path = [] ): bool {
    $value = trim( $value );
    if ( $value === '' ) {
        return false;
    }

    $blockedKeys = ['id', 'slug', 'href', 'url', 'src', 'path', 'image', 'icon', 'iconName', 'video_file', 'youtube_url', 'target', 'tone', 'type', 'key'];
    if ( in_array( $key, $blockedKeys, true ) ) {
        return false;
    }

    if ( preg_match( '#^(https?:)?//#i', $value ) || str_starts_with( $value, '/' ) || preg_match( '#^(mailto|tel|javascript):#i', $value ) ) {
        return false;
    }
    if ( preg_match( '#(^|/)[^/]+\.(png|jpe?g|webp|gif|svg|mp4|webm|pdf|zip)$#i', $value ) ) {
        return false;
    }
    if ( preg_match( '#^[\d\s.,+\-/%°×x]+$#u', $value ) ) {
        return false;
    }
    return true;
}
