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

define('GF_SIMPLE_ADDON_VERSION', '2.0');

add_action('gform_loaded', array('GF_Simple_AddOn_Bootstrap', 'load'), 5);

class GF_Simple_AddOn_Bootstrap
{

    public static function load()
    {

        if (!method_exists('GFForms', 'include_addon_framework')) {
            return;
        }

        require_once('includes/class-gfrcp.php');
        require_once('includes/groups/class-gfrcp-add-group.php');
        require_once('includes/fields/class-gfrcp-email.php');
        require_once('includes/fields/class-gfrcp-username.php');
        require_once('includes/fields/class-gfrcp-membership.php');
        require_once('includes/fields/class-gfrcp-password.php');
        require_once('includes/class-fields.php');

        GFAddOn::register('GFSimpleAddOn');
    }

}

wp_enqueue_style( 'gfrcp-styles', plugin_dir_url( __FILE__ ) . '/css/style.css',false,'1.1','all');
function gf_simple_addon()
{
    return GFSimpleAddOn::get_instance();
}



