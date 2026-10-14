<?php

/**

 * Weekly Summary Report app.

 *

 * @package CF7Apps

 * @since   3.8.0

 */



defined( 'ABSPATH' ) || exit;



if ( ! class_exists( 'CF7Apps_Weekly_Summary_App' ) && class_exists( 'CF7Apps_App' ) ) :



	/**

	 * Sends weekly entry summary emails for Contact Form 7 submissions.

	 */

	class CF7Apps_Weekly_Summary_App extends CF7Apps_App {



		const CRON_HOOK = 'cf7apps_weekly_summary_report';

		const REPORTS_SENT_OPTION = 'cf7apps_weekly_summary_reports_sent';

		/**
		 * Inline image attachments for the current email send.
		 *
		 * @var array<string, string>
		 */
		private $email_embeds = array();

		/**

		 * Constructor.

		 */

		public function __construct() {

			$this->id                 = 'weekly-summary';

			$this->priority           = 0;

			$this->title              = __( 'Weekly Summary Report', 'cf7apps' );

			$this->description        = __( 'Generate weekly summary reports of entries and email them to users to keep them informed.', 'cf7apps' );

			$this->icon               = plugin_dir_url( __FILE__ ) . 'assets/images/logo.svg';

			$this->has_admin_settings = true;

			$this->is_pro             = false;

			$this->by_default_enabled = false;

			$this->documentation_url  = 'https://cf7apps.com/docs/general/weekly-summary-report/';

			$this->parent_menu        = 'general';

			$this->setting_tabs       = array();



			add_action( self::CRON_HOOK, array( $this, 'send_weekly_report' ) );

			add_action( 'init', array( $this, 'maybe_schedule_cron' ) );

			add_action( 'update_option_cf7apps_settings', array( $this, 'handle_settings_updated' ), 10, 2 );

		}



		/**

		 * Admin settings schema.

		 *

		 * @return array

		 */

		public function admin_settings() {

			return array(

				'general' => array(

					'fields' => array(

						'is_enabled'             => array(

							'type'    => 'checkbox',

							'default' => false,

						),

						'additional_recipients' => array(

							'type'        => 'text',

							'default'     => '',

							'placeholder' => __( 'email1@example.com, email2@example.com', 'cf7apps' ),

						),

						'template'               => 'weeklySummaryAdmin',

					),

				),

			);

		}



		/**

		 * Reschedule cron when weekly summary settings change.

		 *

		 * @param mixed $old_value Previous option value.

		 * @param mixed $value     Updated option value.

		 */

		public function handle_settings_updated( $old_value, $value ) {

			if ( ! is_array( $value ) || ! isset( $value[ $this->id ] ) ) {

				return;

			}

			$old_enabled = is_array( $old_value ) && isset( $old_value[ $this->id ]['is_enabled'] )
				? (bool) $old_value[ $this->id ]['is_enabled']
				: false;
			$new_enabled = ! empty( $value[ $this->id ]['is_enabled'] );

			if ( $new_enabled && ! $old_enabled ) {
				delete_option( self::REPORTS_SENT_OPTION );
			}

			$this->schedule_cron();

		}



		/**

		 * Ensure cron is scheduled when the app is enabled.

		 */

		public function maybe_schedule_cron() {

			if ( ! $this->get_option( 'is_enabled' ) ) {
				$this->clear_cron();
				return;
			}

			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( $this->get_next_monday_timestamp(), 'weekly', self::CRON_HOOK );
			}

		}



		/**

		 * Schedule or clear the weekly cron event.

		 */

		public function schedule_cron() {

			$this->clear_cron();



			if ( ! $this->get_option( 'is_enabled' ) ) {

				return;

			}



			wp_schedule_event( $this->get_next_monday_timestamp(), 'weekly', self::CRON_HOOK );

		}



		/**

		 * Get the next Monday 9:00 AM timestamp in the site timezone.

		 *

		 * @return int

		 */

		private function get_next_monday_timestamp() {

			$timezone = wp_timezone();

			$now      = new DateTime( 'now', $timezone );

			$next     = new DateTime( 'next monday 9:00', $timezone );



			if ( 'Mon' === $now->format( 'D' ) && (int) $now->format( 'G' ) < 9 ) {

				$next = new DateTime( 'today 9:00', $timezone );

			}



			return $next->getTimestamp();

		}



		/**

		 * Clear scheduled cron event.

		 */

		private function clear_cron() {

			$timestamp = wp_next_scheduled( self::CRON_HOOK );



			while ( $timestamp ) {

				wp_unschedule_event( $timestamp, self::CRON_HOOK );

				$timestamp = wp_next_scheduled( self::CRON_HOOK );

			}

		}



		/**

		 * Send the weekly summary email.

		 */

		public function send_weekly_report() {

			if ( ! $this->get_option( 'is_enabled' ) ) {

				return;

			}

			$this->ensure_form_entries_model_loaded();

			$stats       = $this->get_weekly_entry_stats();

			$recipients  = $this->get_recipients();

			$week_range  = $stats['week_range'];

			$subject     = sprintf(

				/* translators: %s: reporting week range */

				__( 'Your Weekly Contact Form 7 Summary Report — %s', 'cf7apps' ),

				$week_range

			);

			$this->email_embeds = array();
			$message            = $this->build_email_body( $stats );
			$headers            = array( 'Content-Type: text/html; charset=UTF-8' );

			if ( ! empty( $this->email_embeds ) ) {
				add_action( 'phpmailer_init', array( $this, 'attach_email_embeds' ) );
			}

			foreach ( $recipients as $recipient ) {
				wp_mail( $recipient, $subject, $message, $headers );
			}

			$this->mark_weekly_report_sent();

			if ( ! empty( $this->email_embeds ) ) {
				remove_action( 'phpmailer_init', array( $this, 'attach_email_embeds' ) );
			}
			$this->email_embeds = array();
		}

		/**
		 * Whether spam stats should appear in the weekly summary email.
		 *
		 * The first report after enabling weekly summary omits spam; subsequent reports include it.
		 *
		 * @since 3.8.0
		 *
		 * @return bool
		 */
		private function should_include_spam_in_report() {
			$sent_count = (int) get_option( self::REPORTS_SENT_OPTION, 0 );

			return $sent_count >= 1;
		}

		/**
		 * Record that a weekly summary report was sent.
		 *
		 * @since 3.8.0
		 */
		private function mark_weekly_report_sent() {
			$sent_count = (int) get_option( self::REPORTS_SENT_OPTION, 0 );
			update_option( self::REPORTS_SENT_OPTION, $sent_count + 1, false );
		}

		/**
		 * Attach inline images referenced via cid: URLs in the email body.
		 *
		 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer instance.
		 */
		public function attach_email_embeds( $phpmailer ) {
			foreach ( $this->email_embeds as $cid => $file_path ) {
				if ( is_readable( $file_path ) ) {
					$phpmailer->addEmbeddedImage( $file_path, $cid );
				}
			}
		}



		/**

		 * Get email recipients.

		 *

		 * @return array

		 */

		private function get_recipients() {

			$recipients = array();



			$admin_email = get_option( 'admin_email' );

			if ( is_email( $admin_email ) ) {

				$recipients[] = $admin_email;

			}



			$additional = $this->get_option( 'additional_recipients' );

			if ( is_string( $additional ) && '' !== trim( $additional ) ) {

				$parts = preg_split( '/[\s,;]+/', $additional );

				foreach ( $parts as $email ) {

					$email = sanitize_email( trim( $email ) );

					if ( is_email( $email ) ) {

						$recipients[] = $email;

					}

				}

			}



			$recipients = array_values( array_unique( array_filter( $recipients ) ) );



			/**

			 * Filter weekly summary email recipients.

			 *

			 * @since 3.8.0

			 *

			 * @param array $recipients Email addresses.

			 */

			$recipients = apply_filters( 'cf7apps_weekly_summary_recipients', $recipients );



			$legacy_recipient = apply_filters( 'cf7apps_weekly_summary_recipient', '' );

			if ( is_email( $legacy_recipient ) && ! in_array( $legacy_recipient, $recipients, true ) ) {

				$recipients[] = $legacy_recipient;

			}



			return $recipients;

		}



		/**

		 * Reporting range: Monday 00:00 through yesterday 23:59:59 (site timezone).
		 *
		 * Monday cron runs cover the completed Mon–Sun week that ended on Sunday.
		 * Mid-week manual runs include the current week through yesterday.

		 *

		 * @return array{start:int,end:int,label:string}

		 */

		private function get_reporting_week_range() {

			$timezone = wp_timezone();
			$now      = new DateTime( 'now', $timezone );
			$end      = new DateTime( 'today 23:59:59', $timezone );
			$start    = new DateTime( 'monday this week', $timezone );
			$start->setTime( 0, 0, 0 );

			// Monday morning (before noon): email the completed previous Mon–Sun week.
			if ( 1 === (int) $now->format( 'N' ) && (int) $now->format( 'G' ) < 12 ) {
				$end = new DateTime( 'last sunday 23:59:59', $timezone );
				$start = clone $end;
				$start->modify( 'monday this week' );
				$start->setTime( 0, 0, 0 );
			}

			return array(

				'start' => $start->getTimestamp(),

				'end'   => $end->getTimestamp(),

				'label' => wp_date( 'M j', $start->getTimestamp() ) . ' – ' . wp_date( 'M j, Y', $end->getTimestamp() ),

			);

		}



		/**

		 * Count published Contact Form 7 forms.

		 *

		 * @return int

		 */

		private function get_total_active_forms() {

			$form_ids = get_posts(

				array(

					'post_type'      => 'wpcf7_contact_form',

					'post_status'    => 'publish',

					'posts_per_page' => -1,

					'fields'         => 'ids',

					'no_found_rows'  => true,

				)

			);



			return is_array( $form_ids ) ? count( $form_ids ) : 0;

		}



		/**
		 * Load CF7Apps_Form_Entries so weekly stats can query wp_cf7apps_form_entries.
		 *
		 * @return void
		 */
		private function ensure_form_entries_model_loaded() {
			if ( class_exists( 'CF7Apps_Form_Entries' ) ) {
				return;
			}

			$entries_file = CF7APPS_PLUGIN_DIR . '/includes/apps/cf7-entries/includes/cf7-form-entries.php';

			if ( file_exists( $entries_file ) ) {
				require_once $entries_file;
			}
		}



		/**

		 * Aggregate entry counts for the reporting week.

		 *

		 * Data source: wp_cf7apps_form_entries via CF7Apps_Form_Entries.

		 *

		 * @return array

		 */

		private function get_weekly_entry_stats() {
			$this->ensure_form_entries_model_loaded();

			$week        = $this->get_reporting_week_range();
			$total_forms = $this->get_total_active_forms();
			$total       = 0;
			$by_form     = array();
			$has_entries = false;

			if ( class_exists( 'CF7Apps_Form_Entries' ) ) {
				$table_stats = CF7Apps_Form_Entries::get_weekly_stats_for_range( $week['start'], $week['end'] );
				$total       = (int) $table_stats['total'];
				$by_form     = $table_stats['by_form'];
				$has_entries = ! empty( $table_stats['available'] );
			}

			$include_spam = $this->should_include_spam_in_report();

			return array(
				'total'             => $total,
				'total_spam'        => $include_spam ? cf7apps_get_honeypot_weekly_spam_count( $week['start'] ) : 0,
				'include_spam'      => $include_spam,
				'total_forms'       => $total_forms,
				'by_form'           => $by_form,
				'week_range'        => $week['label'],
				'entries_available' => $has_entries,
			);
		}



		/**

		 * Build HTML email body for the weekly report.

		 *

		 * @param array $stats Entry statistics.

		 * @return string

		 */

		/**
		 * Whether email images should be embedded via cid: instead of public URLs.
		 *
		 * CID embedding is only needed on local/private hosts where image URLs
		 * are not reachable from mail clients. On live sites, public HTTPS URLs
		 * are more reliable (especially with SMTP/API mail plugins).
		 *
		 * @since 3.8.0
		 *
		 * @return bool
		 */
		private function should_embed_email_images() {
			$force_embed = apply_filters( 'cf7apps_weekly_summary_embed_email_images', null );

			if ( null !== $force_embed ) {
				return (bool) $force_embed;
			}

			if ( function_exists( 'wp_get_environment_type' ) ) {
				$environment = wp_get_environment_type();

				if ( in_array( $environment, array( 'local', 'development' ), true ) ) {
					return true;
				}

				if ( 'production' === $environment ) {
					return false;
				}
			}

			$host = wp_parse_url( home_url(), PHP_URL_HOST );

			if ( empty( $host ) ) {
				return false;
			}

			$host = strtolower( (string) $host );

			if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
				return true;
			}

			if ( preg_match( '/\.(local|test|localhost)$/', $host ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Resolve an email image source.
		 *
		 * @since 3.8.0
		 *
		 * @param string $relative_path Path relative to this app directory.
		 * @param string $cid           Content ID for embedded images.
		 * @return string Image source URL, cid: reference, or empty string.
		 */
		private function get_email_image_src( $relative_path, $cid ) {
			$relative_path = ltrim( (string) $relative_path, '/' );
			$file_path     = __DIR__ . '/' . $relative_path;

			if ( ! is_readable( $file_path ) ) {
				return '';
			}

			if ( $this->should_embed_email_images() ) {
				$this->email_embeds[ $cid ] = $file_path;

				return 'cid:' . $cid;
			}

			return plugin_dir_url( __FILE__ ) . $relative_path;
		}

		private function build_email_body( $stats ) {
			$admin_email       = get_option( 'admin_email' );
			$admin_user        = is_email( $admin_email ) ? get_user_by( 'email', $admin_email ) : false;
			$user_name         = $admin_user ? $admin_user->display_name : __( 'there', 'cf7apps' );
			$logo_url          = $this->get_email_image_src( 'assets/images/email/cf7apps-logo.png', 'cf7apps-logo' );
			$page_icon_url     = $this->get_email_image_src( 'assets/images/email/page-icon.png', 'cf7apps-page-icon' );
			$spam_icon_url     = $this->get_email_image_src( 'assets/images/email/spam-icon.png', 'cf7apps-spam-icon' );
			$dashboard_url     = admin_url( 'admin.php?page=cf7apps' );
			$total_forms       = isset( $stats['total_forms'] ) ? (int) $stats['total_forms'] : 0;
			$total_entries     = isset( $stats['total'] ) ? (int) $stats['total'] : 0;
			$total_spam        = isset( $stats['total_spam'] ) ? (int) $stats['total_spam'] : 0;
			$show_spam         = ! empty( $stats['include_spam'] );
			$week_range        = isset( $stats['week_range'] ) ? (string) $stats['week_range'] : '';
			$by_form           = ( isset( $stats['by_form'] ) && is_array( $stats['by_form'] ) ) ? $stats['by_form'] : array();
			$entries_available = ! empty( $stats['entries_available'] );
			$fonts_url         = defined( 'CF7APPS_PLUGIN' ) ? plugins_url( 'assets/fonts/degular-14/', CF7APPS_PLUGIN ) : '';

			ob_start();
			include __DIR__ . '/templates/email-report.php';
			return ob_get_clean();
		}

	}



	/**

	 * Register Weekly Summary app.

	 *

	 * @param array $apps Registered apps.

	 * @return array

	 */

	function cf7apps_register_weekly_summary( $apps ) {

		$apps[] = 'CF7Apps_Weekly_Summary_App';



		return $apps;

	}



	add_filter( 'cf7apps_apps', 'cf7apps_register_weekly_summary' );



endif;


