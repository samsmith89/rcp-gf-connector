<?php


class GFRCP_Fields {

    protected static $_instance;
    public $choices;

    public static function get_instance() {
        if ( ! self::$_instance instanceof GFRCP_Fields ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function __construct() {
        add_action( 'gform_field_standard_settings', [$this, 'my_standard_settings'], 10, 2 );
        add_action( 'gform_editor_js', [$this, 'editor_script'] );
        add_filter( 'gform_tooltips', [$this, 'add_gfrcp_tooltips'] );
        add_action( 'gform_editor_js_set_default_values', [$this, 'set_defaults'] );
        add_filter( 'gform_field_css_class', [$this, 'custom_class'], 10, 3 );
    }

    function my_standard_settings( $position, $form_id ) {

        if ( $position == 1600 ) {
            ?>
            <li class="gfrcp_setting field_setting">
                <label for="field_admin_label">
                    <?php esc_html_e( 'RCP Memberships', 'gravityforms' ); ?>
                    <?php gform_tooltip( 'form_field_gfrcp_value' ) ?>
                </label>
                <select class="gfrcp-memberships">
                    <?php

                    $levels_db = new RCP_Levels();
                    $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

                    foreach ($levels as $level) {
                        echo '<option value="' . $level->name . '">' . $level->name . '</option>';
                    }

                    ?>
                </select>
            </li>
            <script>
                jQuery( ".gfrcp-memberships" ).change(function(e) {
                    jQuery("#field_choices li .gf_insert_field_choice").last().click();
                    const gfrcpTitle = jQuery(this).children("option:selected").text();
                    const gfrcpVal = jQuery(this).children("option:selected").val();
                    jQuery("#field_choices li .field-choice-text").last().val(gfrcpTitle);
                    jQuery("#field_choices li .field-choice-text").last().attr("value",gfrcpTitle)
                    jQuery("#field_choices li .field-choice-value").last().val(gfrcpVal);
                    jQuery("#field_choices li .field-choice-value").last().attr("value",gfrcpVal);
                    jQuery("#field_choices li .field-choice-text").trigger('input');
                })
                jQuery( "#field_choices" ).on( "click", function() {
                    jQuery(".field-choice-value").prop('disabled', true);
                });
                jQuery( "#field_choices" ).on( "keydown", function() {
                    jQuery(".field-choice-value").prop('disabled', true);
                });

            </script>
            <?php
        }
    }

    function editor_script(){
        ?>
        <script type='text/javascript'>
            //adding setting to fields of type "text"
            fieldSettings.membership += ', .gfrcp_setting';

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).on('gform_load_field_settings', function(event, field, form){
                jQuery('#field_gfrcp_value').attr('checked', field.gfrcpField == true);
            });
        </script>
        <?php
    }

    public function add_gfrcp_tooltips( $tooltips ) {
        $tooltips['form_field_gfrcp_value'] = "<h6>Memberships</h6>Select from your available memberships here";
        return $tooltips;
    }

    public function custom_class( $classes, $field, $form ) {
        if ( $field->type == 'membership' ) {
            $classes .= ' gfrcp-membership-field';
        }
        return $classes;
    }

    public function set_defaults(){
        $levels_db = new RCP_Levels();
        $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );
        ?>
        //this hook is fired in the middle of a switch statement,
        //so we need to add a case for our new field type
        case "membership" :
        field.label = "Membership"; //setting the default field label
        field.choices = new Array(<?php

        foreach ($levels as $level) {
            echo 'new Choice(' . json_encode($level->name) . ', ' . json_encode($level->name) . ', "$0.00", "six"),';
        }

        ?>);
        break;
        <?php
    }
}

GFRCP_Fields::get_instance();


