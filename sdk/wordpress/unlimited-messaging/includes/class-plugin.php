<?php
/**
 * Bootstrap.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every part once WordPress and the other plugins are loaded.
 */
class Plugin {

	/**
	 * Singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'boot' ) );
	}

	/**
	 * Registers hooks. Runs on `plugins_loaded` so WooCommerce and the form
	 * plugins are known.
	 */
	public function boot() {
		Sender::init();
		WooCommerce::init();
		Forms::init();
		if ( is_admin() ) {
			Admin::init();
		}
	}
}
