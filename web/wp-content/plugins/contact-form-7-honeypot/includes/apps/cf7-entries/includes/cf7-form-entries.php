<?php
/**
 * CF7 Form Entries Class
 *
 * @package Contact Form 7 Honeypot
 * @since 3.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CF7Apps_Form_Entries' ) ) :
	/**
	 * CF7Apps_Form_Entries class
	 *
	 * @since 3.1.0
	 */
	class CF7Apps_Form_Entries {
		/**
		 * The ID of the form entries.
		 *
		 * @since 3.1.0
		 * @var int $id The ID of the form entries.
		 */
		public $id;

		/**
		 * The name of the form.
		 *
		 * @since 3.1.0
		 * @var int $form_id The ID of the form.
		 */
		public $form_id;

		/**
		 * The name of the form.
		 *
		 * @since 3.1.0
		 * @var string $form_name The name of the form.
		 */
		public $form_name;

		/**
		 * The email address of the user who submitted the form.
		 *
		 * @since 3.1.0
		 * @var string $email The email address of the user who submitted the form.
		 */
		public $email;

		/**
		 * The date and time when the form was submitted.
		 *
		 * @since 3.1.0
		 * @var string $date_time The date and time when the form was submitted.
		 */
		public $date_time;

		/**
		 * The data submitted in the form.
		 *
		 * @since 3.1.0
		 * @var array $data The data submitted in the form.
		 */
		public $data;

		/**
		 * Payment metadata attached for Entries API responses.
		 *
		 * @since 3.8.0
		 * @var array|null $payment Payment details (gateway status, log link, etc.).
		 */
		public $payment;

		/**
		 * Constructor for the CF7Apps_Form_Entries class.
		 *
		 * @since 3.1.0
		 */
		public function __construct() { }

		/**
		 * Save the form entry to the database.
		 *
		 * @since 3.1.0
		 * @return int
		 */
		public function save() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			// If the table doesn't exist, avoid fatal errors.
			if ( ! self::table_exists( $table_name ) ) {
				return 0;
			}

			$data = array(
				'form_id'    => $this->form_id,
				'form_name'  => $this->form_name,
				'email'      => $this->email,
				'date_time'  => $this->date_time,
				'data'       => maybe_serialize( $this->data ),
			);

			if ( ! empty( $this->id ) ) {
				$wpdb->update( $table_name, $data, array( 'id' => $this->id ) );
			} else {
				$wpdb->insert( $table_name, $data );

				$this->id = $wpdb->insert_id;
			}

			return $this->id;
		}

		/**
		 * Get all form entries from the database.
		 *
		 * @since 3.1.0
		 * @return self[]
		 */
		public static function get_all_entries( $limit = 10, $offset = 0, $form_id = 0, $search = '', $start_date = 0, $end_date = 0, $args = array() ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			// Bail out gracefully if the table is missing.
			if ( ! self::table_exists( $table_name ) ) {
				return array();
			}
			$orderby    = isset( $args['orderby'] ) ? $args['orderby'] : 'id';
			$order      = isset( $args['order'] ) ? $args['order'] : 'DESC';
			$limit      = absint( $limit );
			$offset     = absint( $offset );
			$search     = trim( $search );

			$sql = "SELECT * FROM %i";

			$sql .= ' WHERE 1=1 ';

			if ( ! empty( $form_id ) ) {
				$sql .= $wpdb->prepare( ' AND form_id = %d', $form_id );
			}

			if ( ! empty( $search ) ) {
				$search = '%' . $wpdb->esc_like( $search ) . '%';
				$sql    .= $wpdb->prepare( ' AND (form_name LIKE %s OR email LIKE %s)', $search, $search );
			}

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$sql .= $wpdb->prepare( ' AND `date_time` BETWEEN %d AND %d', $start_date, $end_date );
			} elseif ( ! empty( $start_date ) ) {
				$sql .= $wpdb->prepare( ' AND `date_time` >= %d', $start_date );
			} elseif ( ! empty( $end_date ) ) {
				$sql .= $wpdb->prepare( ' AND `date_time` <= %d', $end_date );
			}

			$sql .= $wpdb->prepare( ' ORDER BY %i ' . $order, $orderby );

			if ( $limit > 0 ) {
				$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
			}

			$sql = $wpdb->prepare( $sql, $table_name );

			$results    = $wpdb->get_results(
				$sql,
				ARRAY_A
			);

			if ( ! empty( $results ) ) {

				$results = array_map( array( self::class, 'create_instance' ), $results );
			}

			return $results;
		}

		public static function get_total_entries( $form_id = 0, $search = '', $start_date = 0, $end_date = 0 ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			// If the table doesn't exist yet, report zero entries without erroring.
			if ( ! self::table_exists( $table_name ) ) {
				return 0;
			}

			$sql = "SELECT COUNT(*) FROM %i WHERE 1=1";
			if ( ! empty( $form_id ) ) {
				$sql .= $wpdb->prepare( ' AND form_id = %d', $form_id );
			}
			if ( ! empty( $search ) ) {
				$search = '%' . $wpdb->esc_like( $search ) . '%';
				$sql    .= $wpdb->prepare( ' AND (form_name LIKE %s OR email LIKE %s)', $search, $search );
			}

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$sql .= $wpdb->prepare( ' AND CAST(date_time AS UNSIGNED) BETWEEN %d AND %d', $start_date, $end_date );
			} elseif ( ! empty( $start_date ) ) {
				$sql .= $wpdb->prepare( ' AND CAST(date_time AS UNSIGNED) >= %d', $start_date );
			} elseif ( ! empty( $end_date ) ) {
				$sql .= $wpdb->prepare( ' AND CAST(date_time AS UNSIGNED) <= %d', $end_date );
			}

			$sql = $wpdb->prepare( $sql, $table_name );

			$count      = $wpdb->get_var( $sql );

			return absint( $count );
		}

		/**
		 * Count entries grouped by form name for a date range.
		 *
		 * @since 3.8.0
		 *
		 * @param int $start_date Unix timestamp.
		 * @param int $end_date   Unix timestamp.
		 * @return array<int,array{form_name:string,entry_count:string}>
		 */
		public static function get_entry_counts_by_form( $start_date = 0, $end_date = 0 ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			if ( ! self::table_exists( $table_name ) ) {
				return array();
			}

			$sql = "SELECT form_name, COUNT(*) AS entry_count FROM %i WHERE 1=1";

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$sql .= $wpdb->prepare( ' AND CAST(date_time AS UNSIGNED) BETWEEN %d AND %d', $start_date, $end_date );
			} elseif ( ! empty( $start_date ) ) {
				$sql .= $wpdb->prepare( ' AND CAST(date_time AS UNSIGNED) >= %d', $start_date );
			} elseif ( ! empty( $end_date ) ) {
				$sql .= $wpdb->prepare( ' AND CAST(date_time AS UNSIGNED) <= %d', $end_date );
			}

			$sql .= ' GROUP BY form_name ORDER BY entry_count DESC';
			$sql  = $wpdb->prepare( $sql, $table_name );

			$rows = $wpdb->get_results( $sql, ARRAY_A );

			return is_array( $rows ) ? $rows : array();
		}

		/**
		 * Entries table name (`wp_cf7apps_form_entries`).
		 *
		 * @since 3.8.0
		 * @return string
		 */
		public static function get_table_name() {
			global $wpdb;

			return $wpdb->prefix . 'cf7apps_form_entries';
		}

		/**
		 * Whether the entries table exists.
		 *
		 * @since 3.8.0
		 * @return bool
		 */
		public static function entries_table_exists() {
			return self::table_exists( self::get_table_name() );
		}

		/**
		 * Weekly summary stats from wp_cf7apps_form_entries for a date range.
		 *
		 * @since 3.8.0
		 *
		 * @param int $start_date Unix timestamp (inclusive).
		 * @param int $end_date   Unix timestamp (inclusive).
		 * @return array{total:int,by_form:array,available:bool}
		 */
		public static function get_weekly_stats_for_range( $start_date, $end_date ) {
			if ( ! self::entries_table_exists() ) {
				return array(
					'total'     => 0,
					'by_form'   => array(),
					'available' => false,
				);
			}

			return array(
				'total'     => self::get_total_entries( 0, '', $start_date, $end_date ),
				'by_form'   => self::get_entry_counts_by_form( $start_date, $end_date ),
				'available' => true,
			);
		}

		/**
		 * Get a form entry by its ID.
		 *
		 * @since 3.1.0
		 * @param int $id The ID of the form entry to retrieve.
		 *
		 * @return self|false
		 */
		public static function get_entry_by_id( $id ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			if ( ! self::table_exists( $table_name ) ) {
				return false;
			}
			$result     = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table_name, $id ), ARRAY_A );

			if ( ! empty( $result ) ) {
				return self::create_instance( $result );
			}

			return false;
		}

		/**
		 * Create an instance of the CF7Apps_Form_Entries class from an array of data.
		 *
		 * @since 3.1.0
		 * @param array $data The data to create the instance from.
		 *
		 * @return self
		 */
		public static function create_instance( $data ) {
			$instance = new self();
			$instance->id        = $data['id'];
			$instance->form_id   = $data['form_id'];
			$instance->form_name = $data['form_name'];
			$instance->email     = $data['email'];
			$instance->date_time = date( 'F j, Y h:i a', $data['date_time'] );
			$instance->data      = maybe_unserialize( $data['data'] );

			return $instance;
		}

		/**
		 * Delete the form entry from the database.
		 *
		 * @since 3.1.0
		 * @return true
		 */
		public function destroy() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			if ( ! self::table_exists( $table_name ) ) {
				return true;
			}

			if ( ! empty( $this->id ) ) {
				$wpdb->delete( $table_name, array( 'id' => $this->id ) );
			}

			$this->id        = null;
			$this->form_id   = null;
			$this->form_name = null;
			$this->email     = null;
			$this->date_time = null;
			$this->data      = array();

			return true;
		}

		/**
		 * Delete a single entry by ID.
		 *
		 * @since 3.1.0
		 *
		 * @param int $id Entry ID.
		 * @return bool
		 */
		public static function delete_entry( $id ) {
			$entry = self::get_entry_by_id( $id );

			if ( $entry ) {
				return $entry->destroy();
			}

			return false;
		}

		/**
		 * Delete multiple entries by IDs.
		 *
		 * @since 3.1.0
		 *
		 * @param int|int[] $ids Entry ID or array of IDs.
		 * @return bool
		 */
		public static function delete_entries( $ids ) {
			if ( ! is_array( $ids ) ) {
				$ids = array( $ids );
			}

			foreach ( $ids as $id ) {
				self::delete_entry( $id );
			}

			return true;
		}

		/**
		 * Get multiple entries by IDs.
		 *
		 * @since 3.8.0
		 * @param int[] $ids Entry IDs.
		 * @return self[]
		 */
		public static function get_entries_by_ids( $ids ) {
			global $wpdb;

			$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
			if ( empty( $ids ) ) {
				return array();
			}

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			if ( ! self::table_exists( $table_name ) ) {
				return array();
			}

			$in_clause = implode( ',', $ids );
			$results   = $wpdb->get_results(
				"SELECT * FROM {$table_name} WHERE id IN ({$in_clause}) ORDER BY id DESC",
				ARRAY_A
			);

			if ( empty( $results ) ) {
				return array();
			}

			return array_map( array( self::class, 'create_instance' ), $results );
		}

		/**
		 * Build a CSV string from entry records.
		 *
		 * @since 3.8.0
		 * @param self[] $entries Entry objects.
		 * @return string
		 */
		public static function entries_to_csv( array $entries ) {
			if ( empty( $entries ) ) {
				return '';
			}

			$field_keys = array();
			foreach ( $entries as $entry ) {
				if ( ! empty( $entry->data ) && is_array( $entry->data ) ) {
					$field_keys = array_merge( $field_keys, array_keys( $entry->data ) );
				}
			}
			$field_keys = array_values( array_unique( $field_keys ) );
			sort( $field_keys );

			$headers = array_merge(
				array( 'ID', 'Form Name', 'Email', 'Date & Time' ),
				$field_keys
			);

			$rows = array( $headers );

			foreach ( $entries as $entry ) {
				$row = array(
					(string) $entry->id,
					(string) $entry->form_name,
					(string) $entry->email,
					(string) $entry->date_time,
				);

				foreach ( $field_keys as $field_key ) {
					$value = '';
					if ( ! empty( $entry->data ) && is_array( $entry->data ) && array_key_exists( $field_key, $entry->data ) ) {
						$value = self::flatten_field_value( $entry->data[ $field_key ] );
					}
					$row[] = $value;
				}

				$rows[] = $row;
			}

			$output = fopen( 'php://temp', 'r+' );
			if ( false === $output ) {
				return '';
			}

			// UTF-8 BOM helps Excel open non-ASCII characters correctly.
			fwrite( $output, "\xEF\xBB\xBF" );

			foreach ( $rows as $row ) {
				fputcsv( $output, $row, ',', '"', '\\' );
			}

			rewind( $output );
			$csv = stream_get_contents( $output );
			fclose( $output );

			return false === $csv ? '' : $csv;
		}

		/**
		 * Build export filename: {form-name}-entries-{date}.csv
		 *
		 * @since 3.8.0
		 * @param self[] $entries  Entry objects.
		 * @param int    $form_id  Optional filtered form ID.
		 * @return string
		 */
		public static function get_export_filename( array $entries, $form_id = 0 ) {
			$form_name = '';
			$form_id   = absint( $form_id );

			if ( $form_id ) {
				$form_name = (string) get_the_title( $form_id );
			} elseif ( ! empty( $entries ) ) {
				$names = array_unique(
					array_filter(
						array_map(
							static function ( $entry ) {
								return is_object( $entry ) ? (string) $entry->form_name : '';
							},
							$entries
						)
					)
				);

				if ( 1 === count( $names ) ) {
					$form_name = (string) reset( $names );
				}
			}

			if ( '' === $form_name ) {
				return 'entries-' . gmdate( 'Y-m-d' ) . '.csv';
			}

			$slug = sanitize_title( $form_name );
			if ( '' === $slug ) {
				return 'entries-' . gmdate( 'Y-m-d' ) . '.csv';
			}

			return $slug . '-entries-' . gmdate( 'Y-m-d' ) . '.csv';
		}

		/**
		 * Normalize a stored field value for CSV output.
		 *
		 * @since 3.8.0
		 * @param mixed $value Field value.
		 * @return string
		 */
		private static function flatten_field_value( $value ) {
			if ( is_array( $value ) ) {
				$parts = array_map(
					static function ( $item ) {
						return self::flatten_field_value( $item );
					},
					$value
				);

				return implode( ', ', array_filter( $parts, 'strlen' ) );
			}

			if ( is_bool( $value ) ) {
				return $value ? '1' : '0';
			}

			if ( null === $value ) {
				return '';
			}

			return (string) $value;
		}

		/**
		 * Delete all entries for a specific Contact Form 7 form.
		 *
		 * @since 3.4.0
		 *
		 * @param int $form_id Contact Form 7 form ID.
		 * @return void
		 */
		public static function delete_entries_by_form_id( $form_id ) {
			global $wpdb;

			$form_id = absint( $form_id );
			if ( ! $form_id ) {
				return;
			}

			$table_name = $wpdb->prefix . 'cf7apps_form_entries';

			if ( ! self::table_exists( $table_name ) ) {
				return;
			}

			$wpdb->delete(
				$table_name,
				array( 'form_id' => $form_id ),
				array( '%d' )
			);
		}

		/**
		 * Helper: check whether the entries table exists.
		 *
		 * @since 3.4.0
		 *
		 * @param string $table_name Fully-qualified table name.
		 * @return bool
		 */
		private static function table_exists( $table_name ) {
			global $wpdb;

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table_name
				)
			);

			return $exists === $table_name;
		}
	}
endif;
