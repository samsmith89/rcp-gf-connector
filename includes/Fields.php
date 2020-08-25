<?php

namespace GF_RCP;

use RCP_Levels;

class Fields {

	protected static $_instance;
	public $choices;

	public static $levels;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Fields ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'gform_field_standard_settings', [ $this, 'my_standard_settings' ], 10, 2 );
		add_action( 'gform_editor_js', [ $this, 'editor_script' ] );
		add_filter( 'gform_tooltips', [ $this, 'add_gfrcp_tooltips' ] );
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_defaults' ] );
		add_filter( 'gform_field_css_class', [ $this, 'custom_class' ], 10, 3 );

	}

	public static function gfrcp_get_rcp_levels() {
		$levels_db    = new RCP_Levels();
		return $levels = $levels_db->get_levels( array( 'status' => 'active' ) );
	}

	public function my_standard_settings( $position, $form_id ) {
		if ( $position == 1600 ) {
			?>
            <li class="gfrcp_setting field_setting">
                <label for="field_admin_label">
					<?php esc_html_e( 'RCP Memberships', 'gravityforms' ); ?>
					<?php gform_tooltip( 'form_field_gfrcp_value' ) ?>
                </label>
                <select class="gfrcp-memberships">
                    <option value="select">Choose a Membership</option>
					<?php

					foreach ( self::gfrcp_get_rcp_levels() as $level ) {
						echo '<option value="' . $level->name . '" data-price="' . rcp_currency_filter($level->price) . '">' . $level->name . '</option>';
					}

					?>
                </select>
            </li>
            <script>
                jQuery(".gfrcp-memberships").change(function (e) {
                    const gfrcpTitle = jQuery(this).children("option:selected").text();
                    const gfrcpVal = jQuery(this).children("option:selected").val();
                    const gfrcpPrice = jQuery(this).children("option:selected").attr('data-price');
                    if (gfrcpVal !== 'select') {
                        jQuery("#field_choices li .gf_insert_field_choice").last().click();
                        jQuery("#field_choices li .field-choice-text").last().val(gfrcpTitle);
                        jQuery("#field_choices li .field-choice-text").last().attr("value", gfrcpTitle)
                        jQuery("#field_choices li .field-choice-value").last().val(gfrcpVal);
                        jQuery("#field_choices li .field-choice-value").last().attr("value", gfrcpVal)
                        jQuery("#field_choices li .field-choice-price").last().val(gfrcpPrice);
                        jQuery("#field_choices li .field-choice-price").last().attr("value", gfrcpPrice);
                        jQuery("#field_choices li .field-choice-text").trigger('input');
                        jQuery("#field_choices li .field-choice-price").trigger('input');
                    }
                })
                jQuery("#field_choices").on("click", function () {
                    jQuery(".field-choice-value").prop('disabled', true);
                });
                jQuery("#field_choices").on("keydown", function () {
                    jQuery(".field-choice-value").prop('disabled', true);
                });

            </script>
			<?php
		}
	}

	public function editor_script() {
		?>
        <script type='text/javascript'>
            //adding setting to fields of type "text"
            fieldSettings.membership += ', .gfrcp_setting';

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).on('gform_load_field_settings', function (event, field, form) {
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

	public function set_defaults() {
		$choices = [];
		foreach ( self::gfrcp_get_rcp_levels() as $level ) {
			$choice = 'new Choice("' . $level->name . '", ' . json_encode( $level->name ) . ', "' . rcp_currency_filter($level->price) . '")';
			$choices[] = $choice;
		}
		$text = implode( ", ", $choices);
		?>
        //this hook is fired in the middle of a switch statement,
        //so we need to add a case for our new field type
        case "membership" :
        field.label = "Membership"; //setting the default field label
        field.choices = new Array(<?php echo $text; ?>);
        break;
		<?php
	}
}


