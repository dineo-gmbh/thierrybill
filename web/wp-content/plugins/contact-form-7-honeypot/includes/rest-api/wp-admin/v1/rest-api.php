<?php

class CF7Apps_Rest_API_WP_Admin_V1 extends CF7Apps_Base_Rest_API {

    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register Routes | Action Callback
     * 
     * @since 3.0.0
     */
    public function register_routes() {
        register_rest_route( 'cf7apps/v1', '/get-menu-items', array(
            'methods'               => 'GET',
            'callback'              => array( $this, 'get_menu_items' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

        register_rest_route( 'cf7apps/v1', '/get-apps(?:/(?P<id>[\w-]+))?', array(
            'methods'               => 'GET',
            'callback'              => array( $this, 'get_apps' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

        register_rest_route( 'cf7apps/v1', '/save-app-settings', array(
            'methods'               => 'POST',
            'callback'              => array( $this, 'save_app_settings' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

        register_rest_route( 'cf7apps/v1', '/get-app-settings', array(
            'methods'               => 'POST',
            'callback'              => array( $this, 'get_app_settings' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

        register_rest_route( 'cf7apps/v1', '/get-cf7-forms', array(
            'methods'               => 'GET',
            'callback'              => array( $this, 'get_cf7_forms' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

        register_rest_route( 'cf7apps/v1', '/has-migrated', array(
            'methods'               => 'GET',
            'callback'              => array( $this, 'has_migrated' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

        register_rest_route( 'cf7apps/v1', '/migrate', array(
            'methods'               => 'POST',
            'callback'              => array( $this, 'migrate' ),
            'permission_callback'   => array( $this, 'authenticate' ),
        ) );

		register_rest_route(
			'cf7apps/v1',
			'/get-cf7-entries',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cf7_entries' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);

		register_rest_route(
			'cf7apps/v1',
			'/delete-cf7-entries',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'delete_cf7_entries' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);

		register_rest_route(
			'cf7apps/v1',
			'/export-cf7-entries',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_cf7_entries' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);

		register_rest_route(
			'cf7apps/v1',
			'/get-all-cf7-forms',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_cf7_forms' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);

		register_rest_route(
			'cf7apps/v1',
			'/get-payment-logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payment_logs' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);

		register_rest_route(
			'cf7apps/v1',
			'/delete-payment-logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'delete_payment_logs' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);

        register_rest_route(
            'cf7apps/v1',
            '/spam-count',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_spam_count' ),
                'permission_callback' => array( $this, 'authenticate' ),
            )
        );

        register_rest_route(
            'cf7apps/v1',
            '/recommended-plugins',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_recommended_plugins' ),
                'permission_callback' => array( $this, 'authenticate' ),
            )
        );

        register_rest_route(
            'cf7apps/v1',
            '/recommended-plugins/install-activate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'install_activate_recommended_plugin' ),
                'permission_callback' => array( $this, 'authenticate' ),
            )
        );

        register_rest_route(
            'cf7apps/v1',
            '/dashboard-overview',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_dashboard_overview' ),
                'permission_callback' => array( $this, 'authenticate' ),
            )
        );

        register_rest_route(
            'cf7apps/v1',
            '/domain-authentication',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_domain_authentication' ),
                'permission_callback' => array( $this, 'authenticate' ),
                'args'                => array(
                    'refresh' => array(
                        'type'              => 'boolean',
                        'default'           => false,
                        'sanitize_callback' => 'rest_sanitize_boolean',
                    ),
                ),
            )
        );
    }

    /**
     * Get Menu Items | Callback
     * 
     * @since 3.0.0
     */
    public function get_menu_items( $request ) {
        $menu_for        = $request->get_param( 'menu-for' );
        $registered_apps = $this->get_registered_apps();
        $menu_items = array();

        // First, collect all apps with their priorities
        $apps_with_priority = array();
        
        foreach( $registered_apps as $app ) {
            if( class_exists( $app ) ) {
                $app_instance = new $app;

                if ( 'internal-settings' === $menu_for ) {
                    if ( $app_instance->has_internal_settings ) {
                        $apps_with_priority[] = array(
                            'id'          => $app_instance->id,
                            'title'       => $app_instance->title,
                            'parent_menu' => $app_instance->parent_menu,
                            'priority'    => property_exists($app_instance, 'priority') ? $app_instance->priority : 999
                        );
                    }
                } else {
                    if ( $app_instance->has_admin_settings ) {
                        $apps_with_priority[] = array(
                            'id'          => $app_instance->id,
                            'title'       => $app_instance->title,
                            'parent_menu' => $app_instance->parent_menu,
                            'priority'    => property_exists($app_instance, 'priority') ? $app_instance->priority : 999
                        );
                    }
                }
            }
        }

        // Sort by priority
        usort($apps_with_priority, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Now organize by parent menu while maintaining the sorted order
        foreach($apps_with_priority as $app) {
            $menu_items[$app['parent_menu']][$app['id']] = $app['title'];
        }

        wp_send_json_success( $menu_items );
    }

    /**
     * Get Apps | Callback
     * 
     * @since 3.0.0
     */
    public function get_apps( $request ) {
        $id                 = $request->get_param( 'id' );
        $settings_for       = $request->get_param( 'settings-for' );
        $registered_apps    = $this->get_registered_apps();
        $apps               = array();
        $apps_with_priority = array();

        $requested_app_instance = null;

        // First, collect all apps with their priorities
        foreach( $registered_apps as $key => $app ) {
            if( class_exists( $app ) ) {
                $app_instance = new $app;
                $apps_with_priority[] = array(
                    'app_instance' => $app_instance,
                    'priority'     => property_exists($app_instance, 'priority') ? $app_instance->priority : 999
                );

                // When a specific ID is requested, prefer the Pro app with internal settings.
                if ( ! empty( $id ) && $app_instance->id === $id ) {
                    if ( null === $requested_app_instance ) {
                        $requested_app_instance = $app_instance;
                    } elseif (
                        $app_instance->is_pro
                        && $app_instance->has_internal_settings
                        && ( ! $requested_app_instance->is_pro || ! $requested_app_instance->has_internal_settings )
                    ) {
                        $requested_app_instance = $app_instance;
                    } elseif (
                        $app_instance->has_internal_settings
                        && ! $requested_app_instance->has_internal_settings
                    ) {
                        $requested_app_instance = $app_instance;
                    }
                }
            }
        }

        if ( ! empty( $id ) && null !== $requested_app_instance ) {
            $app_instance = $requested_app_instance;

            if ( empty( $settings_for ) ) {
                wp_send_json_success( $app_instance->get_settings() );
            }

            if ( 'internal-settings' === $settings_for ) {
                if ( $app_instance instanceof CF7Apps_App && $app_instance->has_internal_settings ) {
                    $form_id           = $request->get_param( 'form-id' );
                    $internal_settings = $app_instance->get_internal_settings( $form_id );

                    if ( in_array( $app_instance->id, cf7apps_get_payment_gateway_ids(), true ) ) {
                        $enabled_gateway = cf7apps_get_form_enabled_payment_gateway( absint( $form_id ) );
                        $internal_settings['enabled_form_payment_gateway'] = $enabled_gateway;

                        if ( $enabled_gateway && $enabled_gateway !== $app_instance->id ) {
                            $internal_settings['enabled_form_payment_gateway_label'] = cf7apps_get_payment_gateway_label( $enabled_gateway );
                        }
                    }

                    if ( 'cf7-redirection' === $app_instance->id ) {
                        $enabled_gateway = cf7apps_get_form_enabled_payment_gateway( absint( $form_id ) );
                        $internal_settings['enabled_form_payment_gateway'] = $enabled_gateway;

                        if ( $enabled_gateway ) {
                            $internal_settings['enabled_form_payment_gateway_label'] = cf7apps_get_payment_gateway_label( $enabled_gateway );
                        }
                    }

                    if ( in_array( $app_instance->id, array( 'stripe', 'paypal' ), true ) ) {
                        $internal_settings = cf7apps_apply_payment_form_currency_defaults(
                            $internal_settings,
                            $app_instance->id
                        );
                    }

                    // Global gateway status drives the disabled overlay in the form editor.
                    $global_enabled                           = $app_instance->get_option( 'is_enabled' );
                    $internal_settings['is_enabled']          = $global_enabled;
                    $internal_settings['global_is_enabled']   = $global_enabled;

                    $form_settings = get_post_meta( absint( $form_id ), 'cf7apps_settings', true );
                    if (
                        is_array( $form_settings )
                        && isset( $form_settings[ $app_instance->id ]['is_enabled'] )
                    ) {
                        $form_is_enabled = cf7apps_is_internal_setting_truthy(
                            $form_settings[ $app_instance->id ]['is_enabled']
                        );
                        $internal_settings['form_is_enabled'] = $form_is_enabled;

                        if ( isset( $internal_settings['admin_settings']['general']['fields']['is_enabled'] ) ) {
                            $internal_settings['admin_settings']['general']['fields']['is_enabled']['checked'] = $form_is_enabled;
                        }
                    }

                    wp_send_json_success( $internal_settings );
                }
            }
        }

        // Sort by priority
        usort($apps_with_priority, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Extract the sorted app settings
        foreach($apps_with_priority as $app_data) {
            $apps[] = $app_data['app_instance']->get_settings();
        }

        wp_send_json_success( $apps );
    }

    /**
     * Save Settings | Callback
     * 
     * @since 3.0.0
     */
    public function save_app_settings( $request ) {
        $settings = $request->get_body(); 
        $settings = json_decode( $settings, true );

        if( ! $settings || empty( $settings ) || ! isset( $settings['app_settings'] ) ) {
            wp_send_json_error( 
                'Invalid Request', 
                500 
            );
        }

        $teaser_app_ids = array( 'stripe', 'paypal', 'square' );
        if (
            ! cf7apps_has_pro()
            && isset( $settings['id'] )
            && in_array( $settings['id'], $teaser_app_ids, true )
        ) {
            wp_send_json_error(
                __( 'Upgrade to CF7 Apps Pro to use payment gateways.', 'cf7apps' ),
                403
            );
        }

        $settings['app_settings'] = $this->sanitize_app_settings( $settings['app_settings'] );

        $settings_for = isset( $settings['settings-for'] ) ? $settings['settings-for'] : '';
        if ( 'internal-settings' === $settings_for ) {
            $form_id = isset( $settings['form_id'] ) ? absint( $settings['form_id'] ) : 0;
            if ( $form_id <= 0 ) {
                wp_send_json_error(
                    __( 'Note: Save the Contact Form 7 form before configuring CF7 app settings. Settings can only be saved after the form has been created.', 'cf7apps' ),
                    400
                );
            }

            $response = cf7apps_save_internal_app_settings( $settings['id'], $form_id, $settings['app_settings'] );
        } else {
            $response = cf7apps_save_app_settings( $settings['id'], $settings['app_settings'] );
        }

        if( $response ) {
            wp_send_json_success( 'Settings Saved' );
        }
        else {
            wp_send_json_error( 
                'Error Saving Settings',
                500
            );
        }
    }

    /**
     * Recursively sanitize app settings before persisting.
     *
     * @since 3.5.0
     *
     * @param mixed $value Setting value.
     * @return mixed
     */
    private function sanitize_app_settings( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                $value[ $key ] = $this->sanitize_app_settings( $item );
            }

            return $value;
        }

        if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
            return $value;
        }

        if ( is_scalar( $value ) ) {
            return sanitize_text_field( (string) $value );
        }

        return '';
    }

    /**
     * Get Contact form 7 forms | Callback
     * 
     * @since 3.0.0
     */
    public function get_cf7_forms( $request ) {
        $data = array();
        $cf7_forms = get_posts( array(
            'post_type'      => 'wpcf7_contact_form',
            'posts_per_page' => -1
        ) );

        foreach ( $cf7_forms as $cf7_form ) {
            $hash = substr( get_post_meta( $cf7_form->ID, '_hash', true ), 0, 7 );
            $form = get_post_meta( $cf7_form->ID, '_form', true );
            $regx = '/\[honeypot [a-zA-Z]/i';
            preg_match_all( $regx, $form, $matches );
            $is_honeypot_enabled = ! empty( $matches[0] ) ? __( 'Yes', 'cf7apps' ) : __( 'No', 'cf7apps' );

            $data[] = array(
                'id'        => $cf7_form->ID,
                'title'     => $cf7_form->post_title,
                'shortcode' => '[contact-form-7 id="'.$hash.'" title="'.get_the_title( $cf7_form->ID ).'"]',
                'honeypot'  => $is_honeypot_enabled,
                'date'      => get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $cf7_form->ID ),
                'action'    => admin_url( 'post.php?page=wpcf7&post='.$cf7_form->ID.'&action=edit' )
            );
        }

        wp_send_json_success( $data );
    }

    /**
     * Has Migrated | Callback
     * 
     * @since 3.0.0
     */
    public function has_migrated( $request ) {
        $honeypot4cf7_config = get_option( 'honeypot4cf7_config' );

        if( $honeypot4cf7_config ) {
            wp_send_json_success( array( 'has_migrated' => false ) );
        }

        wp_send_json_success( array( 'has_migrated' => true ) );
    }

    /**
     * Migrate Settings | Callback
     * 
     * @since 3.0.0
     */
    public function migrate() { 
        try {
            cf7apps_migrate_legacy_settings();

            wp_send_json_success( 'Migration completed.' );
        } catch( Exception $e ) {
            wp_send_json_error( 
                "Migration not completed, Something went wrong {$e->getMessage()}", 
                500 
            );
        }
    }

	/**
	 * Get Contact Form 7 Entries | Callback
	 *
	 * @since 3.1.0
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_cf7_entries( $request ) {
		$entries_module_enabled = get_option( 'cf7apps_settings' );
		$entries                = array();
		$total_entries          = 0;

		if ( is_array( $entries_module_enabled ) && isset( $entries_module_enabled['cf7-entries']['is_enabled'] ) && $entries_module_enabled['cf7-entries']['is_enabled'] ) {
			$page       = $request->get_param( 'page' );
			$per_page   = $request->get_param( 'per-page' );
			$search     = $request->get_param( 'search' );
			$form_id    = $request->get_param( 'form-id' );
			$start_date = $request->get_param( 'start-date' );
			$end_date   = $request->get_param( 'end-date' );

			if ( ! is_numeric( $page ) || $page < 1 ) {
				$page = 1;
			}

			if ( ! is_numeric( $per_page ) || $per_page < 1 ) {
				$per_page = 10; // Default to 10 entries per page
			}

			if ( ! empty( $start_date ) ) {
				// currently the date picker returns date -1 we need to add one day
				$start_date  = date( 'Y-m-d H:i:s', strtotime( $start_date . ' +1 day' ) );
				$start_date  = strtotime( $start_date );
			}

			if ( ! empty( $end_date ) ) {
				// currently the date picker returns date -1 we need to add one day
				$end_date    = date( 'Y-m-d H:i:s', strtotime( $end_date . ' +1 day' ) );
				$end_date  = strtotime( $end_date );
			}

			if ( ! empty( $start_date ) && ! empty( $end_date ) && $start_date === $end_date ) {
				$end_date = strtotime( date( 'Y-m-d H:i:s', $end_date ) . ' +1 day' );
			}

			// offset
			$offset = ( $page - 1 ) * $per_page;

			$entries       = CF7Apps_Form_Entries::get_all_entries( $per_page, $offset, $form_id, $search, $start_date, $end_date, array( 'orderby' => 'id', 'order' => 'DESC' ) );
			$total_entries = CF7Apps_Form_Entries::get_total_entries( $form_id, $search, $start_date, $end_date );

			if ( class_exists( 'CF7Apps_Pro_Payment_Logs' ) && ! empty( $entries ) ) {
				foreach ( $entries as $entry ) {
					$payment = CF7Apps_Pro_Payment_Logs::get_by_entry_id( (int) $entry->id );
					$entry->payment = array(
						'is_gateway_enabled' => $this->is_any_payment_gateway_enabled_for_form( (int) $entry->form_id ),
						'has_payment'        => ! empty( $payment ),
						'payment_id'         => ! empty( $payment['id'] ) ? (int) $payment['id'] : 0,
						'payment_method'     => ! empty( $payment['payment_method'] ) ? $payment['payment_method'] : '',
						'payment_method_key' => ! empty( $payment['payment_method_key'] ) ? $payment['payment_method_key'] : '',
						'order_id'           => ! empty( $payment['order_id'] ) ? $payment['order_id'] : '',
						'details'            => ! empty( $payment ) ? $payment : null,
					);
				}
			}

			if ( empty( $entries ) ) {
				wp_send_json_error( 'No entries found.', 404 );
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $entries,
				'total'   => $total_entries,
			)
		);
	}

	/**
	 * Delete Contact Form 7 Entries | Callback
	 *
	 * @since 3.1.0
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_cf7_entries( $request ) {
		$entry_ids = $request->get_param( 'entry_ids' );
		CF7Apps_Form_Entries::delete_entries( $entry_ids );

		wp_send_json_success( 'Entries deleted successfully.' );
	}

	/**
	 * Export Contact Form 7 entries as CSV.
	 *
	 * @since 3.8.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function export_cf7_entries( $request ) {
		if ( ! $this->is_entries_enabled() ) {
			return new WP_Error(
				'cf7apps_entries_disabled',
				__( 'Entries is not enabled.', 'cf7apps' ),
				array( 'status' => 403 )
			);
		}

		$params     = $request->get_json_params();
		$entry_ids  = isset( $params['entry_ids'] ) ? (array) $params['entry_ids'] : array();
		$form_id    = isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0;
		$search     = isset( $params['search'] ) ? sanitize_text_field( (string) $params['search'] ) : '';
		$start_date = isset( $params['start_date'] ) ? sanitize_text_field( (string) $params['start_date'] ) : '';
		$end_date   = isset( $params['end_date'] ) ? sanitize_text_field( (string) $params['end_date'] ) : '';

		$entry_ids = array_values( array_filter( array_map( 'absint', $entry_ids ) ) );

		if ( ! empty( $entry_ids ) ) {
			$entries = CF7Apps_Form_Entries::get_entries_by_ids( $entry_ids );
		} else {
			if ( ! empty( $start_date ) ) {
				$start_date = strtotime( date( 'Y-m-d H:i:s', strtotime( $start_date . ' +1 day' ) ) );
			}

			if ( ! empty( $end_date ) ) {
				$end_date = strtotime( date( 'Y-m-d H:i:s', strtotime( $end_date . ' +1 day' ) ) );
			}

			if ( ! empty( $start_date ) && ! empty( $end_date ) && $start_date === $end_date ) {
				$end_date = strtotime( date( 'Y-m-d H:i:s', $end_date ) . ' +1 day' );
			}

			$entries = CF7Apps_Form_Entries::get_all_entries(
				0,
				0,
				$form_id,
				$search,
				$start_date,
				$end_date,
				array(
					'orderby' => 'id',
					'order'   => 'DESC',
				)
			);
		}

		if ( empty( $entries ) ) {
			return new WP_Error(
				'cf7apps_no_entries',
				__( 'No entries found to export.', 'cf7apps' ),
				array( 'status' => 404 )
			);
		}

		$csv      = CF7Apps_Form_Entries::entries_to_csv( $entries );
		$filename = CF7Apps_Form_Entries::get_export_filename( $entries, $form_id );

		return rest_ensure_response(
			array(
				'success'  => true,
				'filename' => $filename,
				'content'  => $csv,
			)
		);
	}

	/**
	 * Get payment logs for entries tab.
	 *
	 * @since 3.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_payment_logs( $request ) {
		$page     = absint( $request->get_param( 'page' ) );
		$per_page = absint( $request->get_param( 'per-page' ) );
		$form_id         = absint( $request->get_param( 'form-id' ) );
		$search          = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$payment_method  = sanitize_key( (string) $request->get_param( 'payment-method' ) );

		if ( $page < 1 ) {
			$page = 1;
		}
		if ( $per_page < 1 ) {
			$per_page = 10;
		}

		$entries_enabled = $this->is_entries_enabled();
		$has_gateway     = $this->is_any_payment_gateway_enabled_for_form( $form_id );
		$filter_gateway  = ( $payment_method && 'all' !== $payment_method ) ? $payment_method : '';
		$is_test_mode    = $this->is_any_gateway_in_test_mode( $filter_gateway );
		$offset          = ( $page - 1 ) * $per_page;

		if ( ! class_exists( 'CF7Apps_Pro_Payment_Logs' ) ) {
			return rest_ensure_response(
				array(
					'success'      => true,
					'data'         => array(),
					'total'        => 0,
					'entries_on'   => $entries_enabled,
					'gateway_on'   => $has_gateway,
					'is_test_mode' => $is_test_mode,
				)
			);
		}

		$logs  = CF7Apps_Pro_Payment_Logs::get_logs( $per_page, $offset, $form_id, $search, $payment_method );
		$total = CF7Apps_Pro_Payment_Logs::get_total_logs( $form_id, $search, $payment_method );

		return rest_ensure_response(
			array(
				'success'      => true,
				'data'         => $logs,
				'total'        => $total,
				'entries_on'   => $entries_enabled,
				'gateway_on'   => $has_gateway,
				'is_test_mode' => $is_test_mode,
			)
		);
	}

	/**
	 * Delete payment log entries.
	 *
	 * @since 3.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function delete_payment_logs( $request ) {
		$log_ids = $request->get_param( 'log_ids' );
		if ( ! class_exists( 'CF7Apps_Pro_Payment_Logs' ) ) {
			wp_send_json_success( __( 'Payment logs deleted successfully.', 'cf7apps' ) );
		}

		CF7Apps_Pro_Payment_Logs::delete_logs( $log_ids );
		wp_send_json_success( __( 'Payment logs deleted successfully.', 'cf7apps' ) );
	}

	/**
	 * Whether Entries app is enabled.
	 *
	 * @return bool
	 */
	private function is_entries_enabled() {
		$settings = get_option( 'cf7apps_settings', array() );
		return is_array( $settings ) && ! empty( $settings['cf7-entries']['is_enabled'] );
	}

	/**
	 * Whether any globally-enabled payment gateway is running in test/sandbox mode.
	 *
	 * @param string $gateway Optional gateway slug (stripe, paypal, square) to scope the check.
	 * @return bool
	 */
	private function is_any_gateway_in_test_mode( $gateway = '' ) {
		$settings = get_option( 'cf7apps_settings', array() );
		if ( ! is_array( $settings ) ) {
			return false;
		}

		$gateways = array( 'stripe', 'paypal', 'square' );
		if ( $gateway && in_array( $gateway, $gateways, true ) ) {
			$gateways = array( $gateway );
		}

		foreach ( $gateways as $gateway_key ) {
			if ( empty( $settings[ $gateway_key ]['is_enabled'] ) ) {
				continue;
			}
			$mode = isset( $settings[ $gateway_key ]['environment_mode'] ) ? $settings[ $gateway_key ]['environment_mode'] : 'live';
			if ( 'test' === $mode || 'sandbox' === $mode ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if any payment gateway is enabled globally and (optionally) for the form.
	 *
	 * @param int $form_id Form id.
	 * @return bool
	 */
	private function is_any_payment_gateway_enabled_for_form( $form_id = 0 ) {
		$settings = get_option( 'cf7apps_settings', array() );
		if ( ! is_array( $settings ) ) {
			return false;
		}

		$gateways = array( 'stripe', 'paypal', 'square' );
		foreach ( $gateways as $gateway ) {
			if ( empty( $settings[ $gateway ]['is_enabled'] ) ) {
				continue;
			}

			if ( $form_id <= 0 ) {
				return true;
			}

			$form_settings = get_post_meta( $form_id, 'cf7apps_settings', true );
			if ( is_array( $form_settings ) && ! empty( $form_settings[ $gateway ]['is_enabled'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get All Contact Form 7 Forms | Callback
	 *
	 * @since 3.1.0
	 *
	 */
	public function get_all_cf7_forms() {
		$cf7_forms = get_posts( array(
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => -1,
			'post_status'    => 'publish'
		) );

		if ( empty( $cf7_forms ) ) {
			wp_send_json_error( 'No forms found.', 404 );
		}

		$cf7_forms = wp_list_pluck( $cf7_forms, 'post_title', 'ID' );
		$cf7_forms[0] = __( 'All Forms', 'cf7apps' );

		wp_send_json_success( $cf7_forms );
	}

    /**
     * Get Spam Count | Callback
     *
     * @since 3.1.0
     * @return WP_REST_Response
     */
    public function get_spam_count() {
        $cf7apps_settings = get_option( 'cf7apps_settings', array() );
        $spam_count       = 0;
        $install_date     = null;
        if ( isset( $cf7apps_settings['honeypot']['honeypot_count'] ) ) {
            $spam_count = intval( $cf7apps_settings['honeypot']['honeypot_count'] );
        }

        if ( isset( $cf7apps_settings['honeypot']['honeypot_install_date'] ) ) {
            $install_date = gmdate(
                get_option( 'date_format' ),
                $cf7apps_settings['honeypot']['honeypot_install_date']
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'count' => $spam_count,
                    'since' => $install_date,
                ),
            )
        );
    }

    /**
     * Dashboard overview stats for sidebar cards.
     *
     * @since 3.9.0
     * @return WP_REST_Response
     */
    public function get_dashboard_overview() {
        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => CF7Apps_Domain_Authentication::get_overview_stats(),
            )
        );
    }

    /**
     * Domain authentication checks for sidebar card.
     *
     * @since 3.9.0
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_domain_authentication( $request ) {
        $force_refresh = rest_sanitize_boolean( $request->get_param( 'refresh' ) );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => CF7Apps_Domain_Authentication::get_payload( $force_refresh ),
            )
        );
    }

    /**
     * Recommended plugins for dashboard sidebar.
     *
     * @since 3.8.0
     * @return void
     */
    public function get_recommended_plugins() {
        wp_send_json_success( CF7Apps_Recommended_Plugins::get_payload() );
    }

    /**
     * Install and activate a recommended plugin.
     *
     * @since 3.8.0
     * @param WP_REST_Request $request Request.
     * @return void
     */
    public function install_activate_recommended_plugin( $request ) {
        $json = $request->get_json_params();
        $slug = is_array( $json ) && ! empty( $json['slug'] ) ? sanitize_key( $json['slug'] ) : '';

        if ( '' === $slug ) {
            $slug = sanitize_key( (string) $request->get_param( 'slug' ) );
        }

        $result = CF7Apps_Recommended_Plugins::install_and_activate( $slug );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data();
            $code   = is_array( $status ) && isset( $status['status'] ) ? (int) $status['status'] : 500;

            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                ),
                $code
            );
        }

        wp_send_json_success( $result );
    }
}

new CF7Apps_Rest_API_WP_Admin_V1();