<?php

/**
 * Save App Settings
 * 
 * @param int $id
 * @param array $settings
 * 
 * @since 3.0.0
 */
if( ! function_exists( 'cf7apps_save_app_settings' ) ):
function cf7apps_save_app_settings( $id, $settings ) {
    $settings = apply_filters( 'cf7apps_pre_save_app_settings', $settings, $id );

    $existing_settings = get_option( 'cf7apps_settings', false );

    if( $existing_settings ) {
        if( isset( $existing_settings[$id] ) ) {
            $existing_settings[$id] = array_merge( $existing_settings[$id], $settings );
        }
        else {
            $existing_settings[$id] = $settings;
        }
    }
    else {
        $existing_settings = array(
            $id => $settings
        );
    }
    
    update_option( 'cf7apps_settings', $existing_settings );

    return true;
}
endif;

/**
 * Week key (Monday Y-m-d, site timezone) for honeypot spam buckets.
 *
 * @since 3.9.0
 *
 * @param int|null $timestamp Unix timestamp.
 * @return string
 */
if ( ! function_exists( 'cf7apps_get_honeypot_spam_week_key' ) ) :
function cf7apps_get_honeypot_spam_week_key( $timestamp = null ) {
    $timezone = wp_timezone();
    $date     = new DateTime( 'now', $timezone );

    if ( null !== $timestamp ) {
        $date->setTimestamp( (int) $timestamp );
    }

    if ( '1' !== $date->format( 'N' ) ) {
        $date->modify( 'monday this week' );
    }

    $date->setTime( 0, 0, 0 );

    return $date->format( 'Y-m-d' );
}
endif;

/**
 * Increment weekly honeypot spam bucket for the current week.
 *
 * @since 3.9.0
 *
 * @param int|null $timestamp Unix timestamp.
 * @return void
 */
if ( ! function_exists( 'cf7apps_increment_honeypot_weekly_spam_count' ) ) :
function cf7apps_increment_honeypot_weekly_spam_count( $timestamp = null ) {
    $week_key = cf7apps_get_honeypot_spam_week_key( $timestamp );
    $weekly   = get_option( 'cf7apps_honeypot_weekly_counts', array() );

    if ( ! is_array( $weekly ) ) {
        $weekly = array();
    }

    $weekly[ $week_key ] = isset( $weekly[ $week_key ] ) ? ( (int) $weekly[ $week_key ] + 1 ) : 1;

    if ( count( $weekly ) > 52 ) {
        ksort( $weekly );
        $weekly = array_slice( $weekly, -52, null, true );
    }

    update_option( 'cf7apps_honeypot_weekly_counts', $weekly, false );
}
endif;

/**
 * Get honeypot spam count for a reporting week.
 *
 * @since 3.9.0
 *
 * @param int $week_start_timestamp Reporting week start (Monday 00:00).
 * @return int
 */
if ( ! function_exists( 'cf7apps_get_honeypot_weekly_spam_count' ) ) :
function cf7apps_get_honeypot_weekly_spam_count( $week_start_timestamp ) {
    $week_key = cf7apps_get_honeypot_spam_week_key( (int) $week_start_timestamp );
    $weekly   = get_option( 'cf7apps_honeypot_weekly_counts', array() );

    if ( ! is_array( $weekly ) || ! isset( $weekly[ $week_key ] ) ) {
        return 0;
    }

    return (int) $weekly[ $week_key ];
}
endif;

/**
 * Record a honeypot spam detection (lifetime + weekly counts).
 *
 * @since 3.9.0
 *
 * @param array $honeypot4cf7_config Honeypot config (passed by reference).
 * @param bool  $use_cf7apps_settings Whether CF7 Apps settings storage is active.
 * @return void
 */
