<?php
/**
 * Novac Logger Class
 *
 * @package Novac
 */

declare(strict_types=1);

namespace Novac\Novac\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Logger class for Novac plugin
 */
class Logger {

	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	private static $instance = null;
	private $log_file;
	private $enabled;
	private $min_level;
	private $levels = array(
		self::EMERGENCY => 0,
		self::ALERT     => 1,
		self::CRITICAL  => 2,
		self::ERROR     => 3,
		self::WARNING   => 4,
		self::NOTICE    => 5,
		self::INFO      => 6,
		self::DEBUG     => 7,
	);

	private function __construct() {
		$upload_dir     = wp_upload_dir();
		$log_dir        = $upload_dir['basedir'] . '/novac-logs';
		$this->log_file = $log_dir . '/novac-' . gmdate( 'Y-m-d' ) . '.log';
		$this->enabled  = get_option( 'novac_enable_logging', true );
		$this->min_level = get_option( 'novac_log_level', self::INFO );

		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
			file_put_contents( $log_dir . '/.htaccess', "Order deny,allow\nDeny from all" );
		}
	}

	public static function instance(): Logger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function should_log( string $level ): bool {
		if ( ! $this->enabled ) {
			return false;
		}
		if ( ! isset( $this->levels[ $level ] ) || ! isset( $this->levels[ $this->min_level ] ) ) {
			return false;
		}
		return $this->levels[ $level ] <= $this->levels[ $this->min_level ];
	}

	public function log( string $level, string $message, array $context = array() ): bool {
		if ( ! $this->should_log( $level ) ) {
			return false;
		}

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$context_str = ! empty( $context ) ? ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
		$log_entry = sprintf( "[%s] [%s] %s%s\n", $timestamp, strtoupper( $level ), $message, $context_str );

		return (bool) error_log( $log_entry, 3, $this->log_file );
	}

	public function emergency( string $message, array $context = array() ): bool {
		return $this->log( self::EMERGENCY, $message, $context );
	}

	public function alert( string $message, array $context = array() ): bool {
		return $this->log( self::ALERT, $message, $context );
	}

	public function critical( string $message, array $context = array() ): bool {
		return $this->log( self::CRITICAL, $message, $context );
	}

	public function error( string $message, array $context = array() ): bool {
		return $this->log( self::ERROR, $message, $context );
	}

	public function warning( string $message, array $context = array() ): bool {
		return $this->log( self::WARNING, $message, $context );
	}

	public function notice( string $message, array $context = array() ): bool {
		return $this->log( self::NOTICE, $message, $context );
	}

	public function info( string $message, array $context = array() ): bool {
		return $this->log( self::INFO, $message, $context );
	}

	public function debug( string $message, array $context = array() ): bool {
		return $this->log( self::DEBUG, $message, $context );
	}

	public function get_log_file(): string {
		return $this->log_file;
	}

	public function clear_old_logs( int $days = 30 ): int {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/novac-logs';
		$count      = 0;

		if ( ! is_dir( $log_dir ) ) {
			return 0;
		}

		$files = glob( $log_dir . '/novac-*.log' );
		$cutoff_time = time() - ( $days * DAY_IN_SECONDS );

		foreach ( $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $cutoff_time && unlink( $file ) ) {
				$count++;
			}
		}

		return $count;
	}
}