<?php
namespace Novac\Novac\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );

        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
    }

    /**
     * Register the plugin admin menu.
     */
    public static function register_menu() {
        add_menu_page(
            __( 'Novac Payments', 'novac' ),
            __( 'Novac Payments', 'novac' ),
            'novac_manage_settings', // Capability check
            'novac-settings',
            [ __CLASS__, 'render_admin_page' ],
            'dashicons-money-alt',
            56
        );
    }

    /**
     * Enqueue React app for admin page.
     */
    public static function enqueue_scripts( $hook ) {
        if ( $hook !== 'toplevel_page_novac-settings' ) {
            return;
        }

        $asset_url = plugins_url( 'admin/build', NOVAC_PLUGIN_FILE );

        wp_enqueue_script(
            'novac-admin',
            $asset_url . '/index.js',
            [ 'wp-element', 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-i18n' ],
            filemtime( plugin_dir_path( NOVAC_PLUGIN_FILE ) . 'admin/build/index.js' ),
            true
        );

        wp_enqueue_style(
            'novac-admin-style',
            $asset_url . '/index.css',
            [],
            filemtime( plugin_dir_path( NOVAC_PLUGIN_FILE ) . 'admin/build/index.css' )
        );

        wp_localize_script( 'novac-admin', 'novacData', [
            'root'  => esc_url_raw( rest_url( 'novac/v1/' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    /**
     * Admin page wrapper (React mounts here).
     */
    public static function render_admin_page() {
        echo '<div id="novac-admin-root"></div>';
    }

    /**
     * Register REST routes for settings CRUD.
     */
    public static function register_rest_routes() {
        register_rest_route( 'novac/v1', '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_settings' ],
                'permission_callback' => fn() => current_user_can( 'novac_manage_settings' ),
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'save_settings' ],
                'permission_callback' => fn() => current_user_can( 'novac_manage_settings' ),
            ],
        ] );
    }

    public static function get_settings() {
        return get_option( 'novac_settings', [
            'public_key' => '',
            'secret_key' => '',
            'mode'       => 'test',
            'webhook_url'=> home_url( '/?novac-webhook=1' ),
        ] );
    }

    public static function save_settings( $request ) {
        $params = $request->get_json_params();

        $settings = [
            'public_key'  => sanitize_text_field( $params['public_key'] ?? '' ),
            'secret_key'  => sanitize_text_field( $params['secret_key'] ?? '' ),
            'mode'        => in_array( $params['mode'] ?? '', [ 'test', 'live' ], true ) ? $params['mode'] : 'test',
            'webhook_url' => esc_url_raw( $params['webhook_url'] ?? home_url( '/?novac-webhook=1' ) ),
        ];

        update_option( 'novac_settings', $settings );

        return rest_ensure_response( $settings );
    }
}
