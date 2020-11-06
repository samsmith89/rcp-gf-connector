<?php

/**
 * Plugin Name: GF RCP connection
 * Plugin URL: https://gsamsmith.com
 * Description: Combines RCP registration with Gravity Forms
 * Version: 1.0.0
 * Author: Sam Smith
 * Text Domain: rcp-gravity-forms
 * Domain Path: languages
 */

class GF_RCP
{
	/**
	 * @var
	 */
    protected static $_instance;

	/**
	 * @var string
	 */

    protected static $_version = '1.0.0';

	/**
	 * Summary.
	 *
	 * @since 1.0.0
	 *
	 * @return self
	 */

    public static function get_instance() {
        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

	/**
	 * Add Hooks and Actions.
	 *
	 * @since 1.0.0
	 */

    protected function __construct() {
        add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), - 9999 );
    }

	/**
	 * Includes.
	 *
	 * @since 1.0.0
	 */

    protected function includes() {
        if (!method_exists('GFForms', 'include_addon_framework')) {
            return;
        }
        require_once( $this->get_plugin_dir() . 'vendor/autoload.php' );

	    GF_RCP\Groups\Field_Groups::get_instance();
        GF_RCP\Fields::get_instance();
        GF_RCP\GravityFeed::get_instance();
        GF_RCP\Fields\Membership::get_instance();
        GF_RCP\Fields\Password::get_instance();
        GF_RCP\Fields\Useremail::get_instance();
        GF_RCP\Fields\Username::get_instance();
	    GF_RCP\Gateways::get_instance();
	    GF_RCP\Levels::get_instance();
//        GF_RCP\Gateways\Paypal::get_instance();
//        GF_RCP\Gateways\Stripe::get_instance();
//        GF_RCP\Gateways\Paypal_Pro::get_instance();
//        GF_RCP\Gateways\Twocheckout::get_instance();
        GFAddOn::register('GF_RCP\GravityFeed');
    }

	/**
	 * Setup the Plugin
	 *
	 * @since 1.0.0
	 *
	 * @see GF_RCP::includes()
	 * @see GF_RCP::actions()
	 */

    public function maybe_setup() {
        if ( ! $this->check_required_plugins() ) {
            return;
        }

        $this->includes();
        $this->actions();
    }

	/**
	 * Setup action hooks
	 *
	 * @since 1.0.0
	 *
	 * @see GF_RCP::styles()
	 */

    protected function actions() {
//        add_action( 'init', array( $this, 'load_textdomain' ) );
//        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
//        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
//        add_action( 'wp_footer', array( $this, 'print_scripts' ), 11 );
        add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
    }

	/**
	 * Enqueue stylesheets
	 *
	 * @since 1.0.0
	 *
	 * @see GF_RCP::get_plugin_url()
	 * @see GF_RCP::get_version()
	 *
	 */

    public function styles() {
        wp_enqueue_style( $this->get_id() . '-styles', $this->get_plugin_url() . '/assets/css/admin-styles.css', array(), $this->get_version() );
    }

	/**
	 * Get the Plugin URL
	 *
	 * @since 1.0.0
	 *
	 * @see GF_RCP::get_plugin_file()
	 *
	 * @return string
	 */

    public function get_plugin_url() {
        return plugin_dir_url( $this->get_plugin_file() );
    }

	/**
	 * Get the Plugin Directory Path
	 *
	 * @since 1.0.0
	 *
	 * @see GF_RCP::get_plugin_file()
	 *
	 * @return string
	 */

    public function get_plugin_dir() {
        return plugin_dir_path( $this->get_plugin_file() );
    }

	/**
	 * Get the Plugin File Path
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */

    public function get_plugin_file() {
        return __FILE__;
    }

	/**
	 * Checks for required plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */

    protected function check_required_plugins() {
        return true;
    }

    /**
     * Return the version of the plugin.
     *
     * @since  1.0.0
     *
     * @return int
     */
    public function get_version() {
        return self::$_version;
    }

    /**
     * Returns the plugin ID. Used in the textdomain.
     *
     * @since  1.0.0
     *
     * @return string
     */
    public function get_id() {
        return 'gfrcp';
    }

}

/**
 * Calls the instance of the class.
 *
 * @since 1.0.0
 *
 * @see GF_RCP::get_instance()
 *
 * @return object
 */

function gf_rcp() {
    return GF_RCP::get_instance();
}

gf_rcp();

