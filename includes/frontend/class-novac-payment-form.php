<?php
/**
 * Frontend Payment Form
 *
 * @package Novac
 */

namespace Novac\Novac\Frontend;

use Novac\Novac\Api\Api_Client;
use Novac\Novac\Database\Transactions;

defined( 'ABSPATH' ) || exit;

class Payment_Form {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_shortcode( 'novac_payment_form', [ __CLASS__, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_novac_initiate_payment', [ __CLASS__, 'handle_payment_initiation' ] );
		add_action( 'wp_ajax_nopriv_novac_initiate_payment', [ __CLASS__, 'handle_payment_initiation' ] );
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public static function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! has_shortcode( $post->post_content, 'novac_payment_form' ) && ! has_block( 'novac/payment-form', $post ) ) {
			return;
		}

		wp_enqueue_style(
			'novac-frontend',
			plugins_url( 'frontend/css/style.css', NOVAC_PLUGIN_FILE ),
			[],
			'0.0.1'
		);

		wp_enqueue_script(
			'novac-frontend',
			plugins_url( 'frontend/js/payment-form.js', NOVAC_PLUGIN_FILE ),
			[ 'jquery' ],
			'0.0.1',
			true
		);

		wp_localize_script( 'novac-frontend', 'novacFrontend', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'novac_payment' ),
		] );
	}

	/**
	 * Render payment form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'amount'      => '',
			'currency'    => 'NGN',
			'description' => 'Payment',
			'button_text' => 'Pay Now',
		], $atts );

		ob_start();
		?>
		<div class="novac-payment-form-wrapper">
			<form class="novac-payment-form" id="novac-payment-form">
				<div class="novac-form-messages"></div>
				
				<div class="novac-form-group">
					<label for="novac-name"><?php esc_html_e( 'Full Name', 'novac' ); ?> <span class="required">*</span></label>
					<input type="text" id="novac-name" name="name" required />
				</div>

				<div class="novac-form-group">
					<label for="novac-email"><?php esc_html_e( 'Email Address', 'novac' ); ?> <span class="required">*</span></label>
					<input type="email" id="novac-email" name="email" required />
				</div>

				<?php if ( empty( $atts['amount'] ) ) : ?>
				<div class="novac-form-group">
					<label for="novac-amount"><?php esc_html_e( 'Amount', 'novac' ); ?> <span class="required">*</span></label>
					<input type="number" id="novac-amount" name="amount" step="0.01" min="0" required />
				</div>
				<?php else : ?>
				<input type="hidden" name="amount" value="<?php echo esc_attr( $atts['amount'] ); ?>" />
				<div class="novac-form-group">
					<label><?php esc_html_e( 'Amount', 'novac' ); ?></label>
					<div class="novac-amount-display">
						<?php echo esc_html( number_format( (float) $atts['amount'], 2 ) . ' ' . $atts['currency'] ); ?>
					</div>
				</div>
				<?php endif; ?>

				<input type="hidden" name="currency" value="<?php echo esc_attr( $atts['currency'] ); ?>" />
				<input type="hidden" name="description" value="<?php echo esc_attr( $atts['description'] ); ?>" />
				<input type="hidden" name="action" value="novac_initiate_payment" />
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'novac_payment' ) ); ?>" />

				<div class="novac-form-group">
					<button type="submit" class="novac-submit-btn">
						<?php echo esc_html( $atts['button_text'] ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle payment initiation AJAX request.
	 */
	public static function handle_payment_initiation() {

		check_ajax_referer( 'novac_payment', 'nonce' );

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$amount      = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
		$currency    = isset( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : 'NGN';
		$description = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : 'Payment';
        $phone       = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';

		if ( empty( $name ) || empty( $email ) || $amount <= 0 || empty( $currency) ) {
			wp_send_json_error( [ 'message' => __( 'Please fill all required fields: name, email, amount, and currency', 'novac' ) ] );
		}

        try {
            $result = Api_Client::initiate_checkout([
                    'name' => $name,
                    'email' => $email,
                    'amount' => $amount,
                    'currency' => $currency,
                    'tx_ref' => 'WP_' . uniqid('novac_', true),
                    'description' => $description,
                    'phone' => $phone,
            ]);
        } catch ( \Throwable $e ) {
            error_log('Api_Client error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Payment initiation failed: ' . $e->getMessage()]);
        }

        if ( is_wp_error( $result ) ) {
            $error_message = $result->get_error_message();
            $error_data = $result->get_error_data();

            // Check if there's an HTTP response body in the data.
            if ( isset( $error_data['body'] ) ) {
                $body = $error_data['body'];
            } else {
                $body = null;
            }

            wp_send_json_error([
                    'message' => $error_message,
                    'body'    => $body,
                    'data'    => $error_data,
            ]);
        }
		// Store transaction in database.
		$transaction_ref = $result['data']['transactionReference'] ?? '';
		if ( ! empty( $transaction_ref ) ) {
			Transactions::insert( [
				'transaction_ref' => $transaction_ref,
				'customer_email'  => $email,
				'customer_name'   => $name,
				'amount'          => $amount,
				'currency'        => $currency,
				'description'     => $description,
				'status'          => 'pending',
			] );
		}

		wp_send_json_success( [
			'checkout_url' => $result['data']['paymentRedirectUrl'] ?? '',
			'reference'    => $transaction_ref,
		] );
	}
}
