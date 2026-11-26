<?php
/**
 * Short activity log shown in the settings page.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the last entries in a non-autoloaded option. Recipients are masked:
 * the full history lives in the Unlimited Messaging dashboard, this is only
 * there to answer "did it go out, and if not, why".
 */
class Log {

	const OPTION = 'unlimited_messaging_log';
	const MAX    = 100;

	/**
	 * Adds an entry.
	 *
	 * @param string   $source   What triggered it ("Order #123 - processing").
	 * @param string[] $channels Channels.
	 * @param string   $to       Recipient(s), masked before storage.
	 * @param bool     $ok       Whether the API accepted it.
	 * @param string   $detail   Error message or dispatch id.
	 */
	public static function add( $source, array $channels, $to, $ok, $detail = '' ) {
		$entries = self::all();
		array_unshift(
			$entries,
			array(
				'time'     => time(),
				'source'   => (string) $source,
				'channels' => array_values( $channels ),
				'to'       => self::mask( $to ),
				'ok'       => (bool) $ok,
				'detail'   => (string) $detail,
			)
		);
		update_option( self::OPTION, array_slice( $entries, 0, self::MAX ), false );
	}

	/**
	 * Entries, newest first.
	 *
	 * @return array
	 */
	public static function all() {
		$entries = get_option( self::OPTION, array() );
		return is_array( $entries ) ? $entries : array();
	}

	/**
	 * Empties the log.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}

	/**
	 * Masks phone numbers and email addresses: +33612345678 -> +336****678,
	 * jane.doe@example.com -> ja***@example.com.
	 *
	 * @param string $to Recipient(s), comma separated.
	 * @return string
	 */
	public static function mask( $to ) {
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $to ) ) );
		$out   = array();
		foreach ( $parts as $part ) {
			if ( false !== strpos( $part, '@' ) ) {
				list( $local, $domain ) = explode( '@', $part, 2 );
				$out[]                  = substr( $local, 0, 2 ) . '***@' . $domain;
			} elseif ( strlen( $part ) > 7 ) {
				$out[] = substr( $part, 0, 4 ) . str_repeat( '*', strlen( $part ) - 7 ) . substr( $part, -3 );
			} else {
				$out[] = $part;
			}
		}
		return implode( ', ', $out );
	}
}
