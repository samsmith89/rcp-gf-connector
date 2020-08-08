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

function gf_simple_addon()
{
    return GFSimpleAddOn::get_instance();
}

function change_it($choices) {
//    $_GET['page'];
//    $_GET['id'];
    $form = GFAPI::get_form($_GET['id']);
    if (GFAPI::get_fields_by_type( $form, array( 'membership' ))) {
        $choices = [];
        $choices['My Favorite Food'] = array( 'Fruit', 'Hamburger', 'Beans' );
        return $choices;
    } else {
        return $choices;
    }
}
//add_filter( 'gform_predefined_choices', 'change_it' );

add_action( 'gform_editor_js_set_default_values', 'set_defaults' );
function set_defaults() {
    ?>
    //this hook is fired in the middle of a switch statement,
    //so we need to add a case for our new field type
    case "membership" :
    field.inputs = null;
    if (!field.choices) {
    field.choices = new Array(<?php

    $levels_db = new RCP_Levels();
    $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

    foreach ($levels as $level) {
        ?>new Choice(<?php echo json_encode( esc_html__( $level->name, 'gravityforms' ) ); ?>, "<?php echo esc_html__( $level->id, 'gravityforms' ); ?>", "0.00"),
    <?php } ?>
    )}
    break;
    <?php
}

add_action( 'gform_field_standard_settings', 'my_standard_settings', 10, 2 );
function my_standard_settings( $position, $form_id ) {

    //create settings on position 25 (right after Field Label)
    if ( $position == 1600 ) {
        ?>
        <li class="encrypt_setting field_setting">
            <label for="field_admin_label">
                <?php esc_html_e( 'RCP Memberships', 'gravityforms' ); ?>
                <?php gform_tooltip( 'form_field_encrypt_value' ) ?>
            </label>
            <select class="gfrcp-memberships">
                <?php

                $levels_db = new RCP_Levels();
                $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

                foreach ($levels as $level) {
                    echo '<option value="' . $level->id . '">' . $level->name . '</option>';
                }

                ?>
            </select>
        </li>
        <script>
            jQuery( ".gfrcp-memberships" ).change(function(e) {
                jQuery("#field_choices li .gf_insert_field_choice").last().click();
                var again = jQuery(this).children("option:selected").text();
                // var again = "ype";
                var uh = jQuery(this).children("option:selected").val();
                // var uh = "1";
                // jQuery("#field_choices li .field-choice-text").last().focus();
                jQuery("#field_choices li .field-choice-text").last().val(again);
                // jQuery("#field_choices li .field-choice-text").last().blur();
                jQuery("#field_choices li .field-choice-text").last().attr("value",again)
                jQuery("#field_choices li .field-choice-value").last().val(again);
                jQuery("#field_choices li .field-choice-value").last().attr("value",again);
                jQuery("#field_choices li .field-choice-text").trigger('input');
                // jQuery("#field_choices li .field-choice-value").trigger('keyup');
                // jQuery("#field_choices li .field-choice-price").last().val("$0.00");
                // jQuery("#field_choices li .field-choice-price").last().attr("value","$0.00");
                // console.log(writeit);
            });
        </script>
        <?php
    }

}

add_action( 'gform_editor_js', 'editor_script' );
function editor_script(){
    ?>
    <script type='text/javascript'>
        //adding setting to fields of type "text"
        fieldSettings.membership += ', .encrypt_setting';

        //binding to the load field settings event to initialize the checkbox
        jQuery(document).on('gform_load_field_settings', function(event, field, form){
            jQuery('#field_encrypt_value').attr('checked', field.encryptField == true);
        });
    </script>
    <?php
}

add_filter( 'gform_tooltips', 'add_encryption_tooltips' );
function add_encryption_tooltips( $tooltips ) {
    $tooltips['form_field_encrypt_value'] = "<h6>Memberships</h6>Select from your available memberships here";
    return $tooltips;
}


?>