if ( ! function_exists( 'cf7apps_record_honeypot_spam_detection' ) ) :
function cf7apps_record_honeypot_spam_detection( &$honeypot4cf7_config, $use_cf7apps_settings ) {
    $honeypot4cf7_config['honeypot_count'] = isset( $honeypot4cf7_config['honeypot_count'] )
        ? ( (int) $honeypot4cf7_config['honeypot_count'] + 1 )
        : 1;

    cf7apps_increment_honeypot_weekly_spam_count();

    if ( $use_cf7apps_settings ) {
        cf7apps_save_app_settings(
            'honeypot',
            array(
                'honeypot_count' => $honeypot4cf7_config['honeypot_count'],
            )
        );
    } else {
        update_option( 'honeypot4cf7_config', $honeypot4cf7_config );
    }
}
endif;

/**
 * Migrate Legacy Settings
 * 
 * @since 3.0.0
 */
if( ! function_exists( 'cf7apps_migrate_legacy_settings' ) ):
function cf7apps_migrate_legacy_settings() {
    $honeypot4cf7_config = get_option( 'honeypot4cf7_config' );
    $cf7apps_settings = array();

    if( $honeypot4cf7_config ) {
        $cf7apps_settings['is_enabled'] = 1;
        $cf7apps_settings['store_value'] = ! empty( $honeypot4cf7_config['store_honeypot'] ) ? $honeypot4cf7_config['store_honeypot'] : '';
        $cf7apps_settings['global_placeholder'] = ! empty( $honeypot4cf7_config['placeholder'] ) ? $honeypot4cf7_config['placeholder'] : '';
        $cf7apps_settings['accessibility_msg'] = ! empty( $honeypot4cf7_config['accessibility_message'] ) ? $honeypot4cf7_config['accessibility_message'] : '';
        $cf7apps_settings['inline_css'] = ( ! empty( $honeypot4cf7_config['move_inline_css'][0] ) && $honeypot4cf7_config['move_inline_css'][0] == 'true' ) ? 1 : 0;
        $cf7apps_settings['enable_time_check'] = ( ! empty( $honeypot4cf7_config['timecheck_enabled'][0] ) && $honeypot4cf7_config['timecheck_enabled'][0] == 'true' ) ? 1 : 0;
        $cf7apps_settings['time_check'] = ! empty( $honeypot4cf7_config['timecheck_value'] ) ? $honeypot4cf7_config['timecheck_value'] : '';
        $cf7apps_settings['standard_auto_complete'] = ( ! empty( $honeypot4cf7_config['w3c_valid_autocomplete'][0] ) && $honeypot4cf7_config['w3c_valid_autocomplete'][0] == 'true' ) ? 1 : 0;
        $cf7apps_settings['accessibility_label'] = ( ! empty( $honeypot4cf7_config['nomessage'][0] && $honeypot4cf7_config['nomessage'][0] == 'true' ) ) ? 1 : 0;
        $cf7apps_settings['silent_spam_response'] = ! empty( $honeypot4cf7_config['silent_spam_response'] ) ? 1 : 0;
        $cf7apps_settings['honeypot_count'] = ! empty( $honeypot4cf7_config['honeypot_count'] ) ? $honeypot4cf7_config['honeypot_count'] : 0;
        $cf7apps_settings['honeypot_install_date'] = ! empty( $honeypot4cf7_config['honeypot_install_date'] ) ? $honeypot4cf7_config['honeypot_install_date'] : time();
        $cf7apps_settings['honeypot_cf7_req_msg_dismissed'] = ! empty( $honeypot4cf7_config['honeypot_cf7_req_msg_dismissed'] ) ? $honeypot4cf7_config['honeypot_cf7_req_msg_dismissed'] : 0;
        $cf7apps_settings['honeypot4cf7_version'] = ! empty( $honeypot4cf7_config['honeypot4cf7_version'] ) ? $honeypot4cf7_config['honeypot4cf7_version'] : CF7APPS_VERSION;
        $cf7apps_settings['enable_dynamic_honeypot'] = 0;

        cf7apps_save_app_settings( 'honeypot', $cf7apps_settings );
        
        delete_option( 'honeypot4cf7_config' );
    }
}
endif;

/**
 * Get Default Settings
 * 
 * @since 3.0.0
 */
