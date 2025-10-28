<?php
/**
 * Transactions Database Handler
 *
 * @package Novac
 */

namespace Novac\Novac\Database;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery

class Transactions {

    /**
     * Table name.
     */
    const TABLE_NAME = 'novac_transactions';

    /**
     * Initialize hooks.
     *
     * @return void
     */
    public static function init() {
        register_activation_hook( NOVAC_PLUGIN_FILE, [ __CLASS__, 'create_table' ] );
    }

    /**
     * Get full table name with prefix.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create the transactions table.
     *
     * @return void
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
     * Insert a new transaction record.
     *
     * @param array<string,mixed> $data Transaction data.
     * @return int|false Transaction ID on success, false on failure.
     */
    public static function insert( $data ) {
        global $wpdb;

        $defaults = [
            'transaction_ref' => '',
            'customer_email'  => '',
            'customer_name'   => '',
            'amount'          => 0.0,
            'currency'        => 'NGN',
            'status'          => 'pending',
            'payment_method'  => null,
            'description'     => null,
            'metadata'        => null,
        ];

        $data = wp_parse_args( $data, $defaults );

        $data['amount'] = (float) $data['amount'];

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

        if ( ! $result ) {
            return false;
        }

        // Clear cache for this transaction.
        if ( ! empty( $data['transaction_ref'] ) ) {
            wp_cache_delete( 'novac_txn_' . md5( $data['transaction_ref'] ), 'novac' );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update a transaction record.
     *
     * @param string               $transaction_ref Transaction reference.
     * @param array<string,mixed>  $data           Data to update.
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

        // Invalidate cache.
        wp_cache_delete( 'novac_txn_' . md5( $transaction_ref ), 'novac' );

        return $result !== false;
    }

    /**
     * Get a transaction by reference.
     *
     * @param string $transaction_ref Transaction reference.
     * @return object|null Transaction object or null.
     */
    public static function get_by_ref( $transaction_ref ) {
        global $wpdb;

        $cache_key = 'novac_txn_' . md5( $transaction_ref );
        $cached    = wp_cache_get( $cache_key, 'novac' );

        if ( false !== $cached ) {
            return $cached;
        }

        $table = esc_sql( self::get_table_name() );

        $transaction = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE transaction_ref = %s",
                $transaction_ref
            )
        );

        if ( $transaction && ! empty( $transaction->metadata ) ) {
            $transaction->metadata = json_decode( $transaction->metadata, true );
        }

        wp_cache_set( $cache_key, $transaction, 'novac', MINUTE_IN_SECONDS * 10 );

        return $transaction;
    }

    /**
     * Get paginated transactions.
     *
     * @param array<string,mixed> $args Query arguments.
     * @return array{items:array<object>,total:int}
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

        $table  = esc_sql( self::get_table_name() );
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

        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ( (int) $args['page'] - 1 ) * (int) $args['per_page'];
        $limit  = (int) $args['per_page'];

        $count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        $total       = $params
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$params ) )
            : (int) $wpdb->get_var( $count_query );

        $items_query   = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $items_params  = array_merge( $params, [ $limit, $offset ] );
        $items_results = $wpdb->get_results( $wpdb->prepare( $items_query, ...$items_params ) );

        foreach ( $items_results as $item ) {
            if ( ! empty( $item->metadata ) ) {
                $item->metadata = json_decode( $item->metadata, true );
            }
        }

        return [
            'items' => $items_results,
            'total' => $total,
        ];
    }
}
