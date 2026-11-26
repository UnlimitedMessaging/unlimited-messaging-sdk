<?php
/**
 * Builds and sends a notification, in the background when possible.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * One entry point, `Sender::queue()`, used by every trigger.
 */
class Sender {

	const HOOK  = 'unlimited_messaging_send';
	const GROUP = 'unlimited-messaging';

	/**
	 * Registers the background job handler.
	 */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run_job' ), 10, 1 );
	}

	/**
	 * Builds the `POST /message` body for one notification.
	 *
	 * Returns null when no selected channel has a recipient (a customer
	 * without a phone number and only WhatsApp selected, for instance).
	 *
	 * @param string[] $channels Selected channels.
	 * @param string   $phone    E.164 phone, may be empty.
	 * @param string   $email    Email, may be empty.
	 * @param string   $text     Message text.
	 * @param string   $subject  Email subject.
	 * @return array|null
	 */
	public static function build_payload( array $channels, $phone, $email, $text, $subject ) {
		$accounts = Settings::get( 'accounts' );
		$entries  = array();
		$to       = array();

		foreach ( array_intersect( Settings::CHANNELS, $channels ) as $channel ) {
			if ( 'EMAIL' === $channel ) {
				if ( ! is_email( $email ) ) {
					continue;
				}
				$to['email'] = $email;
			} else {
				if ( '' === $phone ) {
					continue;
				}
				$to['phone'] = $phone;
			}

			$entry = array( 'channel' => $channel );
			if ( ! empty( $accounts[ $channel ] ) ) {
				$entry['accountId'] = $accounts[ $channel ];
			}
			if ( 'EMAIL' === $channel ) {
				$entry['subject'] = '' !== trim( $subject ) ? $subject : get_bloginfo( 'name' );
			}
			$entries[] = $entry;
		}

		if ( empty( $entries ) || '' === trim( $text ) ) {
			return null;
		}

		return array(
			'to'       => $to,
			'channels' => $entries,
			'text'     => $text,
			// Order updates and form alerts jump ahead of any bulk send
			// waiting on the same account.
			'priority' => 'transactional',
		);
	}

	/**
	 * Sends a notification in the background (Action Scheduler, bundled with
	 * WooCommerce) so a checkout or a form never waits on the network. Without
	 * Action Scheduler, sends right away.
	 *
	 * @param array  $payload Body built by `build_payload()`.
	 * @param string $source  Human label for the log.
	 */
	public static function queue( array $payload, $source ) {
		$job = array(
			'payload' => $payload,
			'source'  => $source,
		);
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::HOOK, array( $job ), self::GROUP );
			return;
		}
		self::run_job( $job );
	}

	/**
	 * Messages still waiting in Action Scheduler, and whether some have waited
	 * long enough to mean the queue is not running (no WP-Cron, loopback
	 * requests blocked by the host).
	 *
	 * @return array{count: int, stuck: bool}
	 */
	public static function pending() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return array(
				'count' => 0,
				'stuck' => false,
			);
		}
		$ids   = as_get_scheduled_actions(
			array(
				'hook'     => self::HOOK,
				'group'    => self::GROUP,
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 100,
			),
			'ids'
		);
		$stuck = (bool) as_get_scheduled_actions(
			array(
				'hook'         => self::HOOK,
				'group'        => self::GROUP,
				'status'       => \ActionScheduler_Store::STATUS_PENDING,
				'date'         => gmdate( 'Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS ),
				'date_compare' => '<=',
				'per_page'     => 1,
			),
			'ids'
		);
		return array(
			'count' => count( $ids ),
			'stuck' => $stuck,
		);
	}

	/**
	 * Background job: applies the hourly limit, sends, logs.
	 *
	 * @param array $job `{payload, source}`.
	 */
	public static function run_job( $job ) {
		if ( ! is_array( $job ) || empty( $job['payload'] ) ) {
			return;
		}
		$payload  = $job['payload'];
		$source   = isset( $job['source'] ) ? $job['source'] : '';
		$channels = wp_list_pluck( $payload['channels'], 'channel' );
		$to       = implode( ', ', array_filter( (array) $payload['to'] ) );

		if ( ! self::take_hourly_slot() ) {
			Log::add( $source, $channels, $to, false, __( 'Skipped: hourly limit reached. Raise it in the settings if this is expected.', 'unlimited-messaging' ) );
			return;
		}

		self::send_now( $payload, $source );
	}

	/**
	 * Sends synchronously and logs the outcome.
	 *
	 * @param array  $payload Body of `POST /message`.
	 * @param string $source  Human label for the log.
	 * @return true|\WP_Error
	 */
	public static function send_now( array $payload, $source ) {
		$api    = new Api( Settings::get( 'api_key' ) );
		$result = $api->send( $payload );

		// Several accounts on a channel and none chosen yet (a key saved before
		// accounts were picked automatically): pick and remember one, retry once.
		if ( is_wp_error( $result ) && 'ACCOUNT_REQUIRED' === self::error_code( $result ) ) {
			$retry = self::with_accounts( $api, $payload );
			if ( $retry ) {
				$payload = $retry;
				$result  = $api->send( $payload );
			}
		}

		$channels = wp_list_pluck( $payload['channels'], 'channel' );
		$to       = implode( ', ', array_filter( (array) $payload['to'] ) );

		if ( is_wp_error( $result ) ) {
			Log::add( $source, $channels, $to, false, self::error_text( $result ) );
			return $result;
		}

		$detail = isset( $result['dispatchId'] ) ? (string) $result['dispatchId'] : ( isset( $result['id'] ) ? (string) $result['id'] : '' );
		Log::add( $source, $channels, $to, true, $detail );
		return true;
	}

	/**
	 * The API's error code (`context.code`) of a failed call.
	 *
	 * @param \WP_Error $error Error.
	 * @return string
	 */
	private static function error_code( \WP_Error $error ) {
		$data = $error->get_error_data();
		return is_array( $data ) && isset( $data['code'] ) ? (string) $data['code'] : '';
	}

	/**
	 * Fills in `accountId` for channels sent without one, saves the choice,
	 * and returns the new payload (null when nothing could be filled).
	 *
	 * @param Api   $api     Client.
	 * @param array $payload Body of `POST /message`.
	 * @return array|null
	 */
	private static function with_accounts( Api $api, array $payload ) {
		$accounts = $api->accounts();
		if ( is_wp_error( $accounts ) ) {
			return null;
		}
		$settings = Settings::all();
		$changed  = false;
		foreach ( $payload['channels'] as $i => $entry ) {
			if ( ! empty( $entry['accountId'] ) ) {
				continue;
			}
			$id = Admin::account_for( $entry['channel'], '', $accounts );
			if ( '' !== $id ) {
				$payload['channels'][ $i ]['accountId'] = $id;
				$settings['accounts'][ $entry['channel'] ] = $id;
				$changed = true;
			}
		}
		if ( ! $changed ) {
			return null;
		}
		Settings::save( $settings );
		return $payload;
	}

	/**
	 * Error message with the one hint that matters for each known case.
	 *
	 * @param \WP_Error $error Error from the API client.
	 * @return string
	 */
	public static function error_text( \WP_Error $error ) {
		$code    = self::error_code( $error );
		$message = $error->get_error_message();

		if ( 'PLAN_REQUIRED' === $code ) {
			$message .= ' ' . __( 'Your plan does not cover this. See the plans: https://app.unlimitedmessaging.app/plans', 'unlimited-messaging' );
		} elseif ( 'NO_ACCOUNT_CONNECTED' === $code ) {
			$message .= ' ' . __( 'Connect a WhatsApp number, a mailbox or an Android phone for this channel in your Unlimited Messaging dashboard.', 'unlimited-messaging' );
		} elseif ( 'ACCOUNT_REQUIRED' === $code ) {
			$message .= ' ' . __( 'You have several accounts on this channel: pick one in the Connection tab.', 'unlimited-messaging' );
		}
		return $message;
	}

	/**
	 * Counts this send against the hourly limit. Returns false when the limit
	 * is reached. A safety net for the shop's own numbers: a bug or a flood
	 * of fake orders cannot turn into thousands of messages.
	 *
	 * @return bool
	 */
	private static function take_hourly_slot() {
		$limit = (int) Settings::get( 'hourly_limit' );
		if ( $limit <= 0 ) {
			return true;
		}
		$key   = 'unlimited_messaging_count_' . gmdate( 'YmdH' );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}
}
