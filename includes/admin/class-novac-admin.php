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

        add_submenu_page(
            'novac-settings',
            __( 'Settings', 'novac' ),
            __( 'Settings', 'novac' ),
            'novac_manage_settings',
            'novac-settings'
        );

        add_submenu_page(
            'novac-settings',
            __( 'Transactions', 'novac' ),
            __( 'Transactions', 'novac' ),
            'novac_view_transactions',
            'novac-transactions',
            [ __CLASS__, 'render_admin_page' ]
        );
    }

    /**
     * Enqueue React app for admin page.
     */
    public static function enqueue_scripts( $hook ) {
        if ( ! in_array( $hook, [ 'toplevel_page_novac-settings', 'novac-payments_page_novac-transactions' ], true ) ) {
            return;
        }

        $build_path = plugin_dir_path( NOVAC_PLUGIN_FILE ) . 'includes/admin/build/';
        $asset_url  = plugins_url( 'includes/admin/build', NOVAC_PLUGIN_FILE );

        // Load asset file for dependencies and version.
        $asset_file = require $build_path . 'index.asset.php';

        // Register 'react' as an alias to 'wp-element' if not already registered.
        if ( ! wp_script_is( 'react', 'registered' ) ) {
            wp_register_script( 'react', false, [ 'wp-element' ], false, false );
            wp_register_script( 'react-dom', false, [ 'wp-element' ], false, false );
        }

        wp_enqueue_script(
            'novac-admin',
            $asset_url . '/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        // Enqueue WordPress components styles.
        wp_enqueue_style( 'wp-components' );

        wp_enqueue_style(
            'novac-admin-style',
            $asset_url . '/style-index.css',
            [ 'wp-components' ],
            $asset_file['version']
        );

        wp_localize_script( 'novac-admin', 'novacData', [
            'root'  => esc_url_raw( rest_url( 'novac/v1/' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'page'  => isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : 'novac-settings',
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

        register_rest_route( 'novac/v1', '/transactions', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_transactions' ],
            'permission_callback' => fn() => current_user_can( 'novac_view_transactions' ),
        ] );

        register_rest_route( 'novac/v1', '/transactions/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_transaction' ],
            'permission_callback' => fn() => current_user_can( 'novac_view_transactions' ),
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

    public static function get_transactions( $request ) {
        $page     = $request->get_param( 'page' ) ?? 1;
        $per_page = $request->get_param( 'per_page' ) ?? 20;
        $search   = $request->get_param( 'search' ) ?? null;
        $status   = $request->get_param( 'status' ) ?? null;

        $result = \Novac\Novac\Database\Transactions::get_all( [
            'page'     => $page,
            'per_page' => $per_page,
            'search'   => $search,
            'status'   => $status,
        ] );

        return rest_ensure_response( $result );
    }

    public static function get_transaction( $request ) {
        $id = $request->get_param( 'id' );

        global $wpdb;
        $table       = \Novac\Novac\Database\Transactions::get_table_name();
        $transaction = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

        if ( ! $transaction ) {
            return new \WP_Error( 'not_found', 'Transaction not found', [ 'status' => 404 ] );
        }

        if ( ! empty( $transaction->metadata ) ) {
            $transaction->metadata = wp_json_decode( $transaction->metadata, true );
        }

        return rest_ensure_response( $transaction );
    }
}