if( ! function_exists( 'cf7apps_get_default_settings' ) ):
function cf7apps_get_default_settings() {
    return array(
        'is_enabled'                        => 1,
        'store_value'                       => 0,
        'global_placeholder'                => '',
        'accessibility_msg'                 => '',
        'inline_css'                        => 0,
        'enable_time_check'                 => 0,
        'time_check'                        => 4,
        'standard_auto_complete'            => 0,
        'accessibility_label'               => 0,
        'silent_spam_response'            => 0,
        'honeypot_count'                    => 0,
        'honeypot_install_date'             => time(),
        'honeypot_cf7_req_msg_dismissed'    => 0,
        'honeypot4cf7_version'              => CF7APPS_VERSION,
        'enable_dynamic_honeypot'           => 0,
    );
}
endif;

/**
 * One-time migration for the dynamic honeypot toggle.
 *
 * @since 3.6.1.2
 */
if ( ! function_exists( 'cf7apps_maybe_migrate_dynamic_honeypot_setting' ) ) :
function cf7apps_maybe_migrate_dynamic_honeypot_setting() {
    $settings = get_option( 'cf7apps_settings', array() );

    if ( empty( $settings['honeypot'] ) ) {
        return;
    }

    if ( array_key_exists( 'enable_dynamic_honeypot', $settings['honeypot'] ) ) {
        return;
    }

    $prev_version = $settings['honeypot']['honeypot4cf7_version'] ?? '0.0.0';
    $settings['honeypot']['enable_dynamic_honeypot'] = version_compare( $prev_version, '3.6.0', '>=' ) ? 1 : 0;

    update_option( 'cf7apps_settings', $settings );
}
endif;

if ( ! function_exists( 'cf7apps_get_post_types_options' ) ) :
    /**
     * Get Post Types Options
     *
     * @since 3.2.0
     * @return array
     */
    function cf7apps_get_post_types_options() {
        // Default: posts + pages (filterable).
        $default_post_types = array( 'post', 'page' );

        // Filterable list of post types for the dropdown.
        $post_type_slugs = apply_filters( 'cf7apps_redirection_post_types', $default_post_types );

        // Unique, non-empty slugs only.
        $post_type_slugs = array_values( array_filter( array_unique( (array) $post_type_slugs ) ) );

        $query_args = array(
            'post_type'      => $post_type_slugs,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        );

        $options  = array();
        $post_ids = get_posts( $query_args );

        foreach ( $post_ids as $post_id ) {
            $title = get_post_field( 'post_title', $post_id );

            if ( '' === $title ) {
                /* translators: %d: post ID. */
                $title = sprintf( __( '(no title) (ID: %d)', 'cf7apps' ), $post_id );
            }

            $options[ $post_id ] = sprintf( '%s (ID: %d)', $title, $post_id );
        }

        return $options;
    }
endif;

if ( ! function_exists( 'cf7apps_is_internal_setting_truthy' ) ) :
    /**
     * Whether a per-form internal setting value should be treated as enabled/checked.
     *
     * @since 3.6.0
     *
     * @param mixed $value Raw setting value.
     * @return bool
     */
    function cf7apps_is_internal_setting_truthy( $value ) {
        if ( class_exists( 'CF7Apps_Pro_Payment_Checkout', false ) ) {
            return CF7Apps_Pro_Payment_Checkout::is_truthy( $value );
        }

        return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    }
endif;

if ( ! function_exists( 'cf7apps_normalize_internal_app_settings_for_storage' ) ) :
    /**
     * Normalize internal app settings before persisting to post meta.
     *
     * @since 3.6.0
     *
     * @param array $app_settings App settings payload.
     * @return array
     */
    function cf7apps_normalize_internal_app_settings_for_storage( $app_settings ) {
        if ( ! is_array( $app_settings ) ) {
            return array();
        }

        foreach ( $app_settings as $key => $value ) {
            if ( is_bool( $value ) ) {
                $app_settings[ $key ] = $value ? 1 : 0;
            }
        }

        return $app_settings;
    }
endif;

if ( ! function_exists( 'cf7apps_get_payment_gateway_ids' ) ) :
    /**
     * Per-form payment gateway app IDs.
     *
     * @since 3.6.0
     * @return string[]
     */
    function cf7apps_get_payment_gateway_ids() {
        return array( 'stripe', 'paypal', 'square' );
    }
