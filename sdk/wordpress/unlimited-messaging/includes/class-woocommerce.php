<?php
/**
 * WooCommerce order notifications.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks order status changes and customer notes.
 */
class WooCommerce {

	/**
	 * Status transitions that make an order "new" for the shop owner, the
	 * same ones WooCommerce's own "New order" email listens to.
	 */
	const NEW_ORDER_FROM = array( 'pending', 'failed', 'cancelled', 'checkout-draft' );
	const NEW_ORDER_TO   = array( 'processing', 'completed', 'on-hold' );

	/**
	 * Registers hooks when WooCommerce is active.
	 */
	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_changed' ), 20, 4 );
		add_action( 'woocommerce_new_customer_note', array( __CLASS__, 'on_customer_note' ), 20, 1 );
	}

	/**
	 * Order status changed.
	 *
	 * @param int       $order_id Order id.
	 * @param string    $from     Previous status, without "wc-".
	 * @param string    $to       New status, without "wc-".
	 * @param \WC_Order $order    Order.
	 */
	public static function on_status_changed( $order_id, $from, $to, $order = null ) {
		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		if ( in_array( $from, self::NEW_ORDER_FROM, true ) && in_array( $to, self::NEW_ORDER_TO, true ) ) {
			self::notify( $order, 'new_order', 'admin' );
		}

		$events = Settings::wc_events();
		if ( isset( $events[ $to ] ) && 'new_order' !== $to ) {
			foreach ( $events[ $to ]['audiences'] as $audience ) {
				self::notify( $order, $to, $audience );
			}
		}
	}

	/**
	 * A note to the customer was added from the order screen.
	 *
	 * @param array $args `{order_id, customer_note}`.
	 */
	public static function on_customer_note( $args ) {
		if ( empty( $args['order_id'] ) ) {
			return;
		}
		$order = wc_get_order( $args['order_id'] );
		if ( ! $order ) {
			return;
		}
		$note = isset( $args['customer_note'] ) ? wp_strip_all_tags( $args['customer_note'] ) : '';
		// Notes are not one-off events: every note gets its own message.
		self::notify( $order, 'customer_note', 'customer', array( 'customer_note' => $note ), false );
	}

	/**
	 * Sends one notification if it is enabled and was not already sent.
	 *
	 * @param \WC_Order $order    Order.
	 * @param string    $event    Event key.
	 * @param string    $audience customer|admin.
	 * @param array     $extra    Extra placeholder values.
	 * @param bool      $once     Send at most once per order.
	 */
	public static function notify( \WC_Order $order, $event, $audience, array $extra = array(), $once = true ) {
		$config = Settings::wc_notification( $event, $audience );
		if ( ! $config || empty( $config['enabled'] ) || empty( $config['channels'] ) ) {
			return;
		}

		// An order bouncing between statuses (on-hold, processing, on-hold...)
		// must not message the customer each time.
		$meta_key = '_unlimited_messaging_sent_' . $event . '_' . $audience;
		if ( $once && $order->get_meta( $meta_key ) ) {
			return;
		}

		if ( 'admin' === $audience ) {
			$phone = Templates::to_e164( Settings::get( 'admin_phone' ) );
			$email = Settings::get( 'admin_email' );
			if ( '' === $email ) {
				$email = get_option( 'admin_email' );
			}
		} else {
			$phone = Templates::order_phone( $order );
			$email = $order->get_billing_email();
		}

		$vars    = Templates::order_vars( $order, $extra );
		$text    = '' !== trim( $config['text'] ) ? $config['text'] : Settings::default_text( $event, $audience );
		$subject = '' !== trim( $config['subject'] ) ? $config['subject'] : Settings::default_subject();

		/**
		 * Filters the text of an order notification before it is sent.
		 *
		 * @param string    $text     Rendered text.
		 * @param \WC_Order $order    Order.
		 * @param string    $event    Event key (new_order, processing...).
		 * @param string    $audience customer|admin.
		 */
		$text = apply_filters( 'unlimited_messaging_order_text', Templates::render( $text, $vars ), $order, $event, $audience );

		$payload = Sender::build_payload( $config['channels'], $phone, $email, $text, Templates::render( $subject, $vars ) );
		if ( ! $payload ) {
			return;
		}

		if ( $once ) {
			$order->update_meta_data( $meta_key, time() );
			$order->save_meta_data();
		}

		$events = Settings::wc_events();
		$source = sprintf(
			/* translators: 1: order number, 2: event name, 3: customer or shop owner. */
			__( 'Order #%1$s - %2$s (%3$s)', 'unlimited-messaging' ),
			$order->get_order_number(),
			isset( $events[ $event ] ) ? $events[ $event ]['label'] : $event,
			'admin' === $audience ? __( 'you', 'unlimited-messaging' ) : __( 'customer', 'unlimited-messaging' )
		);
		Sender::queue( $payload, $source );
	}
}
