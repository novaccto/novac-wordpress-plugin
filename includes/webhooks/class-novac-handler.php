<?php
/**
 * Webhook Handler
 *
 * @package Novac
 */

namespace Novac\Novac\Webhooks;

use Novac\Novac\Database\Transactions;
use Novac\Novac\Api\Api_Client;

defined( 'ABSPATH' ) || exit;

class Handler {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'handle_webhook' ] );
		add_action( 'init', [ __CLASS__, 'handle_callback' ] );
	}

	/**
	 * Handle webhook from Novac.
	 */
	public static function handle_webhook() {
		if ( ! isset( $_GET['novac-webhook'] ) ) {
			return;
		}

		$raw_body = file_get_contents( 'php://input' );
		$data     = json_decode( $raw_body, true );

		if ( empty( $data['transactionRef'] ) ) {
			wp_send_json_error( [ 'message' => 'Invalid webhook data' ], 400 );
		}

		$transaction_ref = sanitize_text_field( $data['transactionRef'] );

		// Verify transaction with Novac API.
		$verification = Api_Client::verify_transaction( $transaction_ref );

		if ( is_wp_error( $verification ) ) {
			wp_send_json_error( [ 'message' => $verification->get_error_message() ], 400 );
		}

		// Update transaction in database.
		$transaction = Transactions::get_by_ref( $transaction_ref );

		if ( ! $transaction ) {
			// Create new transaction if it doesn't exist.
			Transactions::insert( [
				'transaction_ref' => $transaction_ref,
				'customer_email'  => $verification['data']['customerEmail'] ?? '',
				'customer_name'   => $verification['data']['customerName'] ?? '',
				'amount'          => $verification['data']['amount'] ?? 0,
				'currency'        => $verification['data']['currency'] ?? 'NGN',
				'status'          => $verification['data']['status'] ?? 'pending',
				'payment_method'  => $verification['data']['paymentMethod'] ?? null,
				'metadata'        => $verification['data']['metadata'] ?? null,
			] );
		} else {
			// Update existing transaction.
			Transactions::update( $transaction_ref, [
				'status'         => $verification['data']['status'] ?? 'pending',
				'payment_method' => $verification['data']['paymentMethod'] ?? null,
			] );
		}

		do_action( 'novac_webhook_received', $verification, $transaction_ref );

		wp_send_json_success( [ 'message' => 'Webhook processed' ] );
	}

	/**
	 * Handle payment callback redirect.
	 */
	public static function handle_callback() {
		if ( ! isset( $_GET['novac-callback'] ) ) {
			return;
		}

		$transaction_ref = isset( $_GET['reference'] ) ? sanitize_text_field( $_GET['reference'] ) : '';

		if ( empty( $transaction_ref ) ) {
			wp_die( esc_html__( 'Invalid payment reference', 'novac' ) );
		}

		// Verify transaction.
		$verification = Api_Client::verify_transaction( $transaction_ref );

		if ( is_wp_error( $verification ) ) {
			wp_die( esc_html( $verification->get_error_message() ) );
		}

		// Update transaction.
		$transaction = Transactions::get_by_ref( $transaction_ref );

		if ( $transaction ) {
			Transactions::update( $transaction_ref, [
				'status'         => $verification['data']['status'] ?? 'pending',
				'payment_method' => $verification['data']['paymentMethod'] ?? null,
			] );
		}

		do_action( 'novac_payment_callback', $verification, $transaction_ref );

		// Redirect to success/failure page based on status.
		$status       = $verification['data']['status'] ?? 'pending';
		$redirect_url = home_url( '/' );

		if ( isset( $transaction->metadata['redirect_url'] ) ) {
			$redirect_url = $transaction->metadata['redirect_url'];
		}

		$redirect_url = add_query_arg( [
			'novac-payment' => $status,
			'reference'     => $transaction_ref,
		], $redirect_url );

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