endif;

if ( ! function_exists( 'cf7apps_get_payment_gateway_label' ) ) :
    /**
     * Human-readable payment gateway name.
     *
     * @since 3.6.0
     *
     * @param string $gateway_id stripe|paypal|square.
     * @return string
     */
    function cf7apps_get_payment_gateway_label( $gateway_id ) {
        $labels = array(
            'stripe' => __( 'Stripe', 'cf7apps' ),
            'paypal' => __( 'PayPal', 'cf7apps' ),
            'square' => __( 'Square', 'cf7apps' ),
        );

        return isset( $labels[ $gateway_id ] ) ? $labels[ $gateway_id ] : $gateway_id;
    }
endif;

if ( ! function_exists( 'cf7apps_get_form_enabled_payment_gateway' ) ) :
    /**
     * Active per-form payment gateway for a CF7 form (at most one).
     *
     * @since 3.6.0
     *
     * @param int $form_id Form post ID.
     * @return string Gateway ID or empty string.
     */
    function cf7apps_get_form_enabled_payment_gateway( $form_id ) {
        $form_id = absint( $form_id );
        if ( $form_id <= 0 ) {
            return '';
        }

        $settings = get_post_meta( $form_id, 'cf7apps_settings', true );
        if ( ! is_array( $settings ) ) {
            return '';
        }

        foreach ( cf7apps_get_payment_gateway_ids() as $gateway_id ) {
            if ( empty( $settings[ $gateway_id ] ) || ! is_array( $settings[ $gateway_id ] ) ) {
                continue;
            }

            if ( cf7apps_is_internal_setting_truthy( $settings[ $gateway_id ]['is_enabled'] ?? false ) ) {
                return $gateway_id;
            }
        }

        return '';
    }
endif;

if ( ! function_exists( 'cf7apps_enforce_single_form_payment_gateway' ) ) :
    /**
     * Ensure only one payment gateway is enabled per form.
     *
     * @since 3.6.0
     *
     * @param array  $settings        Full cf7apps_settings post meta array (by reference).
     * @param string $active_gateway  Gateway being enabled.
     * @return void
     */
    function cf7apps_enforce_single_form_payment_gateway( array &$settings, $active_gateway ) {
        if ( ! in_array( $active_gateway, cf7apps_get_payment_gateway_ids(), true ) ) {
            return;
        }

        foreach ( cf7apps_get_payment_gateway_ids() as $gateway_id ) {
            if ( $gateway_id === $active_gateway ) {
                continue;
            }

            if ( ! isset( $settings[ $gateway_id ] ) || ! is_array( $settings[ $gateway_id ] ) ) {
                continue;
            }

            $settings[ $gateway_id ]['is_enabled'] = 0;
        }
    }
endif;

if ( ! function_exists( 'cf7apps_save_internal_app_settings' ) ) :
    /**
     * Save Internal App Settings
     *
     * @since 3.2.0
     * @param string     $id App ID.
     * @param string|int $form_id CF7 Form ID.
     * @param array      $app_settings App Settings.
     *
     * @return bool
     */
    function cf7apps_save_internal_app_settings( $id, $form_id, $app_settings  ) {
        $form_id = absint( $form_id );
        if ( $form_id <= 0 || empty( $id ) || ! is_array( $app_settings ) ) {
            return false;
        }

        $app_settings = cf7apps_normalize_internal_app_settings_for_storage( $app_settings );

        $settings = get_post_meta( $form_id, 'cf7apps_settings', true );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        if ( isset( $settings[ $id ] ) && is_array( $settings[ $id ] ) ) {
            $settings[ $id ] = array_merge( $settings[ $id ], $app_settings );
        } else {
            $settings[ $id ] = $app_settings;
        }

        if (
            in_array( $id, cf7apps_get_payment_gateway_ids(), true )
            && cf7apps_is_internal_setting_truthy( $settings[ $id ]['is_enabled'] ?? false )
        ) {
            cf7apps_enforce_single_form_payment_gateway( $settings, $id );
        }

        update_post_meta( $form_id, 'cf7apps_settings', $settings );

        // update_post_meta() returns false when the value is unchanged.
        $saved = get_post_meta( $form_id, 'cf7apps_settings', true );

        return is_array( $saved )
            && isset( $saved[ $id ] )
            && is_array( $saved[ $id ] );
    }
