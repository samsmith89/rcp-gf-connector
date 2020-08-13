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
    protected static $_instance;

    protected static $_version = '1.0.0';

    public static function get_instance() {
        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function __construct() {
        add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), - 9999 );
    }

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
        GF_RCP\Gateways\PayPal::get_instance();
        GFAddOn::register('GF_RCP\GravityFeed');

    }

    public function maybe_setup() {
        if ( ! $this->check_required_plugins() ) {
            return;
        }

        $this->includes();
        $this->actions();
    }

    protected function actions() {
//        add_action( 'init', array( $this, 'load_textdomain' ) );
//        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
//        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
//        add_action( 'wp_footer', array( $this, 'print_scripts' ), 11 );
//        add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
    }

    public function styles() {
        wp_enqueue_style( $this->get_id() . '-styles', $this->get_plugin_url() . 'css/style.css', array(), $this->get_version() );
    }

    public function get_plugin_url() {
        return plugin_dir_url( $this->get_plugin_file() );
    }

    public function get_plugin_dir() {
        return plugin_dir_path( $this->get_plugin_file() );
    }

    public function get_plugin_file() {
        return __FILE__;
    }

    protected function check_required_plugins() {
        return true;
    }

    /**
     * Return the version of the plugin
     *
     * @return string
     * @since  1.0.0
     *
     */
    public function get_version() {
        return self::$_version;
    }

    /**
     * Returns the plugin ID. Used in the textdomain
     *
     * @return string
     * @since  1.0.0
     *
     */
    public function get_id() {
        return 'gfrcp';
    }

}

//Add this above into "actions()"
wp_enqueue_style( 'gfrcp-styles', plugin_dir_url( __FILE__ ) . '/css/style.css',false,'1.1','all');


function gf_rcp() {
    return GF_RCP::get_instance();
}

gf_rcp();

