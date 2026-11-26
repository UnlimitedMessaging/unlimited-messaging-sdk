<?php
/**
 * Plugin settings: one option array, with defaults.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the `unlimited_messaging_settings` option.
 */
class Settings {

	const OPTION = 'unlimited_messaging_settings';

	/** Channels as the Unlimited Messaging API names them. */
	const CHANNELS = array( 'WHATSAPP', 'SMS', 'EMAIL' );

	/** Who a notification goes to. */
	const AUDIENCES = array( 'customer', 'admin' );

	/**
	 * Cached option value for the current request.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * WooCommerce events the plugin can notify on, with the audiences each one
	 * makes sense for. `new_order` is the shop owner's alert; the status events
	 * mirror WooCommerce's own customer emails.
	 *
	 * @return array<string, array{label: string, audiences: string[]}>
	 */
	public static function wc_events() {
		return array(
			'new_order'     => array(
				'label'     => __( 'New order', 'unlimited-messaging' ),
				'audiences' => array( 'admin' ),
			),
			'processing'    => array(
				'label'     => __( 'Order processing (paid)', 'unlimited-messaging' ),
				'audiences' => array( 'customer' ),
			),
			'on-hold'       => array(
				'label'     => __( 'Order on hold (awaiting payment)', 'unlimited-messaging' ),
				'audiences' => array( 'customer' ),
			),
			'completed'     => array(
				'label'     => __( 'Order completed (shipped)', 'unlimited-messaging' ),
				'audiences' => array( 'customer' ),
			),
			'cancelled'     => array(
				'label'     => __( 'Order cancelled', 'unlimited-messaging' ),
				'audiences' => array( 'customer', 'admin' ),
			),
			'refunded'      => array(
				'label'     => __( 'Order refunded', 'unlimited-messaging' ),
				'audiences' => array( 'customer' ),
			),
			'failed'        => array(
				'label'     => __( 'Payment failed', 'unlimited-messaging' ),
				'audiences' => array( 'customer', 'admin' ),
			),
			'customer_note' => array(
				'label'     => __( 'Note to customer added', 'unlimited-messaging' ),
				'audiences' => array( 'customer' ),
			),
		);
	}

	/**
	 * Default message for an event and audience. Translated at render time, so
	 * a shop that never edits a template gets it in the site language.
	 *
	 * @param string $event    Event key.
	 * @param string $audience customer|admin.
	 * @return string
	 */
	public static function default_text( $event, $audience ) {
		$texts = array(
			'new_order|admin'        => __( 'New order #{order_number} on {site_name}: {order_total} from {customer_full_name} ({order_items}). {admin_order_url}', 'unlimited-messaging' ),
			'processing|customer'    => __( 'Hi {customer_first_name}, thank you for your order #{order_number} on {site_name}! We are preparing it. Total: {order_total}. Details: {order_url}', 'unlimited-messaging' ),
			'on-hold|customer'       => __( 'Hi {customer_first_name}, we received your order #{order_number} on {site_name}. It will be prepared as soon as your payment is confirmed. Details: {order_url}', 'unlimited-messaging' ),
			'completed|customer'     => __( 'Good news {customer_first_name}: your order #{order_number} from {site_name} is on its way! Details: {order_url}', 'unlimited-messaging' ),
			'cancelled|customer'     => __( 'Hi {customer_first_name}, your order #{order_number} on {site_name} has been cancelled. Reply to this message if you have any question.', 'unlimited-messaging' ),
			'cancelled|admin'        => __( 'Order #{order_number} ({order_total}) from {customer_full_name} was cancelled. {admin_order_url}', 'unlimited-messaging' ),
			'refunded|customer'      => __( 'Hi {customer_first_name}, your order #{order_number} on {site_name} has been refunded. The money should reach your account within a few days.', 'unlimited-messaging' ),
			'failed|customer'        => __( 'Hi {customer_first_name}, the payment for your order #{order_number} on {site_name} did not go through. You can try again here: {payment_url}', 'unlimited-messaging' ),
			'failed|admin'           => __( 'Payment failed for order #{order_number} ({order_total}) from {customer_full_name}. {admin_order_url}', 'unlimited-messaging' ),
			'customer_note|customer' => __( 'Hi {customer_first_name}, a note about your order #{order_number}: {customer_note}', 'unlimited-messaging' ),
		);
		$key = $event . '|' . $audience;
		return isset( $texts[ $key ] ) ? $texts[ $key ] : '';
	}

	/**
	 * Default email subject for an event.
	 *
	 * @return string
	 */
	public static function default_subject() {
		return __( '{site_name}: order #{order_number}', 'unlimited-messaging' );
	}

	/**
	 * Default form alert.
	 *
	 * @return string
	 */
	public static function default_form_text() {
		return __( "New message from the form \"{form_name}\" on {site_name}:\n\n{fields}", 'unlimited-messaging' );
	}

	/**
	 * Default form alert subject.
	 *
	 * @return string
	 */
	public static function default_form_subject() {
		return __( '{site_name}: new "{form_name}" submission', 'unlimited-messaging' );
	}

	/**
	 * Settings a fresh install starts with. Only the two notifications every
	 * shop wants are on, on WhatsApp: the owner hears about each new order and
	 * the customer gets a thank-you once it is paid.
	 *
	 * @return array
	 */
	public static function defaults() {
		$wc = array();
		foreach ( self::wc_events() as $event => $def ) {
			foreach ( $def['audiences'] as $audience ) {
				$wc[ $event ][ $audience ] = array(
					'enabled'  => ( 'new_order' === $event || 'processing' === $event ),
					'channels' => array( 'WHATSAPP' ),
					'text'     => '',
					'subject'  => '',
				);
			}
		}

		return array(
			'api_key'      => '',
			'accounts'     => array(
				'WHATSAPP' => '',
				'SMS'      => '',
				'EMAIL'    => '',
			),
			'admin_phone'  => '',
			'admin_email'  => '',
			'hourly_limit' => 60,
			'wc'           => $wc,
			'forms'        => array(
				'enabled'  => false,
				'channels' => array( 'WHATSAPP' ),
				'text'     => '',
				'subject'  => '',
			),
		);
	}

	/**
	 * Full settings, stored values merged over the defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = self::merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}
		return self::$cache;
	}

	/**
	 * One top-level setting.
	 *
	 * @param string $key Setting name.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Saves the full settings array.
	 *
	 * @param array $settings Sanitized settings.
	 */
	public static function save( array $settings ) {
		update_option( self::OPTION, $settings, false );
		self::$cache = null;
	}

	/**
	 * Settings of one WooCommerce notification.
	 *
	 * @param string $event    Event key.
	 * @param string $audience customer|admin.
	 * @return array|null
	 */
	public static function wc_notification( $event, $audience ) {
		$wc = self::get( 'wc' );
		return isset( $wc[ $event ][ $audience ] ) ? $wc[ $event ][ $audience ] : null;
	}

	/**
	 * Recursive merge where stored scalars and lists replace defaults.
	 *
	 * @param array $defaults Defaults.
	 * @param array $stored   Stored values.
	 * @return array
	 */
	private static function merge( array $defaults, array $stored ) {
		foreach ( $stored as $key => $value ) {
			if ( isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) && is_array( $value ) && ! wp_is_numeric_array( $defaults[ $key ] ) ) {
				$defaults[ $key ] = self::merge( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}
		return $defaults;
	}
}
