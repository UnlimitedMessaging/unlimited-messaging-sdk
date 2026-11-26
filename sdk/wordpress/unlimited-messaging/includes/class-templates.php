<?php
/**
 * Placeholder rendering and phone number formatting.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Turns `{order_number}`-style templates into message text.
 */
class Templates {

	/**
	 * Placeholders available in order templates, for the help text.
	 *
	 * @return string[]
	 */
	public static function order_placeholders() {
		return array(
			'{customer_first_name}',
			'{customer_last_name}',
			'{customer_full_name}',
			'{customer_phone}',
			'{customer_email}',
			'{order_number}',
			'{order_total}',
			'{order_status}',
			'{order_date}',
			'{order_items}',
			'{payment_method}',
			'{shipping_method}',
			'{shipping_address}',
			'{order_url}',
			'{payment_url}',
			'{admin_order_url}',
			'{customer_note}',
			'{site_name}',
			'{site_url}',
		);
	}

	/**
	 * Replaces `{name}` placeholders. Unknown ones are left as typed so a typo
	 * shows up in the message instead of vanishing.
	 *
	 * @param string $template Template.
	 * @param array  $vars     name => value, without braces.
	 * @return string
	 */
	public static function render( $template, array $vars ) {
		$vars = array_merge(
			array(
				'site_name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'site_url'  => home_url( '/' ),
			),
			$vars
		);
		$replace = array();
		foreach ( $vars as $name => $value ) {
			$replace[ '{' . $name . '}' ] = (string) $value;
		}
		return trim( strtr( (string) $template, $replace ) );
	}

	/**
	 * Placeholder values for an order.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $extra Extra values (customer_note...).
	 * @return array
	 */
	public static function order_vars( \WC_Order $order, array $extra = array() ) {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = $item->get_quantity() . ' x ' . $item->get_name();
		}

		$address = $order->has_shipping_address()
			? $order->get_formatted_shipping_address()
			: $order->get_formatted_billing_address();

		$date = $order->get_date_created();

		return array_merge(
			array(
				'customer_first_name' => $order->get_billing_first_name(),
				'customer_last_name'  => $order->get_billing_last_name(),
				'customer_full_name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'customer_phone'      => self::order_phone( $order ),
				'customer_email'      => $order->get_billing_email(),
				'order_number'        => $order->get_order_number(),
				'order_total'         => self::plain( $order->get_formatted_order_total() ),
				'order_status'        => wc_get_order_status_name( $order->get_status() ),
				'order_date'          => $date ? wc_format_datetime( $date ) : '',
				'order_items'         => implode( ', ', $items ),
				'payment_method'      => $order->get_payment_method_title(),
				'shipping_method'     => $order->get_shipping_method(),
				'shipping_address'    => self::plain( str_replace( array( '<br/>', '<br />', '<br>' ), ', ', (string) $address ) ),
				'order_url'           => $order->get_view_order_url(),
				'payment_url'         => $order->get_checkout_payment_url(),
				'admin_order_url'     => $order->get_edit_order_url(),
				'customer_note'       => '',
			),
			$extra
		);
	}

	/**
	 * The customer's phone in E.164, from the billing phone (or the shipping
	 * phone) and the billing country. Empty when there is none.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function order_phone( \WC_Order $order ) {
		$phone = $order->get_billing_phone();
		if ( '' === trim( (string) $phone ) && method_exists( $order, 'get_shipping_phone' ) ) {
			$phone = $order->get_shipping_phone();
		}
		$country = $order->get_billing_country() ? $order->get_billing_country() : $order->get_shipping_country();
		return self::to_e164( $phone, $country );
	}

	/**
	 * Normalizes a phone number to E.164.
	 *
	 * "06 12 34 56 78" + FR -> "+33612345678"; "0033 6..." -> "+336...";
	 * "+44 7700 900123" stays as is. Without a country, a number that is not
	 * already international falls back to the store's country.
	 *
	 * @param string $phone   Phone as typed.
	 * @param string $country ISO 3166-1 alpha-2 country, may be empty.
	 * @return string
	 */
	public static function to_e164( $phone, $country = '' ) {
		$phone = trim( (string) $phone );
		if ( '' === $phone ) {
			return '';
		}
		$international = 0 === strpos( $phone, '+' );
		$digits        = preg_replace( '/\D+/', '', $phone );
		if ( '' === $digits ) {
			return '';
		}
		if ( $international ) {
			return '+' . $digits;
		}
		if ( 0 === strpos( $digits, '00' ) ) {
			return '+' . substr( $digits, 2 );
		}

		$calling_code = self::calling_code( $country );
		if ( '' === $calling_code ) {
			// Nothing to anchor it to: assume it already carries its country code.
			return '+' . ltrim( $digits, '0' );
		}
		// A national number drops its trunk prefix "0" (FR, UK, DE...). Italy
		// keeps it.
		if ( '39' !== $calling_code ) {
			$digits = ltrim( $digits, '0' );
		}
		if ( 0 === strpos( $digits, $calling_code ) && strlen( $digits ) > 10 ) {
			return '+' . $digits;
		}
		return '+' . $calling_code . $digits;
	}

	/**
	 * Calling code without "+", from WooCommerce's table, falling back to the
	 * store's base country.
	 *
	 * @param string $country ISO country.
	 * @return string
	 */
	private static function calling_code( $country ) {
		if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
			return '';
		}
		if ( '' === (string) $country ) {
			$country = WC()->countries->get_base_country();
		}
		$code = WC()->countries->get_country_calling_code( $country );
		if ( is_array( $code ) ) {
			$code = reset( $code );
		}
		return ltrim( (string) $code, '+' );
	}

	/**
	 * HTML (prices, addresses) to plain text.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function plain( $html ) {
		return trim( html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' ) );
	}
}
