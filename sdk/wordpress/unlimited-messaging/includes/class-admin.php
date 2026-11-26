<?php
/**
 * Settings screens.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu, the four tabs, and the save / test / clear handlers.
 */
class Admin {

	const SLUG       = 'unlimited-messaging';
	const CAPABILITY = 'manage_options';
	const NOTICE     = 'unlimited_messaging_notice_';

	/**
	 * Registers admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_unlimited_messaging_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_unlimited_messaging_test', array( __CLASS__, 'handle_test' ) );
		add_action( 'admin_post_unlimited_messaging_clear_log', array( __CLASS__, 'handle_clear_log' ) );
		add_action( 'admin_notices', array( __CLASS__, 'setup_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( UNLIMITED_MESSAGING_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Link to the app with attribution, so sign-ups from the plugin are
	 * counted as such.
	 *
	 * @param string $path     App path ("/" for sign-up, "/api" for keys...).
	 * @param string $campaign utm_campaign / where in the plugin.
	 * @return string
	 */
	public static function app_url( $path, $campaign ) {
		return add_query_arg(
			array(
				'source'       => 'wordpress',
				'utm_source'   => 'wordpress',
				'utm_medium'   => 'plugin',
				'utm_campaign' => $campaign,
			),
			'https://app.unlimitedmessaging.app' . $path
		);
	}

