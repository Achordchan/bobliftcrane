<?php

/**
 * Plugin Name: PolyTranslate AI for Polylang
 * Description: AI automatic translation for Polylang. Translate posts, pages, WooCommerce, ACF, SEO meta & custom fields using OpenAI, DeepL, Google or OpenRouter.
 * Version: 5.0.1
 * Author: Almhdy
 * Text Domain: polytranslate-ai
 * Domain Path: /languages
 * Author URI: https://github.com/almhdy
 * Requires Plugins: polylang
 * Update URI: false
 * License: GPLv3
 * License URI: https://opensource.org/licenses/GPL-3.0
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
// BOB-LIFT customised build (OpenAI-compatible base URL): never replace it with the upstream release.
add_filter( 'site_transient_update_plugins', function ( $value ) {
    if ( is_object( $value ) && isset( $value->response['polytranslate-ai/polytranslate-ai.php'] ) ) {
        unset( $value->response['polytranslate-ai/polytranslate-ai.php'] );
    }
    return $value;
}, 999 );
add_filter( 'auto_update_plugin', function ( $update, $item ) {
    return ( isset( $item->plugin ) && 'polytranslate-ai/polytranslate-ai.php' === $item->plugin ) ? false : $update;
}, 999, 2 );
if ( function_exists( 'ptai_fs' ) ) {
    ptai_fs()->set_basename( false, __FILE__ );
} else {
    /**
     * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
     * `function_exists` CALL ABOVE TO PROPERLY WORK.
     */
    if ( !function_exists( 'ptai_fs' ) ) {
        // Create a helper function for easy SDK access.
        function ptai_fs() {
            global $ptai_fs;
            if ( !isset( $ptai_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $ptai_fs = fs_dynamic_init( array(
                    'id'               => '19310',
                    'slug'             => 'polytranslate-ai',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_77bf2e53282ccae346dece922158a',
                    'is_premium'       => false,
                    'premium_suffix'   => 'Pro',
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => true,
                    'menu'             => array(
                        'slug'    => 'polytranslate-ai',
                        'support' => false,
                    ),
                    'is_live'          => true,
                ) );
            }
            return $ptai_fs;
        }

        // Init Freemius.
        ptai_fs();
        // Signal that SDK was initiated.
        do_action( 'ptai_fs_loaded' );
    }
    /**
     * Deactivate plugin if Polylang is not active
     */
    add_action( 'admin_init', 'polytranslate_ai_check_polylang_dependency' );
    function polytranslate_ai_check_polylang_dependency() {
        if ( !function_exists( 'pll_languages_list' ) ) {
            // Show error notice
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>PolyTranslate AI</strong> requires <a href="https://wordpress.org/plugins/polylang/" target="_blank">Polylang</a> or Polylang Pro to function. Please install and activate Polylang first.</p>';
                echo '</div>';
            } );
            // Deactivate the plugin
            // deactivate_plugins(plugin_basename(__FILE__));
            // Hide "Plugin activated" notice if polylang is not active
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // if (isset($_GET['activate'])) {
            //   unset($_GET['activate']);
            // }
        }
    }

    define( 'PTAI_PLUGIN_VERSION', '5.0.1' );
    define( 'PTAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'PTAI_INCLUDES_DIR', PTAI_PLUGIN_DIR . 'includes/' );
    define( 'PTAI_ADMIN_DIR', PTAI_PLUGIN_DIR . 'admin/' );
    define( 'PTAI_ASSETS_DIR', PTAI_PLUGIN_DIR . 'assets/' );
    require_once PTAI_INCLUDES_DIR . 'enqueue.php';
    require_once PTAI_ADMIN_DIR . 'settings.php';
    require_once PTAI_ADMIN_DIR . 'api-tab.php';
    require_once PTAI_ADMIN_DIR . 'translation-tab.php';
    require_once PTAI_ADMIN_DIR . 'advanced-tab.php';
    require_once PTAI_ADMIN_DIR . 'translatable-strings-tab.php';
    require_once PTAI_ADMIN_DIR . 'free-vs-pro-tab.php';
    require_once PTAI_ADMIN_DIR . 'row-actions.php';
    require_once PTAI_ADMIN_DIR . 'helper.php';
    require_once PTAI_ADMIN_DIR . 'polylang-register-strings.php';
    require_once PTAI_ADMIN_DIR . 'restrictor.php';
    require_once PTAI_INCLUDES_DIR . 'translation-handler.php';
    require_once PTAI_INCLUDES_DIR . 'content-handler.php';
    require_once PTAI_INCLUDES_DIR . 'link-targets.php';
    require_once PTAI_INCLUDES_DIR . 'ai-connection.php';
    require_once PTAI_INCLUDES_DIR . 'boblift-compat.php';
    require_once PTAI_INCLUDES_DIR . 'api-test-handler.php';
    /**
     * Add settings link on plugin page
     */
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'polytranslate_ai_plugin_action_links' );
    function polytranslate_ai_plugin_action_links(  $links  ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=polytranslate-ai' ) . '">' . __( 'Settings', 'polytranslate-ai' ) . '</a>';
        array_unshift( $links, $settings_link );
        // Add at the beginning
        return $links;
    }

}
