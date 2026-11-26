<?php
/**
 * Unlimited Messaging REST API client.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper over the two endpoints the plugin needs: list accounts, send.
 */
class Api {

	const BASE_URL = 'https://api.unlimitedmessaging.app';

	/**
	 * API key (`ak_...`).
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = trim( (string) $api_key );
	}

	/**
	 * Base URL, overridable for local development with the
	 * `UNLIMITED_MESSAGING_API_URL` constant.
	 *
	 * @return string
	 */
	public static function base_url() {
		if ( defined( 'UNLIMITED_MESSAGING_API_URL' ) && UNLIMITED_MESSAGING_API_URL ) {
			return untrailingslashit( UNLIMITED_MESSAGING_API_URL );
		}
		return self::BASE_URL;
	}

	/**
	 * Active messaging accounts, with the channel normalized to
	 * WHATSAPP / SMS / EMAIL.
	 *
	 * @return array|\WP_Error List of `{id, channel, label, status}`.
	 */
	public function accounts() {
		$response = $this->request( 'GET', '/account' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$accounts = array();
		foreach ( (array) $response as $account ) {
			if ( ! is_array( $account ) || empty( $account['id'] ) || empty( $account['channel'] ) ) {
				continue;
			}
			$channel = self::normalize_channel( $account['channel'] );
			if ( ! $channel ) {
				continue;
			}
			$label = '';
			if ( ! empty( $account['name'] ) ) {
				$label = $account['name'];
			}
			if ( ! empty( $account['phone'] ) ) {
				$label = $label ? $label . ' (' . $account['phone'] . ')' : $account['phone'];
			}
			$accounts[] = array(
				'id'      => (string) $account['id'],
				'channel' => $channel,
				'label'   => $label ? $label : (string) $account['id'],
				'status'  => isset( $account['status'] ) ? (string) $account['status'] : '',
			);
		}
		return $accounts;
	}

	/**
	 * Sends one message on one or several channels.
	 *
	 * @param array $payload Body of `POST /message`.
	 * @return array|\WP_Error Decoded response.
	 */
	public function send( array $payload ) {
		return $this->request( 'POST', '/message', $payload );
	}

	/**
	 * Maps an account channel to the channel name `POST /message` takes.
	 *
	 * @param string $channel Account channel (WHATSAPP_WHATSMEOW, EMAIL, SMS...).
	 * @return string|null
	 */
	public static function normalize_channel( $channel ) {
		$channel = strtoupper( (string) $channel );
		if ( 0 === strpos( $channel, 'WHATSAPP' ) ) {
			return 'WHATSAPP';
		}
		if ( 'SMS' === $channel || 'EMAIL' === $channel ) {
			return $channel;
		}
		return null;
	}

	/**
	 * Performs a request.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path, starting with a slash.
	 * @param array|null $body   JSON body.
	 * @return array|\WP_Error
	 */
	private function request( $method, $path, $body = null ) {
		if ( '' === $this->api_key ) {
			return new \WP_Error( 'unlimited_messaging_no_key', __( 'No API key set.', 'unlimited-messaging' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Accept'        => 'application/json',
				// Lets Unlimited Messaging tell plugin traffic apart from a
				// hand-written integration (same header as the n8n node).
				'X-UM-Client'   => 'wordpress',
			),
			'user-agent' => self::user_agent(),
		);
		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::base_url() . $path, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = is_array( $decoded ) && ! empty( $decoded['message'] ) ? $decoded['message'] : '';
			if ( is_array( $message ) ) {
				$message = implode( ' ', array_map( 'strval', $message ) );
			}
			$code = is_array( $decoded ) && ! empty( $decoded['context']['code'] ) ? (string) $decoded['context']['code'] : '';
			if ( 401 === $status ) {
				$message = __( 'The API key is invalid or was revoked.', 'unlimited-messaging' );
			}
			if ( '' === $message ) {
				/* translators: %d: HTTP status code. */
				$message = sprintf( __( 'Unlimited Messaging answered with HTTP %d.', 'unlimited-messaging' ), $status );
			}
			return new \WP_Error(
				'unlimited_messaging_http_' . $status,
				$message,
				array(
					'status' => $status,
					'code'   => $code,
				)
			);
		}

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * User-Agent naming the plugin, WordPress and WooCommerce versions.
	 *
	 * @return string
	 */
	private static function user_agent() {
		$ua = 'UnlimitedMessaging-WordPress/' . UNLIMITED_MESSAGING_VERSION . ' WordPress/' . get_bloginfo( 'version' );
		if ( defined( 'WC_VERSION' ) ) {
			$ua .= ' WooCommerce/' . WC_VERSION;
		}
		return $ua;
	}
}
