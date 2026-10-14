<?php
/**
 * Domain authentication checks for the CF7 Apps dashboard sidebar.
 *
 * @package CF7Apps
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CF7Apps_Domain_Authentication
 */
class CF7Apps_Domain_Authentication {

	const CACHE_KEY = 'cf7apps_domain_authentication_v3';

	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Post SMTP Mail Tester API (same integration as Post SMTP plugin).
	 *
	 * @see https://smtper.postmansmtp.com/wp-json/mail-tester/v1
	 */
	const MAIL_TESTER_BASE_URL = 'https://smtper.postmansmtp.com/wp-json/mail-tester/v1';

	const MAIL_TESTER_WP_TOKEN = 'wp_9f3a7c2e8b1d4a6f5c8e9b2d1a3f7c6e';

	/**
	 * REST payload for the dashboard sidebar.
	 *
	 * @param bool $force_refresh Skip cached results.
	 * @return array
	 */
	public static function get_payload( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return self::normalize_payload( $cached );
			}
		}

		$domain  = self::get_site_domain();
		$checks  = self::run_checks( $domain );
		$score   = self::calculate_score( $checks );
		$payload = array(
			'domain'               => $domain,
			'deliverability_score' => $score,
			'checks'               => $checks,
		);

		set_transient( self::CACHE_KEY, $payload, self::CACHE_TTL );

		return self::normalize_payload( $payload );
	}

	/**
	 * Strip deprecated checks and recalculate score when reading cached data.
	 *
	 * @param array $payload Cached or fresh payload.
	 * @return array
	 */
	private static function normalize_payload( $payload ) {
		if ( empty( $payload['checks'] ) || ! is_array( $payload['checks'] ) ) {
			if ( empty( $payload['domain'] ) ) {
				$payload['domain'] = self::get_site_domain();
			}
			return $payload;
		}

		$checks = array_values(
			array_filter(
				$payload['checks'],
				static function ( $check ) {
					return empty( $check['id'] ) || 'bimi' !== $check['id'];
				}
			)
		);

		$payload['checks']               = $checks;
		$payload['deliverability_score'] = self::calculate_score( $checks );

		if ( empty( $payload['domain'] ) || 'yourdomain.com' === $payload['domain'] ) {
			$payload['domain'] = self::get_site_domain();
		}

		return $payload;
	}

	/**
	 * Clear cached authentication results.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
		delete_transient( 'cf7apps_domain_authentication_v2' );
		delete_transient( 'cf7apps_domain_authentication' );
	}

	/**
	 * Dashboard overview stats.
	 *
	 * @return array
	 */
	public static function get_overview_stats() {
		$total_entries = 0;

		if ( class_exists( 'CF7Apps_Form_Entries' ) ) {
			$total_entries = (int) CF7Apps_Form_Entries::get_total_entries();
		}

		$cf7apps_settings = get_option( 'cf7apps_settings', array() );
		$spam_count       = 0;

		if ( isset( $cf7apps_settings['honeypot']['honeypot_count'] ) ) {
			$spam_count = (int) $cf7apps_settings['honeypot']['honeypot_count'];
		}

		return array(
			'total_entries'    => $total_entries,
			'spam_detections'  => $spam_count,
		);
	}

	/**
	 * Extract the site domain from the home URL.
	 *
	 * @return string
	 */
	private static function get_site_domain() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			$host = wp_parse_url( site_url(), PHP_URL_HOST );
		}

		if ( ! is_string( $host ) || '' === $host ) {
			return 'yourdomain.com';
		}

		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		return $host;
	}

	/**
	 * Run all authentication checks.
	 *
	 * @param string $domain Site domain.
	 * @return array<int, array<string, string>>
	 */
	private static function run_checks( $domain ) {
		$mail_tester = self::fetch_mail_tester_auth_results();

		return array(
			array(
				'id'     => 'smtp',
				'label'  => __( 'SMTP Connection', 'cf7apps' ),
				'status' => self::check_smtp_connection() ? 'pass' : 'fail',
			),
			array(
				'id'     => 'spf',
				'label'  => __( 'SPF', 'cf7apps' ),
				'status' => self::resolve_auth_check_status( 'spf', $mail_tester, $domain ),
			),
			array(
				'id'     => 'dkim',
				'label'  => __( 'DKIM', 'cf7apps' ),
				'status' => self::resolve_auth_check_status( 'dkim', $mail_tester, $domain ),
			),
			array(
				'id'     => 'dmarc',
				'label'  => __( 'DMARC', 'cf7apps' ),
				'status' => self::resolve_auth_check_status( 'dmarc', $mail_tester, $domain ),
			),
		);
	}

	/**
	 * Resolve SPF/DKIM/DMARC status via Mail Tester API, with DNS fallback.
	 *
	 * @param string               $check_id    spf|dkim|dmarc.
	 * @param array<string, mixed> $mail_tester Mail Tester response data.
	 * @param string               $domain      Site domain.
	 * @return string pass|fail
	 */
	private static function resolve_auth_check_status( $check_id, $mail_tester, $domain ) {
		if ( isset( $mail_tester[ $check_id ] ) && is_string( $mail_tester[ $check_id ] ) ) {
			return self::mail_tester_status_is_pass( $mail_tester[ $check_id ] ) ? 'pass' : 'fail';
		}

		switch ( $check_id ) {
			case 'spf':
				return self::check_spf( $domain ) ? 'pass' : 'fail';
			case 'dkim':
				return self::check_dkim( $domain ) ? 'pass' : 'fail';
			case 'dmarc':
				return self::check_dmarc( $domain ) ? 'pass' : 'fail';
			default:
				return 'fail';
		}
	}

	/**
	 * Whether a Mail Tester status value counts as passing.
	 *
	 * @param string $status Raw Mail Tester status.
	 * @return bool
	 */
	private static function mail_tester_status_is_pass( $status ) {
		return 'pass' === strtolower( trim( (string) $status ) );
	}

	/**
	 * Fetch SPF, DKIM, and DMARC results from the Post SMTP Mail Tester API.
	 *
	 * @return array<string, mixed>
	 */
	private static function fetch_mail_tester_auth_results() {
		$context = self::get_mail_tester_context();

		if ( empty( $context['test_email'] ) || empty( $context['selector'] ) ) {
			return array();
		}

		$url = add_query_arg(
			array(
				'test_email' => $context['test_email'],
				'selector'   => $context['selector'],
			),
			self::MAIL_TESTER_BASE_URL . '/email-auth-check'
		);

		$headers = array(
			'X-MT-Token-WP' => self::MAIL_TESTER_WP_TOKEN,
			'Site-URL'      => get_site_url(),
		);

		if ( ! empty( $context['api_key'] ) ) {
			$headers['API-KEY'] = $context['api_key'];
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$headers['IP'] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return array();
		}

		if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
			return $body['data'];
		}

		return $body;
	}

	/**
	 * Build Mail Tester request context from Post SMTP settings when available.
	 *
	 * @return array{test_email: string, selector: string, api_key: string}
	 */
	private static function get_mail_tester_context() {
		$postman_options = get_option( 'postman_options', array() );
		$selector        = ! empty( $postman_options['transport_type'] )
			? sanitize_key( $postman_options['transport_type'] )
			: 'smtp';

		$test_email = '';
		if ( ! empty( $postman_options['sender_email'] ) ) {
			$test_email = sanitize_email( $postman_options['sender_email'] );
		}

		if ( ! is_email( $test_email ) ) {
			$test_email = sanitize_email( get_option( 'admin_email' ) );
		}

		if ( ! is_email( $test_email ) ) {
			$domain = self::get_site_domain();
			if ( 'yourdomain.com' !== $domain ) {
				$test_email = sanitize_email( 'admin@' . $domain );
			}
		}

		return array(
			'test_email' => is_email( $test_email ) ? $test_email : '',
			'selector'   => $selector,
			'api_key'    => self::get_postman_api_key( $postman_options, $selector ),
		);
	}

	/**
	 * Resolve provider API key from Post SMTP options (when required by Mail Tester).
	 *
	 * @param array  $options  Post SMTP options.
	 * @param string $selector Transport selector.
	 * @return string
	 */
	private static function get_postman_api_key( $options, $selector ) {
		$key_map = array(
			'sendgrid_api'   => 'sendgrid_api_key',
			'sendinblue_api' => 'sendinblue_api_key',
			'mandrill_api'   => 'mandrill_api_key',
			'sparkpost_api'  => 'sparkpost_api_key',
			'smtp2go_api'    => 'smtp2go_api_key',
			'sendpulse_api'  => 'sendpulse_api_key',
			'mailgun_api'    => 'mailgun_api_key',
			'postmark_api'   => 'postmark_api_key',
			'mailjet_api'    => 'mailjet_api_key',
			'elasticemail_api' => 'elasticemail_api_key',
			'mailersend_api' => 'mailersend_api_key',
			'mailtrap_api'   => 'mailtrap_api_key',
			'resend_api'     => 'resend_api_key',
			'emailit_api'    => 'emailit_api_key',
			'maileroo_api'   => 'maileroo_api_key',
			'sweego_api'     => 'sweego_api_key',
		);

		if ( empty( $key_map[ $selector ] ) || empty( $options[ $key_map[ $selector ] ] ) ) {
			return '';
		}

		return sanitize_text_field( (string) $options[ $key_map[ $selector ] ] );
	}

	/**
	 * Whether Post SMTP is active.
	 *
	 * @return bool
	 */
	private static function is_post_smtp_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'post-smtp/postman-smtp.php' )
			|| is_plugin_active( 'post-smtp/post-smtp.php' );
	}

	/**
	 * Calculate deliverability score from check results.
	 *
	 * @param array<int, array<string, string>> $checks Check results.
	 * @return int
	 */
	private static function calculate_score( $checks ) {
		if ( empty( $checks ) ) {
			return 0;
		}

		$passed = 0;

		foreach ( $checks as $check ) {
			if ( isset( $check['status'] ) && 'pass' === $check['status'] ) {
				++$passed;
			}
		}

		return (int) round( ( $passed / count( $checks ) ) * 100 );
	}

	/**
	 * Whether an SMTP plugin appears configured.
	 *
	 * @return bool
	 */
	private static function check_smtp_connection() {
		if ( self::is_post_smtp_active() ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$smtp_plugins = array(
			'wp-mail-smtp/wp_mail_smtp.php',
			'easy-wp-smtp/easy-wp-smtp.php',
			'fluent-smtp/fluent-smtp.php',
		);

		foreach ( $smtp_plugins as $plugin_file ) {
			if ( is_plugin_active( $plugin_file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check SPF TXT record.
	 *
	 * @param string $domain Site domain.
	 * @return bool
	 */
	private static function check_spf( $domain ) {
		return self::txt_record_contains( $domain, 'v=spf1' );
	}

	/**
	 * Check DKIM record using common selectors.
	 *
	 * @param string $domain Site domain.
	 * @return bool
	 */
	private static function check_dkim( $domain ) {
		$selectors = array( 'default', 'google', 'selector1', 'selector2', 'k1', 'mail', 'smtp', 'dkim' );

		foreach ( $selectors as $selector ) {
			if ( self::has_dns_records( $selector . '._domainkey.' . $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check DMARC TXT record.
	 *
	 * @param string $domain Site domain.
	 * @return bool
	 */
	private static function check_dmarc( $domain ) {
		return self::txt_record_contains( '_dmarc.' . $domain, 'v=DMARC1' );
	}

	/**
	 * Whether a host has any DNS records.
	 *
	 * @param string $host DNS host.
	 * @return bool
	 */
	private static function has_dns_records( $host ) {
		if ( ! function_exists( 'dns_get_record' ) ) {
			return false;
		}

		$records = @dns_get_record( $host, DNS_ALL ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		return is_array( $records ) && ! empty( $records );
	}

	/**
	 * Whether TXT records contain a needle string.
	 *
	 * @param string $host   DNS host.
	 * @param string $needle Value to search for.
	 * @return bool
	 */
	private static function txt_record_contains( $host, $needle ) {
		if ( ! function_exists( 'dns_get_record' ) ) {
			return false;
		}

		$records = @dns_get_record( $host, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! is_array( $records ) ) {
			return false;
		}

		foreach ( $records as $record ) {
			if ( empty( $record['txt'] ) ) {
				continue;
			}

			if ( false !== stripos( (string) $record['txt'], $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
