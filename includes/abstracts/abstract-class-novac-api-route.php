<?php
/**
 * Base class for Novac API routes.
 *
 * @package   Novac/Classes/Novac_API_Route
 * @author    Olaobaju Abraham
 * @copyright Copyright (c) 2025, WP Novac LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     0.0.1
 * @version   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Novac_API_Route' ) ) :

    /**
     * Charitable_API_Route
     *
     * @since 0.0.1
     */
    abstract class Novac_API_Route extends WP_REST_Controller {

        /**
         * Namespace.
         *
         * @since 0.0.1
         *
         * @var   string
         */
        protected $namespace;

        /**
         * API version.
         *
         * @since 0.0.1
         *
         * @var   int
         */
        protected $version;

        /**
         * Set up the API namespace.
         *
         * @since 0.0.1
         */
        public function __construct() {
            $this->version   = 1;
            $this->namespace = 'novac/v' . $this->version;
        }

        /**
         * Returns whether the current user can export Novac reports.
         *
         * @since  0.0.1
         *
         * @return boolean
         */
        public function user_can_get_novac_reports() {
            return current_user_can( 'export_novac_reports' );
        }
    }

endif;
