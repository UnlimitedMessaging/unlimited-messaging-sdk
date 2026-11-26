<?php
/**
 * Form submission alerts for the most used form plugins.
 *
 * @package UnlimitedMessaging
 */

namespace UnlimitedMessaging;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks Contact Form 7, WPForms, Gravity Forms, Elementor Pro, Fluent Forms
 * and Ninja Forms, normalizes each submission to a list of label/value pairs,
 * and sends one alert to the site owner.
 */
class Forms {

	/**
	 * Registers the hooks. Each one is inert when its form plugin is absent.
	 */
	public static function init() {
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'on_cf7' ), 20, 1 );
		add_action( 'wpforms_process_complete', array( __CLASS__, 'on_wpforms' ), 20, 3 );
		add_action( 'gform_after_submission', array( __CLASS__, 'on_gravity' ), 20, 2 );
		add_action( 'elementor_pro/forms/new_record', array( __CLASS__, 'on_elementor' ), 20, 1 );
		add_action( 'fluentform/submission_inserted', array( __CLASS__, 'on_fluent' ), 20, 3 );
		add_action( 'ninja_forms_after_submission', array( __CLASS__, 'on_ninja' ), 20, 1 );
	}

	/**
	 * Form plugins the site has, for the settings page.
	 *
	 * @return string[]
	 */
	public static function detected() {
		$found = array();
		if ( defined( 'WPCF7_VERSION' ) ) {
			$found[] = 'Contact Form 7';
		}
		if ( function_exists( 'wpforms' ) ) {
			$found[] = 'WPForms';
		}
		if ( class_exists( 'GFForms' ) ) {
			$found[] = 'Gravity Forms';
		}
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$found[] = 'Elementor Pro';
		}
		if ( defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ) ) {
			$found[] = 'Fluent Forms';
		}
		if ( class_exists( 'Ninja_Forms' ) ) {
			$found[] = 'Ninja Forms';
		}
		return $found;
	}

	/**
	 * Contact Form 7.
	 *
	 * @param object $form WPCF7_ContactForm.
	 */
	public static function on_cf7( $form ) {
		if ( ! class_exists( '\WPCF7_Submission' ) ) {
			return;
		}
		$submission = \WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}
		$fields = array();
		foreach ( (array) $submission->get_posted_data() as $name => $value ) {
			$fields[] = array( self::humanize( $name ), $value );
		}
		self::notify( method_exists( $form, 'title' ) ? $form->title() : 'Contact Form 7', $fields );
	}

	/**
	 * WPForms.
	 *
	 * @param array $fields    Submitted fields.
	 * @param array $entry     Raw entry.
	 * @param array $form_data Form settings.
	 */
	public static function on_wpforms( $fields, $entry, $form_data ) {
		$pairs = array();
		foreach ( (array) $fields as $field ) {
			if ( isset( $field['name'], $field['value'] ) ) {
				$pairs[] = array( $field['name'], $field['value'] );
			}
		}
		$title = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : 'WPForms';
		self::notify( $title, $pairs );
	}

	/**
	 * Gravity Forms.
	 *
	 * @param array $entry Entry.
	 * @param array $form  Form.
	 */
	public static function on_gravity( $entry, $form ) {
		$pairs = array();
		foreach ( (array) ( isset( $form['fields'] ) ? $form['fields'] : array() ) as $field ) {
			if ( ! is_object( $field ) || in_array( $field->type, array( 'html', 'section', 'page', 'captcha' ), true ) ) {
				continue;
			}
			$value   = method_exists( $field, 'get_value_export' ) ? $field->get_value_export( $entry ) : rgar( $entry, (string) $field->id );
			$pairs[] = array( $field->label, $value );
		}
		self::notify( isset( $form['title'] ) ? $form['title'] : 'Gravity Forms', $pairs );
	}

	/**
	 * Elementor Pro forms.
	 *
	 * @param object $record Form_Record.
	 */
	public static function on_elementor( $record ) {
		if ( ! is_object( $record ) || ! method_exists( $record, 'get' ) ) {
			return;
		}
		$pairs = array();
		foreach ( (array) $record->get( 'fields' ) as $id => $field ) {
			$label   = ! empty( $field['title'] ) ? $field['title'] : $id;
			$pairs[] = array( $label, isset( $field['value'] ) ? $field['value'] : '' );
		}
		$name = method_exists( $record, 'get_form_settings' ) ? $record->get_form_settings( 'form_name' ) : '';
		self::notify( $name ? $name : 'Elementor', $pairs );
	}

	/**
	 * Fluent Forms.
	 *
	 * @param int    $entry_id  Entry id.
	 * @param array  $form_data Submitted data, keyed by field name.
	 * @param object $form      Form.
	 */
	public static function on_fluent( $entry_id, $form_data, $form ) {
		$pairs = array();
		foreach ( (array) $form_data as $name => $value ) {
			$pairs[] = array( self::humanize( $name ), $value );
		}
		self::notify( is_object( $form ) && ! empty( $form->title ) ? $form->title : 'Fluent Forms', $pairs );
	}

	/**
	 * Ninja Forms.
	 *
	 * @param array $form_data Submission.
	 */
	public static function on_ninja( $form_data ) {
		$pairs = array();
		foreach ( (array) ( isset( $form_data['fields'] ) ? $form_data['fields'] : array() ) as $field ) {
			if ( isset( $field['type'] ) && in_array( $field['type'], array( 'submit', 'html', 'hr', 'recaptcha' ), true ) ) {
				continue;
			}
			$pairs[] = array( isset( $field['label'] ) ? $field['label'] : '', isset( $field['value'] ) ? $field['value'] : '' );
		}
		$title = isset( $form_data['settings']['title'] ) ? $form_data['settings']['title'] : 'Ninja Forms';
		self::notify( $title, $pairs );
	}

	/**
	 * Field name to a readable label, for form plugins that only give names:
	 * "your-name" -> "Name", "phone_number" -> "Phone number". Technical
	 * names starting with "_" are kept so format_fields() can drop them.
	 *
	 * @param string $name Field name.
	 * @return string
	 */
	public static function humanize( $name ) {
		$name = (string) $name;
		if ( 0 === strpos( $name, '_' ) ) {
			return $name;
		}
		$label = preg_replace( '/^your[-_]/i', '', $name );
		$label = trim( str_replace( array( '-', '_' ), ' ', $label ) );
		return '' === $label ? $name : ucfirst( $label );
	}

	/**
	 * Formats label/value pairs as "Label: value" lines, dropping technical
	 * fields (CF7's `_wpcf7*`, captcha tokens, empty values).
	 *
	 * @param array $pairs List of [label, value].
	 * @return string
	 */
	public static function format_fields( array $pairs ) {
		$lines = array();
		foreach ( $pairs as $pair ) {
			list( $label, $value ) = $pair;
			$label                 = trim( wp_strip_all_tags( (string) $label ) );
			if ( '' === $label || 0 === strpos( $label, '_' ) || preg_match( '/captcha|turnstile|honeypot|nonce/i', $label ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', array_filter( array_map( 'trim', array_map( 'strval', $value ) ) ) ) );
			}
			$value = trim( wp_strip_all_tags( (string) $value ) );
			if ( '' === $value ) {
				continue;
			}
			if ( function_exists( 'mb_substr' ) && mb_strlen( $value ) > 500 ) {
				$value = mb_substr( $value, 0, 500 ) . '...';
			}
			$lines[] = $label . ': ' . $value;
		}
		return implode( "\n", $lines );
	}

	/**
	 * Sends the alert to the site owner.
	 *
	 * @param string $form_name Form title.
	 * @param array  $pairs     List of [label, value].
	 */
	public static function notify( $form_name, array $pairs ) {
		$config = Settings::get( 'forms' );
		if ( empty( $config['enabled'] ) || empty( $config['channels'] ) ) {
			return;
		}

		$vars = array(
			'form_name' => wp_strip_all_tags( (string) $form_name ),
			'fields'    => self::format_fields( $pairs ),
		);

		$text    = '' !== trim( $config['text'] ) ? $config['text'] : Settings::default_form_text();
		$subject = '' !== trim( $config['subject'] ) ? $config['subject'] : Settings::default_form_subject();

		/**
		 * Filters the text of a form alert before it is sent.
		 *
		 * @param string $text      Rendered text.
		 * @param string $form_name Form title.
		 * @param array  $pairs     Submitted [label, value] pairs.
		 */
		$text = apply_filters( 'unlimited_messaging_form_text', Templates::render( $text, $vars ), $form_name, $pairs );

		$email = Settings::get( 'admin_email' );
		if ( '' === $email ) {
			$email = get_option( 'admin_email' );
		}
		$payload = Sender::build_payload(
			$config['channels'],
			Templates::to_e164( Settings::get( 'admin_phone' ) ),
			$email,
			$text,
			Templates::render( $subject, $vars )
		);
		if ( ! $payload ) {
			return;
		}

		/* translators: %s: form name. */
		Sender::queue( $payload, sprintf( __( 'Form "%s"', 'unlimited-messaging' ), $vars['form_name'] ) );
	}
}
