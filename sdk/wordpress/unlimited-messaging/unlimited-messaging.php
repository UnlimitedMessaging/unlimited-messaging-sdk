<?php
/**
 * Plugin Name:       Unlimited Messaging: Order Notifications & Form Alerts
 * Plugin URI:        https://unlimitedmessaging.app/wordpress
 * Description:       Send WooCommerce order updates and form alerts by WhatsApp, SMS and email from your own number and mailbox. One account, no Twilio, no Meta templates.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Unlimited Messaging
 * Author URI:        https://unlimitedmessaging.app
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unlimited-messaging
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   11.3
 *
 * @package UnlimitedMessaging
 */

defined( 'ABSPATH' ) || exit;

define( 'UNLIMITED_MESSAGING_VERSION', '1.0.0' );
define( 'UNLIMITED_MESSAGING_FILE', __FILE__ );
define( 'UNLIMITED_MESSAGING_DIR', plugin_dir_path( __FILE__ ) );
define( 'UNLIMITED_MESSAGING_URL', plugin_dir_url( __FILE__ ) );

require_once UNLIMITED_MESSAGING_DIR . 'includes/class-settings.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-api.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-log.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-sender.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-templates.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-woocommerce.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-forms.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-admin.php';
require_once UNLIMITED_MESSAGING_DIR . 'includes/class-plugin.php';

// Declare compatibility with WooCommerce order storage (HPOS) and the
// block-based cart and checkout: the plugin only reads orders through the
// WC_Order API and never touches the checkout.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

\UnlimitedMessaging\Plugin::instance();
