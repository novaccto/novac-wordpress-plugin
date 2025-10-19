<?php
namespace Novac\Novac\Users;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Novac\Novac\Logger\Logger;

class Roles {

    /**
     * Prefix used for all custom capabilities.
     */
    private const CAP_PREFIX = 'novac_';

    /**
     * Capabilities used by this plugin.
     */
    private const CAPS = [
        'manage_settings',
        'view_transactions',
        'refund_transactions',
        'export_transactions',
    ];

    /**
     * Custom roles added by Novac.
     */
    private const ROLES = [
        'novac_payment_manager' => [
            'label' => 'Novac Payment Manager',
            'caps'  => [
                'read' => true,
            ],
        ],
        'novac_finance_analyst' => [
            'label' => 'Novac Finance Analyst',
            'caps'  => [
                'read' => true,
            ],
        ],
    ];

    /**
     * Initialize hooks.
     */
    public static function init() {
        register_activation_hook( NOVAC_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
        register_deactivation_hook( NOVAC_PLUGIN_FILE, [ __CLASS__, 'deactivate' ] );
        add_action( 'init', [ __CLASS__, 'maybe_add_caps_for_new_sites' ] );
    }

    /**
     * Activation hook: Add roles and capabilities.
     */
    public static function activate() {
		$logger = Logger::instance();
	    $logger->info('Activating Novac Payments');
        self::add_roles();
	    $logger->info('Added Novac Payments roles');
        self::add_caps();
	    $logger->info('Adding Novac Payments capabilities');
    }

    /**
     * Deactivation hook: Remove roles and capabilities.
     */
    public static function deactivate() {
	    $logger = Logger::instance();
	    $logger->info('Deactivating Novac Payments');
        self::remove_caps();
	    $logger->info('Removed Novac Payments capabilities');
        self::remove_roles();
	    $logger->info('Removed Novac Payments roles');
    }

    /**
     * Add custom roles.
     */
    private static function add_roles() {
        foreach ( self::ROLES as $role => $data ) {
            add_role( $role, __( $data['label'], 'novac' ), $data['caps'] );
        }
    }

    /**
     * Remove custom roles.
     */
    private static function remove_roles() {
        foreach ( array_keys( self::ROLES ) as $role ) {
            remove_role( $role );
        }
    }

    /**
     * Add capabilities to administrator and custom roles.
     */
    private static function add_caps() {
        $caps = self::prefixed_caps();

        $roles = [
            'administrator'         => $caps,
            'novac_payment_manager' => $caps,
            'novac_finance_analyst' => [
                self::prefixed( 'view_transactions' ),
                self::prefixed( 'export_transactions' ),
            ],
        ];

        foreach ( $roles as $role_name => $role_caps ) {
            $role = get_role( $role_name );
            if ( ! $role ) {
                continue;
            }

            foreach ( $role_caps as $cap ) {
                $role->add_cap( $cap );
            }
        }
    }

    /**
     * Remove capabilities on deactivation.
     */
    private static function remove_caps() {
        $all_caps = self::prefixed_caps();

        $roles = [ 'administrator', 'novac_payment_manager', 'novac_finance_analyst' ];
        foreach ( $roles as $role_name ) {
            $role = get_role( $role_name );
            if ( ! $role ) {
                continue;
            }

            foreach ( $all_caps as $cap ) {
                $role->remove_cap( $cap );
            }
        }
    }

    /**
     * Handles multisite safety — ensures roles are added on new sites.
     */
    public static function maybe_add_caps_for_new_sites() {
        if ( is_multisite() && is_main_site() ) {
            self::add_caps();
        }
    }

    /**
     * Return a list of fully prefixed capabilities.
     */
    private static function prefixed_caps(): array {
        return array_map( [ __CLASS__, 'prefixed' ], self::CAPS );
    }

    /**
     * Prefix a single capability.
     */
    private static function prefixed( string $cap ): string {
        return self::CAP_PREFIX . $cap;
    }
}