endif;

/**
 * Global default payment currency from cf7apps_settings[ $app_id ].
 *
 * @since 3.6.0
 *
 * @param string $app_id   stripe|paypal|square.
 * @param string $fallback Fallback ISO code.
 * @return string
 */
if ( ! function_exists( 'cf7apps_get_global_payment_currency' ) ) :
function cf7apps_get_global_payment_currency( $app_id, $fallback = 'USD' ) {
    if ( class_exists( 'CF7Apps_Pro_Currencies', false ) ) {
        return CF7Apps_Pro_Currencies::get_global_default_for_app( $app_id, $fallback );
    }

    $settings = get_option( 'cf7apps_settings', array() );
    if ( ! is_array( $settings ) || empty( $settings[ $app_id ] ) || ! is_array( $settings[ $app_id ] ) ) {
        return $fallback;
    }

    $cur = isset( $settings[ $app_id ]['default_currency'] )
        ? sanitize_text_field( (string) $settings[ $app_id ]['default_currency'] )
        : '';

    return '' !== $cur ? $cur : $fallback;
}
endif;

/**
 * Inject global currency into per-form internal settings payload.
 *
 * @since 3.6.0
 *
 * @param array  $settings Internal settings response.
 * @param string $app_id   stripe|paypal.
 * @return array
 */
if ( ! function_exists( 'cf7apps_apply_payment_form_currency_defaults' ) ) :
function cf7apps_apply_payment_form_currency_defaults( array $settings, $app_id ) {
    if ( ! in_array( $app_id, array( 'stripe', 'paypal' ), true ) ) {
        return $settings;
    }

    if ( class_exists( 'CF7Apps_Pro_Currencies', false ) ) {
        return CF7Apps_Pro_Currencies::apply_per_form_currency_defaults( $settings, $app_id );
    }

    $cur = cf7apps_get_global_payment_currency( $app_id );
    $field_key = 'currency';

    $settings['payment_global'] = array(
        'default_currency' => $cur,
    );

    if ( empty( $settings['admin_settings']['general']['fields'][ $field_key ] ) ) {
        return $settings;
    }

    $field            = &$settings['admin_settings']['general']['fields'][ $field_key ];
    $field['default'] = $cur;

    if ( ! isset( $field['selected'] ) || '' === (string) $field['selected'] ) {
        $field['selected'] = $cur;
    }

    return $settings;
}
endif;

if ( ! function_exists( 'cf7apps_has_enabled_internal_settings_app' ) ) :
    /**
     * Whether any globally enabled app exposes per-form internal settings.
     *
     * @since 3.8.0
     * @return bool
     */
    function cf7apps_has_enabled_internal_settings_app() {
        $options = get_option( 'cf7apps_settings', array() );
        if ( ! is_array( $options ) ) {
            return false;
        }

        $registered_apps = apply_filters( 'cf7apps_apps', array() );
        foreach ( $registered_apps as $app_class ) {
            if ( ! class_exists( $app_class ) ) {
                continue;
            }

            $app = new $app_class();
            if ( empty( $app->has_internal_settings ) || empty( $app->id ) ) {
                continue;
            }

            if ( ! empty( $options[ $app->id ]['is_enabled'] ) ) {
                return true;
            }
        }

        return false;
    }
endif;

/**
 * Whether CF7 Apps Pro is active.
 *
 * @since 3.6.0
 *
 * @return bool
 */
if ( ! function_exists( 'cf7apps_has_pro' ) ) :
function cf7apps_has_pro() {
    return (bool) apply_filters(
        'cf7apps_has_pro',
        class_exists( 'CF7Apps_Pro_Stripe', false )
    );
}
endif;
