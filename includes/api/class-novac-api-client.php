<?php
/**
 * Novac API Client
 *
 * @package Novac
 */

namespace Novac\Novac\Api;

defined( 'ABSPATH' ) || exit;

class Api_Client {

	/**
	 * Base URL for Novac API.
	 */
	const API_BASE_URL = 'https://api.novacpayment.com/api/v1';

	/**
	 * Get plugin settings.
	 */
	private static function get_settings() {
		return get_option( 'novac_settings', [
			'public_key' => '',
			'secret_key' => '',
			'mode'       => 'test',
		] );
	}

	/**
	 * Get authorization header.
	 */
	private static function get_auth_header() {
		$settings = self::get_settings();
		return 'Bearer ' . $settings['secret_key'];
	}

	/**
	 * Initiate payment checkout.
	 *
	 * @param array $data Payment data.
	 * @return array|WP_Error
	 */
	public static function initiate_checkout( $data ) {
		$settings = self::get_settings();

		$body = [
			'publicKey'   => $settings['public_key'],
			'amount'      => $data['amount'],
			'currency'    => $data['currency'] ?? 'NGN',
			'customerEmail' => $data['email'],
			'customerName' => $data['name'] ?? '',
			'description' => $data['description'] ?? 'Payment',
			'callbackUrl' => $data['callback_url'] ?? home_url( '/?novac-callback=1' ),
		];

		if ( ! empty( $data['metadata'] ) ) {
			$body['metadata'] = $data['metadata'];
		}

		$response = wp_remote_post( self::API_BASE_URL . '/paymentlink/initiate', [
			'headers' => [
				'Authorization' => self::get_auth_header(),
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new \WP_Error( 'api_error', $data['message'] ?? 'Failed to initiate payment' );
		}

		return $data;
	}

	/**
	 * Verify transaction.
	 *
	 * @param string $transaction_ref Transaction reference.
	 * @return array|WP_Error
	 */
	public static function verify_transaction( $transaction_ref ) {
		$response = wp_remote_get( 
			self::API_BASE_URL . '/checkout/' . $transaction_ref . '/verify',
			[
				'headers' => [
					'Authorization' => self::get_auth_header(),
					'Content-Type'  => 'application/json',
				],
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new \WP_Error( 'api_error', $data['message'] ?? 'Failed to verify transaction' );
		}

		return $data;
	}
}
