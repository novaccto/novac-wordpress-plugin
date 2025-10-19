<?php
/**
 * Novac API Client
 *
 * @package Novac
 */

namespace Novac\Novac\Api;

defined( 'ABSPATH' ) || exit;

use Novac\Novac\Logger\Logger;

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
			'webhook_url'=> home_url( '/?novac-webhook=1' ),
			'logo'       => ''
		] );
	}

	// function to get site logo.
	public static function get_site_logo() {
		$settings = self::get_settings();
		return wp_get_attachment_url( $settings['logo'] );
	}

	/**
	 * Get authorization header.
	 */
	private static function get_auth_header() {
		$settings = self::get_settings();
		return 'Bearer ' . $settings['public_key'];
	}

	/**
	 * Initiate payment checkout.
	 *
	 * @param array $data Payment data.
	 * @return array|WP_Error
	 */
	public static function initiate_checkout( $data ) {
		$settings = self::get_settings();

		Logger::instance()->info( 'Initiate checkout',  $data );

		$full_name = $data['name'];
		$names = explode( ' ', $full_name );
		$first_name = $names[0];
		$last_name = $names[1] ?? '';

		// TODO: store data in database.

		$body = [
			'transactionReference' => $data['tx_ref'],
			'amount'      => $data['amount'],
			'currency'    => $data['currency'] ?? 'NGN',
			'checkoutCustomerData' => [
				'email' => $data['email'],
				'firstName' => $first_name ?? '',
				'lastName' => $last_name,
				'phoneNumber' => $data['phone'] ?? ''
			],
			'checkoutCustomizationData' => [
				'logoUrl' => get_site_icon_url() ?? home_url( '/favicon.ico' ),
				'paymentMethodLogoUrl' => '',
				'checkoutModalTitle' => $data['description'],
			]
		];

		if ( ! empty( $data['metadata'] ) ) {
			$body['metadata'] = $data['metadata'];
		}

		$response = wp_remote_post( self::API_BASE_URL . '/initiate', [
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

		Logger::instance()->info( 'Response from Novac: ',  $data );

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
		$settings = self::get_settings();
		$response = wp_remote_get( 
			self::API_BASE_URL . '/checkout/' . $transaction_ref . '/verify',
			[
				'headers' => [
					'Authorization' => $settings['secret_key'],
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