	/**
	 * Settings page URL.
	 *
	 * @param string $tab Tab.
	 * @return string
	 */
	public static function page_url( $tab = 'connection' ) {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Menu entry.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Unlimited Messaging', 'unlimited-messaging' ),
			__( 'Messaging', 'unlimited-messaging' ),
			self::CAPABILITY,
			self::SLUG,
			array( __CLASS__, 'render' ),
			'dashicons-format-chat',
			58
		);
	}

	/**
	 * Styles, only on our page.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'unlimited-messaging-admin', UNLIMITED_MESSAGING_URL . 'assets/admin.css', array(), UNLIMITED_MESSAGING_VERSION );
	}

	/**
	 * "Settings" link on the plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::page_url() ) . '">' . esc_html__( 'Settings', 'unlimited-messaging' ) . '</a>' );
		return $links;
	}

	/**
	 * Nudge on the plugins screen and dashboard until an API key is set.
	 */
	public static function setup_notice() {
		if ( ! current_user_can( self::CAPABILITY ) || '' !== Settings::get( 'api_key' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'dashboard' ), true ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Unlimited Messaging is installed. Add your API key to start sending order updates by WhatsApp, SMS and email.', 'unlimited-messaging' ),
			esc_url( self::page_url() ),
			esc_html__( 'Set it up', 'unlimited-messaging' )
		);
	}

	/**
	 * Current tab, whitelisted.
	 *
	 * @return string
	 */
	private static function current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'connection';
		return in_array( $tab, array( 'connection', 'woocommerce', 'forms', 'log' ), true ) ? $tab : 'connection';
	}

	/**
	 * Page.
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$tab  = self::current_tab();
		$tabs = array(
			'connection'  => __( 'Connection', 'unlimited-messaging' ),
			'woocommerce' => __( 'WooCommerce', 'unlimited-messaging' ),
			'forms'       => __( 'Forms', 'unlimited-messaging' ),
			'log'         => __( 'Activity', 'unlimited-messaging' ),
		);
		?>
		<div class="wrap umsg">
			<h1><?php esc_html_e( 'Unlimited Messaging', 'unlimited-messaging' ); ?></h1>
			<p class="umsg-lede"><?php esc_html_e( 'WhatsApp, SMS and email from your own number and mailbox. One account, no Twilio, no Meta templates.', 'unlimited-messaging' ); ?></p>
			<?php self::render_notice(); ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a href="<?php echo esc_url( self::page_url( $key ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="umsg-body">
				<?php
				switch ( $tab ) {
					case 'woocommerce':
						self::render_woocommerce();
						break;
					case 'forms':
						self::render_forms();
						break;
					case 'log':
						self::render_log();
						break;
					default:
						self::render_connection();
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Shows and clears the one-shot notice left by a handler.
	 */
	private static function render_notice() {
		$key    = self::NOTICE . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			'error' === $notice['type'] ? 'error' : 'success',
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Stores a notice for the next page load.
	 *
	 * @param string $type    success|error.
	 * @param string $message Text.
	 */
	private static function flash( $type, $message ) {
		set_transient(
			self::NOTICE . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			60
		);
	}

	/**
	 * Accounts of the connected Unlimited Messaging user, cached 5 minutes.
	 *
	 * @param bool $refresh Skip the cache.
	 * @return array|\WP_Error
	 */
	private static function accounts( $refresh = false ) {
		$key = Settings::get( 'api_key' );
		if ( '' === $key ) {
			return array();
		}
		$cache_key = 'unlimited_messaging_accounts_' . md5( $key );
		$cached    = $refresh ? false : get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$accounts = ( new Api( $key ) )->accounts();
		if ( ! is_wp_error( $accounts ) ) {
			set_transient( $cache_key, $accounts, 5 * MINUTE_IN_SECONDS );
		}
		return $accounts;
	}

	/**
	 * Channel labels.
	 *
	 * @return array
	 */
	private static function channel_labels() {
		return array(
			'WHATSAPP' => __( 'WhatsApp', 'unlimited-messaging' ),
			'SMS'      => __( 'SMS', 'unlimited-messaging' ),
			'EMAIL'    => __( 'Email', 'unlimited-messaging' ),
		);
	}

	/**
	 * Opening of a form posting to admin-post.php.
	 *
	 * @param string $action Action.
	 * @param string $tab    Tab to come back to.
	 */
	private static function form_open( $action, $tab ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
		wp_nonce_field( $action );
	}

	/**
	 * Channel checkboxes.
	 *
	 * @param string   $name     Input name (without []).
	 * @param string[] $selected Selected channels.
	 */
	private static function channel_checkboxes( $name, array $selected ) {
		foreach ( self::channel_labels() as $channel => $label ) {
			printf(
				'<label class="umsg-check"><input type="checkbox" name="%s[]" value="%s" %s> %s</label>',
				esc_attr( $name ),
				esc_attr( $channel ),
				checked( in_array( $channel, $selected, true ), true, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Connection tab.
	 */
	private static function render_connection() {
		$settings = Settings::all();
		$has_key  = '' !== $settings['api_key'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only refresh flag.
		$accounts = self::accounts( isset( $_GET['refresh'] ) );
		$labels   = self::channel_labels();

		if ( ! $has_key ) :
			?>
			<div class="umsg-card umsg-start">
				<h2><?php esc_html_e( 'Get started in 3 steps', 'unlimited-messaging' ); ?></h2>
				<ol>
					<li>
						<?php
						printf(
							/* translators: %s: link to create an account. */
							esc_html__( '%s (free plan, no card needed).', 'unlimited-messaging' ),
							'<a href="' . esc_url( self::app_url( '/login', 'wordpress_plugin_signup' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Create your Unlimited Messaging account', 'unlimited-messaging' ) . '</a>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Connect your channels: scan a QR code for WhatsApp, sign in to your mailbox for email, install the free Android app for SMS.', 'unlimited-messaging' ); ?></li>
					<li>
						<?php
						printf(
							/* translators: %s: link to the API keys page. */
							esc_html__( 'Create an API key on the %s page and paste it below.', 'unlimited-messaging' ),
							'<a href="' . esc_url( self::app_url( '/api', 'wordpress_plugin_key' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'API keys', 'unlimited-messaging' ) . '</a>'
						);
						?>
					</li>
				</ol>
			</div>
			<?php
		endif;

		self::form_open( 'unlimited_messaging_save', 'connection' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umsg-api-key"><?php esc_html_e( 'API key', 'unlimited-messaging' ); ?></label></th>
				<td>
					<input type="password" id="umsg-api-key" name="api_key" value="<?php echo esc_attr( $settings['api_key'] ); ?>" class="regular-text" autocomplete="off" placeholder="ak_...">
					<p class="description">
						<a href="<?php echo esc_url( self::app_url( '/api', 'wordpress_plugin_key' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get an API key', 'unlimited-messaging' ); ?></a>
					</p>
				</td>
			</tr>
			<?php if ( $has_key ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'unlimited-messaging' ); ?></th>
					<td>
						<?php if ( is_wp_error( $accounts ) ) : ?>
							<p class="umsg-bad"><?php echo esc_html( Sender::error_text( $accounts ) ); ?></p>
						<?php else : ?>
							<p class="umsg-ok"><?php esc_html_e( 'Connected.', 'unlimited-messaging' ); ?>
								<a href="<?php echo esc_url( add_query_arg( 'refresh', '1', self::page_url() ) ); ?>"><?php esc_html_e( 'Refresh accounts', 'unlimited-messaging' ); ?></a>
							</p>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( ! is_wp_error( $accounts ) ) : ?>
					<?php foreach ( $labels as $channel => $label ) : ?>
						<?php
						$options = array_values(
							array_filter(
								$accounts,
								static function ( $account ) use ( $channel ) {
									return $account['channel'] === $channel && 'ACTIVE' === $account['status'];
								}
							)
						);
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<?php if ( empty( $options ) ) : ?>
									<span class="umsg-muted"><?php esc_html_e( 'Not connected.', 'unlimited-messaging' ); ?></span>
									<a href="<?php echo esc_url( self::app_url( '/accounts', 'wordpress_plugin_connect_' . strtolower( $channel ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Connect it', 'unlimited-messaging' ); ?></a>
								<?php else : ?>
									<select name="accounts[<?php echo esc_attr( $channel ); ?>]">
										<option value=""><?php esc_html_e( 'Automatic', 'unlimited-messaging' ); ?></option>
										<?php foreach ( $options as $account ) : ?>
											<option value="<?php echo esc_attr( $account['id'] ); ?>" <?php selected( $settings['accounts'][ $channel ], $account['id'] ); ?>><?php echo esc_html( $account['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php endif; ?>
		</table>

		<h2><?php esc_html_e( 'Where to reach you', 'unlimited-messaging' ); ?></h2>
		<p class="description"><?php esc_html_e( 'New orders and form submissions are sent here.', 'unlimited-messaging' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umsg-admin-phone"><?php esc_html_e( 'Your phone', 'unlimited-messaging' ); ?></label></th>
				<td>
					<input type="tel" id="umsg-admin-phone" name="admin_phone" value="<?php echo esc_attr( $settings['admin_phone'] ); ?>" class="regular-text" placeholder="+33612345678">
					<p class="description"><?php esc_html_e( 'International format, with the country code. For WhatsApp and SMS.', 'unlimited-messaging' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="umsg-admin-email"><?php esc_html_e( 'Your email', 'unlimited-messaging' ); ?></label></th>
				<td>
					<input type="email" id="umsg-admin-email" name="admin_email" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Leave empty to use the site admin email.', 'unlimited-messaging' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Safety', 'unlimited-messaging' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umsg-limit"><?php esc_html_e( 'Messages per hour', 'unlimited-messaging' ); ?></label></th>
				<td>
					<input type="number" min="0" step="1" id="umsg-limit" name="hourly_limit" value="<?php echo esc_attr( (string) $settings['hourly_limit'] ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'At most this many notifications go out per hour, so a bug or a wave of fake orders cannot flood your number. 0 means no limit.', 'unlimited-messaging' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
		</form>

		<?php if ( $has_key ) : ?>
			<div class="umsg-card">
				<h2><?php esc_html_e( 'Send a test', 'unlimited-messaging' ); ?></h2>
				<?php self::form_open( 'unlimited_messaging_test', 'connection' ); ?>
					<p><?php esc_html_e( 'Sends a test message to your phone and email above.', 'unlimited-messaging' ); ?></p>
					<p><?php self::channel_checkboxes( 'channels', array( 'WHATSAPP' ) ); ?></p>
					<?php submit_button( __( 'Send test message', 'unlimited-messaging' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * WooCommerce tab.
	 */
	private static function render_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce is not active. Order notifications appear here once it is.', 'unlimited-messaging' ) . '</p>';
			return;
		}
		$wc = Settings::get( 'wc' );

		self::form_open( 'unlimited_messaging_save', 'woocommerce' );
		?>
		<p><?php esc_html_e( 'Pick which order updates go out, to whom and on which channels. Select several channels to reach a customer everywhere at once: if they miss the WhatsApp, the SMS or the email still gets there.', 'unlimited-messaging' ); ?></p>
		<p class="description">
			<?php esc_html_e( 'Leave a message empty to use the default text, in your site language. Placeholders:', 'unlimited-messaging' ); ?>
			<code><?php echo esc_html( implode( ' ', Templates::order_placeholders() ) ); ?></code>
		</p>

		<?php foreach ( Settings::wc_events() as $event => $def ) : ?>
			<?php foreach ( $def['audiences'] as $audience ) : ?>
				<?php
				$config = $wc[ $event ][ $audience ];
				$base   = 'wc[' . $event . '][' . $audience . ']';
				$id     = 'umsg-' . $event . '-' . $audience;
				?>
				<div class="umsg-card umsg-event <?php echo ! empty( $config['enabled'] ) ? 'is-on' : ''; ?>">
					<div class="umsg-event-head">
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[enabled]" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?>>
							<strong><?php echo esc_html( $def['label'] ); ?></strong>
						</label>
						<span class="umsg-to">
							<?php echo 'admin' === $audience ? esc_html__( 'to you', 'unlimited-messaging' ) : esc_html__( 'to the customer', 'unlimited-messaging' ); ?>
						</span>
					</div>
					<p class="umsg-channels"><?php self::channel_checkboxes( $base . '[channels]', (array) $config['channels'] ); ?></p>
					<p>
						<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>-text"><?php esc_html_e( 'Message', 'unlimited-messaging' ); ?></label>
						<textarea id="<?php echo esc_attr( $id ); ?>-text" name="<?php echo esc_attr( $base ); ?>[text]" rows="3" class="large-text" placeholder="<?php echo esc_attr( Settings::default_text( $event, $audience ) ); ?>"><?php echo esc_textarea( $config['text'] ); ?></textarea>
					</p>
					<p>
						<label for="<?php echo esc_attr( $id ); ?>-subject"><?php esc_html_e( 'Email subject', 'unlimited-messaging' ); ?></label>
						<input type="text" id="<?php echo esc_attr( $id ); ?>-subject" name="<?php echo esc_attr( $base ); ?>[subject]" value="<?php echo esc_attr( $config['subject'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( Settings::default_subject() ); ?>">
					</p>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>
		<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Forms tab.
	 */
	private static function render_forms() {
		$config   = Settings::get( 'forms' );
		$detected = Forms::detected();

		self::form_open( 'unlimited_messaging_save', 'forms' );
		?>
		<p><?php esc_html_e( 'Get every form submission on your phone the moment it arrives. Works with Contact Form 7, WPForms, Gravity Forms, Elementor Pro, Fluent Forms and Ninja Forms.', 'unlimited-messaging' ); ?></p>
		<p class="description">
			<?php
			echo empty( $detected )
				? esc_html__( 'No supported form plugin detected yet.', 'unlimited-messaging' )
				/* translators: %s: list of form plugins. */
				: esc_html( sprintf( __( 'Detected: %s.', 'unlimited-messaging' ), implode( ', ', $detected ) ) );
			?>
		</p>
		<div class="umsg-card umsg-event <?php echo ! empty( $config['enabled'] ) ? 'is-on' : ''; ?>">
			<div class="umsg-event-head">
				<label>
					<input type="checkbox" name="forms[enabled]" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?>>
					<strong><?php esc_html_e( 'New form submission', 'unlimited-messaging' ); ?></strong>
				</label>
				<span class="umsg-to"><?php esc_html_e( 'to you', 'unlimited-messaging' ); ?></span>
			</div>
			<p class="umsg-channels"><?php self::channel_checkboxes( 'forms[channels]', (array) $config['channels'] ); ?></p>
			<p>
				<label for="umsg-form-text"><?php esc_html_e( 'Message', 'unlimited-messaging' ); ?></label>
				<textarea id="umsg-form-text" name="forms[text]" rows="4" class="large-text" placeholder="<?php echo esc_attr( Settings::default_form_text() ); ?>"><?php echo esc_textarea( $config['text'] ); ?></textarea>
				<span class="description"><?php esc_html_e( 'Placeholders:', 'unlimited-messaging' ); ?> <code>{form_name} {fields} {site_name} {site_url}</code></span>
			</p>
			<p>
				<label for="umsg-form-subject"><?php esc_html_e( 'Email subject', 'unlimited-messaging' ); ?></label>
				<input type="text" id="umsg-form-subject" name="forms[subject]" value="<?php echo esc_attr( $config['subject'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( Settings::default_form_subject() ); ?>">
			</p>
		</div>
		<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Activity tab.
	 */
	private static function render_log() {
		$entries = Log::all();
		$labels  = self::channel_labels();
		?>
		<p>
			<?php esc_html_e( 'The last 100 notifications. The full history, with delivery status, is in your', 'unlimited-messaging' ); ?>
			<a href="<?php echo esc_url( self::app_url( '/messages', 'wordpress_plugin_log' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Unlimited Messaging dashboard', 'unlimited-messaging' ); ?></a>.
		</p>
		<?php if ( empty( $entries ) ) : ?>
			<p class="umsg-muted"><?php esc_html_e( 'Nothing sent yet.', 'unlimited-messaging' ); ?></p>
			<?php return; ?>
		<?php endif; ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'When', 'unlimited-messaging' ); ?></th>
					<th><?php esc_html_e( 'What', 'unlimited-messaging' ); ?></th>
					<th><?php esc_html_e( 'Channels', 'unlimited-messaging' ); ?></th>
					<th><?php esc_html_e( 'To', 'unlimited-messaging' ); ?></th>
					<th><?php esc_html_e( 'Result', 'unlimited-messaging' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $entries as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $entry['time'] ) ); ?></td>
						<td><?php echo esc_html( $entry['source'] ); ?></td>
						<td>
							<?php
							echo esc_html(
								implode(
									', ',
									array_map(
										static function ( $channel ) use ( $labels ) {
											return isset( $labels[ $channel ] ) ? $labels[ $channel ] : $channel;
										},
										(array) $entry['channels']
									)
								)
							);
							?>
						</td>
						<td><?php echo esc_html( $entry['to'] ); ?></td>
						<td class="<?php echo $entry['ok'] ? 'umsg-ok' : 'umsg-bad'; ?>">
							<?php echo $entry['ok'] ? esc_html__( 'Sent', 'unlimited-messaging' ) : esc_html( $entry['detail'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php self::form_open( 'unlimited_messaging_clear_log', 'log' ); ?>
			<?php submit_button( __( 'Clear activity', 'unlimited-messaging' ), 'secondary' ); ?>
		</form>
		<?php
	}

	/**
	 * Checks capability and nonce, and returns the tab posted back.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	private static function verify( $action ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'unlimited-messaging' ) );
		}
		check_admin_referer( $action );
		return isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'connection';
	}

	/**
	 * Sanitizes a list of channels.
	 *
	 * @param mixed $value Posted value.
	 * @return string[]
	 */
	private static function sanitize_channels( $value ) {
		$value = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();
		$value = array_map( 'strtoupper', $value );
		return array_values( array_intersect( Settings::CHANNELS, $value ) );
	}

	/**
	 * Saves the tab that was posted.
	 */
	public static function handle_save() {
		$tab      = self::verify( 'unlimited_messaging_save' );
		$settings = Settings::all();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in verify().
		if ( 'connection' === $tab ) {
			$key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
			if ( $key !== $settings['api_key'] ) {
				// A new key means a different user: their accounts differ.
				$settings['accounts'] = array_fill_keys( Settings::CHANNELS, '' );
			}
			$settings['api_key'] = $key;

			$posted_accounts = isset( $_POST['accounts'] ) && is_array( $_POST['accounts'] ) ? map_deep( wp_unslash( $_POST['accounts'] ), 'sanitize_text_field' ) : array();
			foreach ( Settings::CHANNELS as $channel ) {
				if ( isset( $posted_accounts[ $channel ] ) ) {
					$settings['accounts'][ $channel ] = sanitize_text_field( $posted_accounts[ $channel ] );
				}
			}

			$phone                   = isset( $_POST['admin_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['admin_phone'] ) ) : '';
			$settings['admin_phone'] = '' === $phone ? '' : Templates::to_e164( $phone );
			$email                   = isset( $_POST['admin_email'] ) ? sanitize_email( wp_unslash( $_POST['admin_email'] ) ) : '';
			$settings['admin_email'] = is_email( $email ) ? $email : '';
			$settings['hourly_limit'] = isset( $_POST['hourly_limit'] ) ? max( 0, absint( $_POST['hourly_limit'] ) ) : 60;
		}

		if ( 'woocommerce' === $tab ) {
			$posted = isset( $_POST['wc'] ) && is_array( $_POST['wc'] ) ? map_deep( wp_unslash( $_POST['wc'] ), 'sanitize_textarea_field' ) : array();
			foreach ( Settings::wc_events() as $event => $def ) {
				foreach ( $def['audiences'] as $audience ) {
					$row = isset( $posted[ $event ][ $audience ] ) && is_array( $posted[ $event ][ $audience ] ) ? $posted[ $event ][ $audience ] : array();

					$settings['wc'][ $event ][ $audience ] = array(
						'enabled'  => ! empty( $row['enabled'] ),
						'channels' => self::sanitize_channels( isset( $row['channels'] ) ? $row['channels'] : array() ),
						'text'     => isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '',
						'subject'  => isset( $row['subject'] ) ? sanitize_text_field( $row['subject'] ) : '',
					);
				}
			}
		}

		if ( 'forms' === $tab ) {
			$row               = isset( $_POST['forms'] ) && is_array( $_POST['forms'] ) ? map_deep( wp_unslash( $_POST['forms'] ), 'sanitize_textarea_field' ) : array();
			$settings['forms'] = array(
				'enabled'  => ! empty( $row['enabled'] ),
				'channels' => self::sanitize_channels( isset( $row['channels'] ) ? $row['channels'] : array() ),
				'text'     => isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '',
				'subject'  => isset( $row['subject'] ) ? sanitize_text_field( $row['subject'] ) : '',
			);
		}
		// phpcs:enable

		Settings::save( $settings );
		self::flash( 'success', __( 'Settings saved.', 'unlimited-messaging' ) );
		wp_safe_redirect( self::page_url( $tab ) );
		exit;
	}

	/**
	 * Sends a test message right away and reports the result.
	 */
	public static function handle_test() {
		self::verify( 'unlimited_messaging_test' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify().
		$channels = self::sanitize_channels( isset( $_POST['channels'] ) ? map_deep( wp_unslash( $_POST['channels'] ), 'sanitize_key' ) : array() );

		$email = Settings::get( 'admin_email' );
		if ( '' === $email ) {
			$email = get_option( 'admin_email' );
		}
		$payload = Sender::build_payload(
			$channels,
			Templates::to_e164( Settings::get( 'admin_phone' ) ),
			$email,
			Templates::render( __( 'Test from {site_name}: Unlimited Messaging is connected. Order updates and form alerts will arrive here.', 'unlimited-messaging' ), array() ),
			Templates::render( __( '{site_name}: test message', 'unlimited-messaging' ), array() )
		);

		if ( ! $payload ) {
			self::flash( 'error', __( 'Nothing to send: pick a channel and fill in your phone (WhatsApp, SMS) or email first, then save.', 'unlimited-messaging' ) );
		} else {
			$result = Sender::send_now( $payload, __( 'Test message', 'unlimited-messaging' ) );
			if ( is_wp_error( $result ) ) {
				self::flash( 'error', Sender::error_text( $result ) );
			} else {
				self::flash( 'success', __( 'Test message sent. It should arrive within a few seconds.', 'unlimited-messaging' ) );
			}
		}
		wp_safe_redirect( self::page_url( 'connection' ) );
		exit;
	}

	/**
	 * Clears the activity log.
	 */
	public static function handle_clear_log() {
		self::verify( 'unlimited_messaging_clear_log' );
		Log::clear();
		wp_safe_redirect( self::page_url( 'log' ) );
		exit;
	}
}
