<?php
/**
 * Transactions Database Handler
 *
 * @package Novac
 */

namespace Novac\Novac\Database;

defined( 'ABSPATH' ) || exit;

class Transactions {

	/**
	 * Table name.
	 */
	const TABLE_NAME = 'novac_transactions';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		register_activation_hook( NOVAC_PLUGIN_FILE, [ __CLASS__, 'create_table' ] );
	}

	/**
	 * Get table name with prefix.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create transactions table.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			transaction_ref varchar(255) NOT NULL,
			customer_email varchar(255) NOT NULL,
			customer_name varchar(255) DEFAULT NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(10) DEFAULT 'NGN',
			status varchar(50) DEFAULT 'pending',
			payment_method varchar(50) DEFAULT NULL,
			description text DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY transaction_ref (transaction_ref),
			KEY customer_email (customer_email),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert transaction.
	 *
	 * @param array $data Transaction data.
	 * @return int|false Transaction ID or false on failure.
	 */
	public static function insert( $data ) {
		global $wpdb;

		$defaults = [
			'transaction_ref' => '',
			'customer_email'  => '',
			'customer_name'   => '',
			'amount'          => 0,
			'currency'        => 'NGN',
			'status'          => 'pending',
			'payment_method'  => null,
			'description'     => null,
			'metadata'        => null,
		];

		$data = wp_parse_args( $data, $defaults );

		if ( is_array( $data['metadata'] ) ) {
			$data['metadata'] = wp_json_encode( $data['metadata'] );
		}

		$result = $wpdb->insert(
			self::get_table_name(),
			$data,
			[
				'%s', // transaction_ref
				'%s', // customer_email
				'%s', // customer_name
				'%f', // amount
				'%s', // currency
				'%s', // status
				'%s', // payment_method
				'%s', // description
				'%s', // metadata
			]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update transaction.
	 *
	 * @param string $transaction_ref Transaction reference.
	 * @param array  $data           Data to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update( $transaction_ref, $data ) {
		global $wpdb;

		if ( isset( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
			$data['metadata'] = wp_json_encode( $data['metadata'] );
		}

		$result = $wpdb->update(
			self::get_table_name(),
			$data,
			[ 'transaction_ref' => $transaction_ref ],
			null,
			[ '%s' ]
		);

		return $result !== false;
	}

	/**
	 * Get transaction by reference.
	 *
	 * @param string $transaction_ref Transaction reference.
	 * @return object|null Transaction object or null.
	 */
	public static function get_by_ref( $transaction_ref ) {
		global $wpdb;

		$table = self::get_table_name();
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE transaction_ref = %s",
				$transaction_ref
			)
		);

		if ( $transaction && ! empty( $transaction->metadata ) ) {
			$transaction->metadata = json_decode( $transaction->metadata, true );
		}

		return $transaction;
	}

	/**
	 * Get all transactions with pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array Array with 'items' and 'total'.
	 */
	public static function get_all( $args = [] ) {
		global $wpdb;

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'status'   => null,
			'search'   => null,
		];

		$args = wp_parse_args( $args, $defaults );

		$table  = self::get_table_name();
		$where  = '1=1';
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where   .= ' AND (customer_email LIKE %s OR customer_name LIKE %s OR transaction_ref LIKE %s)';
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
		}

		$orderby = in_array( $args['orderby'], [ 'created_at', 'amount', 'status' ], true ) 
			? $args['orderby'] 
			: 'created_at';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$limit  = $args['per_page'];

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM $table WHERE $where";
		$total       = $wpdb->get_var( 
			! empty( $params ) 
				? $wpdb->prepare( $count_query, ...$params ) 
				: $count_query 
		);

		// Get items.
		$items_query = "SELECT * FROM $table WHERE $where ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$params[]    = $limit;
		$params[]    = $offset;
		$items       = $wpdb->get_results( $wpdb->prepare( $items_query, ...$params ) );

		// Decode metadata.
		foreach ( $items as $item ) {
			if ( ! empty( $item->metadata ) ) {
				$item->metadata = json_decode( $item->metadata, true );
			}
		}

		return [
			'items' => $items,
			'total' => (int) $total,
		];
	}
}
