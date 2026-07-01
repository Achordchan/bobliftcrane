<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Enqueue admin JS
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook === 'toplevel_page_polytranslate-ai' ) {
        // Alpinjs
        wp_enqueue_script(
            'alpine-js',
            plugin_dir_url( __DIR__ ) . 'assets/js/alpinejs.min.js',
            [],
            '3.15.8',
            [
                'strategy' => 'defer',
            ]
        );
        wp_enqueue_script(
            'jquery.tablesorter-js',
            plugin_dir_url( __DIR__ ) . 'assets/js/jquery.tablesorter.min.js',
            ['jquery'],
            '2.32.0',
            true
        );
        wp_enqueue_script(
            'jquery.tablesorter.widgets-js',
            plugin_dir_url( __DIR__ ) . 'assets/js/jquery.tablesorter.widgets.min.js',
            ['jquery', 'jquery.tablesorter-js'],
            '2.32.0',
            true
        );
        wp_enqueue_script(
            'tablesorter.widget-grouping-js',
            plugin_dir_url( __DIR__ ) . 'assets/js/tablesorter.widget-grouping.min.js',
            ['jquery', 'jquery.tablesorter-js'],
            '2.32.0',
            true
        );
        wp_enqueue_script(
            'polytranslate-ai-advanced-tab-script',
            plugin_dir_url( __DIR__ ) . 'assets/js/advanced-tab.js',
            ['jquery', 'jquery.tablesorter-js'],
            PTAI_PLUGIN_VERSION,
            true
        );
        wp_enqueue_style(
            'jquery.tablesorter-css',
            plugin_dir_url( __DIR__ ) . 'assets/css/tablesorter.theme.default.min.css',
            [],
            '2.32.0'
        );
        wp_enqueue_style(
            'tablesorter.widget-grouping-css',
            plugin_dir_url( __DIR__ ) . 'assets/css/tablesorter.widget.grouping.min.css',
            [],
            '2.32.0'
        );
    }
    // Enqueue outside of the main plugin page
    wp_enqueue_script(
        'polytranslate-ai-script',
        plugin_dir_url( __DIR__ ) . 'assets/js/admin.js',
        ['jquery'],
        PTAI_PLUGIN_VERSION,
        true
    );
    wp_localize_script( 'polytranslate-ai-script', 'ptaiData', [
        'isPro' => true,
        'nonce' => wp_create_nonce( 'ptai_nonce' ),
        'i18n'  => [
            'testingConnection'    => __( 'Testing connection...', 'polytranslate-ai' ),
            'connectionSuccessful' => __( 'Connection successful!', 'polytranslate-ai' ),
            'connectionFailed'     => __( 'Connection failed. Please check your API key and try again.', 'polytranslate-ai' ),
            'upgradeToPro'         => __( 'Upgrade to Pro', 'polytranslate-ai' ),
            'bulkTranslate'        => __( 'Bulk Translate', 'polytranslate-ai' ),
            'aiBulkTranslate'      => __( '🤖 AI Bulk Translate', 'polytranslate-ai' ),
            'BtnFill'              => __( 'Fill Empty Translations', 'polytranslate-ai' ),
            'BtnWorking'           => __( '⏳ Working…', 'polytranslate-ai' ),
            'ErrorNothingToFill'   => __( '✅ Nothing to fill. Enable "Overwrite existing" to re-translate.', 'polytranslate-ai' ),
            'ErrorGeneric'         => __( '❌ Error:', 'polytranslate-ai' ),
            'Translating'          => __( 'Translating', 'polytranslate-ai' ),
            'Strings'              => __( 'strings', 'polytranslate-ai' ),
            'OverwriteExisting'    => __( 'Overwrite existing', 'polytranslate-ai' ),
            'StatusDone'           => __( 'fields filled — review then click <em>Save Changes</em>.', 'polytranslate-ai' ),
        ],
    ] );
    wp_enqueue_style(
        'polytranslate-ai-style',
        plugin_dir_url( __DIR__ ) . 'assets/css/admin.css',
        [],
        PTAI_PLUGIN_VERSION
    );
} );
// Enqueue Loader
if ( is_admin() ) {
    add_action( 'admin_footer', function () {
        echo '<div class="polytranslate-ai-loader-overlay-container" style="display:none;"><svg class="loader-overlay" width="60" height="60" viewBox="0 0 50 50"><defs><linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#000"></stop><stop offset="100%" stop-color="#000"></stop></linearGradient></defs><circle cx="25" cy="25" r="20" fill="none" stroke="url(#gradient)" stroke-width="4" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"></animateTransform></circle><text x="25" y="28" text-anchor="middle" font-size="10" fill="#000" font-weight="bold"></text></svg></div>';
    } );
}