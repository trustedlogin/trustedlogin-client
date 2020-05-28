<?php

namespace TrustedLogin;

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

use \Exception;
use \WP_Error;
use \WP_User;
use \WP_Admin_Bar;

final class Button {

	/**
	 * @var \TrustedLogin\Config
	 */
	private $config;

	/**
	 * @var string
	 */
	private $hook_name;

	/**
	 * @var null|\TrustedLogin\Logging $logging
	 */
	private $logging;

	/**
	 * Cron constructor.
	 *
	 * @param Config $config
	 * @param Logging|null $logging
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config  = $config;
		$this->logging = $logging;

		$this->hook_name = 'trustedlogin/' . $this->config->ns() . '/access/revoke';
	}

	/**
	 *
	 */
	public function init() {
		add_action( $this->hook_name, array( $this, 'cron_revoke_access' ) );
	}

	/**
	 * @param int $expiration_timestamp
	 * @param string $identifier_hash
	 *
	 * @return bool
	 */
	public function schedule( $expiration_timestamp, $identifier_hash ) {

		$hash = Encryption::hash( $identifier_hash );

		if ( is_wp_error( $hash ) ) {
			$this->logging->log( $hash, __METHOD__ );

			return false;
		}

		$args = array( $hash );

		$scheduled_expiration = wp_schedule_single_event( $expiration_timestamp, $this->hook_name, $args );

		$this->logging->log( 'Scheduled Expiration: ' . var_export( $scheduled_expiration, true ) . '; identifier: ' . $identifier_hash, __METHOD__, 'info' );

		return $scheduled_expiration;
	}

	/**
	 * Hooked Action: Revokes access for a specific support user
	 *
	 * @since 0.2.1
	 *
	 * @param string $identifier_hash Identifier hash for the user associated with the cron job
	 * @todo
	 * @return void
	 */
	public function revoke( $identifier_hash ) {

		$this->logging->log( 'Running cron job to disable user. ID: ' . $identifier_hash, __METHOD__, 'notice' );

		$this->support_user->delete( $identifier_hash );

		$this->endpoint->delete();
	}
}
