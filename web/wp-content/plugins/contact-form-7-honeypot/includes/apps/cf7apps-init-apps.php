<?php

class CF7Apps_Init_Apps {
    /**
     * Instance
     * 
     * @since 3.0.0
     */
    private static $instance;

    /**
     * Apps
     * 
     * @since 3.0.0
     */
    private $apps = array();

    /**
     * Whether registered apps have been booted.
     *
     * @var bool
     */
    private $apps_booted = false;

    /**
     * Initialize the class
     * 
     * @since 3.0.0
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof CF7Apps_Init_Apps ) ) {
            self::$instance = new CF7Apps_Init_Apps;
        }

        return self::$instance;
    }

    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        $this->require_app_modules();
        add_action( 'init', array( $this, 'boot_registered_apps' ), 2 );
    }

    /**
     * Load app module files so they can register cf7apps_apps filters.
     *
     * @since 3.0.0
     */
    public function require_app_modules() {
        foreach ( new DirectoryIterator( CF7APPS_PLUGIN_DIR . '/includes/apps' ) as $file ) {
            if ( $file->isDir() && ! $file->isDot() ) {
                $app_name = $file->getBasename();
                $app_file = CF7APPS_PLUGIN_DIR . "/includes/apps/{$app_name}/{$app_name}.php";

                if ( file_exists( $app_file ) ) {
                    require_once $app_file;
                }
            }
        }
    }

    /**
     * Register and boot apps after translations are available (WordPress 6.7+).
     *
     * @since 3.7.0
     */
    public function boot_registered_apps() {
        if ( ! empty( $this->apps_booted ) ) {
            return;
        }

        /**
         * Filter to Register App
         *
         * @since 3.0.0
         */
        $this->apps = apply_filters( 'cf7apps_apps', $this->apps );

        // Run code globally for each app.
        foreach ( $this->apps as $app ) {
            if ( class_exists( $app ) ) {
                new $app;
            }
        }

        $this->apps_booted = true;
    }

    /**
     * Initialize the plugin
     *
     * @since 3.0.0
     * @deprecated 3.7.0 Use require_app_modules() and boot_registered_apps().
     */
    public function init_apps() {
        $this->require_app_modules();
        $this->boot_registered_apps();
    }
}

CF7Apps_Init_Apps::instance();