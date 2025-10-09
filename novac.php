<?php
/**
 * Plugin Name: Novac
 * Plugin URI: https://www.novacpayment.com
 * Description: The official plugin of Novac Payment Gateway for WordPress.
 * Version: 0.0.1
 * Author: Novac Payments
 * Author URI: https://www.novacpayment.com
 * Developer: Novac Payments Developers
 * Developer URI: https://developer.novacpayment.com
 * Text Domain: novacpayments
 * Domain Path: /languages
 *
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Novac
 */

declare(strict_types=1);

use Novac\Novac\Users\Roles;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

const NOVAC_PLUGIN_FILE = __FILE__;

require_once plugin_dir_path( __FILE__ ) . 'includes/users/class-novac-roles.php';

Roles::init();

// TODO: Implement the plugin.