<?php
/**
 * Removes the plugin's options when it is deleted from the Plugins screen.
 * Order meta (`_unlimited_messaging_sent_*`) is left in place: it only marks
 * which notifications already went out.
 *
 * @package UnlimitedMessaging
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'unlimited_messaging_settings' );
delete_option( 'unlimited_messaging_log' );
