<?php
/**
 * Gutenberg Blocks
 *
 * @package Novac
 */

namespace Novac\Novac\Blocks;

defined( 'ABSPATH' ) || exit;

class Payment_Block {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_block' ] );
	}

	/**
	 * Register payment form block.
	 */
	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'novac-payment-block',
			plugins_url( 'blocks/build/index.js', NOVAC_PLUGIN_FILE ),
			[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ],
			'0.0.1',
			true
		);

		register_block_type( 'novac/payment-form', [
			'editor_script'   => 'novac-payment-block',
			'render_callback' => [ __CLASS__, 'render_block' ],
			'attributes'      => [
				'amount'      => [
					'type'    => 'string',
					'default' => '',
				],
				'currency'    => [
					'type'    => 'string',
					'default' => 'NGN',
				],
				'description' => [
					'type'    => 'string',
					'default' => 'Payment',
				],
				'buttonText'  => [
					'type'    => 'string',
					'default' => 'Pay Now',
				],
			],
		] );
	}

	/**
	 * Render block on frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $attributes ) {
		$shortcode_atts = [
			'amount'      => $attributes['amount'] ?? '',
			'currency'    => $attributes['currency'] ?? 'NGN',
			'description' => $attributes['description'] ?? 'Payment',
			'button_text' => $attributes['buttonText'] ?? 'Pay Now',
		];

		return do_shortcode( '[novac_payment_form ' . self::build_shortcode_atts( $shortcode_atts ) . ']' );
	}

	/**
	 * Build shortcode attributes string.
	 *
	 * @param array $atts Attributes array.
	 * @return string
	 */
	private static function build_shortcode_atts( $atts ) {
		$parts = [];
		foreach ( $atts as $key => $value ) {
			if ( ! empty( $value ) ) {
				$parts[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
			}
		}
		return implode( ' ', $parts );
	}
}
