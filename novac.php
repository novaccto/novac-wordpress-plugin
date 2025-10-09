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
use Novac\Novac\Admin\Admin;
use Novac\Novac\Database\Transactions;
use Novac\Novac\Webhooks\Handler;
use Novac\Novac\Frontend\Payment_Form;
use Novac\Novac\Blocks\Payment_Block;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

const NOVAC_PLUGIN_FILE = __FILE__;

// Initialize user roles and capabilities.
require_once plugin_dir_path( __FILE__ ) . 'includes/users/class-novac-roles.php';
Roles::init();

// Initialize database.
require_once plugin_dir_path( __FILE__ ) . 'includes/database/class-novac-transactions.php';
Transactions::init();

// Initialize admin.
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-novac-admin.php';
Admin::init();

// Initialize webhook handler.
require_once plugin_dir_path( __FILE__ ) . 'includes/webhooks/class-novac-handler.php';
Handler::init();

// Initialize frontend.
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/class-novac-payment-form.php';
Payment_Form::init();

// Initialize blocks.
require_once plugin_dir_path( __FILE__ ) . 'includes/blocks/class-novac-payment-block.php';
Payment_Block::init();
