<?php

namespace GF_RCP\Fields;

if ( ! class_exists( 'GFForms' ) ) {
    die();
}

use GF_Fields;
use GF_Field_Select;

class Membership extends GF_Field_Select
{
    protected static $_instance;

    public static function get_instance() {
        if ( ! self::$_instance instanceof Membership ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public $type = 'membership';

    /**
     * Adds the field button to the specified group.
     *
     * @param array $field_groups
     *
     * @return array
     */

//    public function __construct()
//    {
//        $yep = GFFormDetail::forms_page();
//        $yep->predefined_choices = [];
//    }

    public function add_button($field_groups)
    {

// Check a button for the type hasn't already been added
        foreach ($field_groups as $group) {
            foreach ($group['fields'] as $button) {
                if (isset($button['data-type']) && $button['data-type'] == $this->type) {
                    return $field_groups;
                }
            }
        }

        $new_button = $this->get_form_editor_button();
        if (!empty($new_button)) {
            foreach ($field_groups as &$group) {
                if ($group['name'] == $new_button['group']) {

// Prepare Membership button.
                    $membership_button = array(
                        'class' => 'button',
                        'value' => $new_button['text'],
                        'data-type' => $this->type,
                        'onclick' => "StartAddField('{$this->type}');",
                        'onkeypress' => "StartAddField('{$this->type}');",
                        // Get the active RCP memberhsips and echo them into choices
                    );

// Insert membership button.
                    array_splice($group['fields'], 0, 0, array($membership_button));

                    break;
                }
            }
        }

        return $field_groups;

    }

    public function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
//            'prepopulate_field_setting',
            'error_message_setting',
            'enable_enhanced_ui_setting',
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'size_setting',
            'choices_setting',
            'rules_setting',
            'default_value_setting'=> true,
            'placeholder_setting',
            'visibility_setting',
            'duplicate_setting',
            'description_setting',
            'css_class_setting',
        );
    }



    /**
     * Return the field title.
     *
     * @access public
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('Membership', 'rcp-gravity-forms');
    }

    /**
     * Assign the field button to the custom group.
     *
     * @return array
     */
    public function get_form_editor_button() {
        return array(
            'group' => 'rcp_field_group',
            'text'  => $this->get_form_editor_field_title(),
        );
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
						echo '<option value="' . $level->name . '">' . $level->name . '</option>';
					}

					?>
				</select>
			</li>
			<script>
                jQuery(".gfrcp-memberships").change(function (e) {
                    const gfrcpTitle = jQuery(this).children("option:selected").text();
                    const gfrcpVal = jQuery(this).children("option:selected").val();
                    if (gfrcpVal !== 'select') {
                        jQuery("#field_choices li .gf_insert_field_choice").last().click();
                        jQuery("#field_choices li .field-choice-text").last().val(gfrcpTitle);
                        jQuery("#field_choices li .field-choice-text").last().attr("value", gfrcpTitle)
                        jQuery("#field_choices li .field-choice-value").last().val(gfrcpVal);
                        jQuery("#field_choices li .field-choice-value").last().attr("value", gfrcpVal);
                        jQuery("#field_choices li .field-choice-text").trigger('input');
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
//        put together all the strings here. OB start into a variable then echo the variable into the hook.

//		$js = '';
//		foreach ( self::$levels as $level ) {
//			$js .= 'new Choice("' . $level->name . '", ' . json_encode( $level->name ) . '),';;
//		}
//		ob_start(); ?>
		<!---->
		<!--		--><?php
//		$js = ob_get_clean();
//		?>
		//this hook is fired in the middle of a switch statement,
		//so we need to add a case for our new field type
		case "membership" :
		field.label = "Membership"; //setting the default field label
		field.choices = new Array(<?php

		foreach ( self::gfrcp_get_rcp_levels() as $level ) {
			echo 'new Choice("' . $level->name . '", ' . json_encode( $level->name ) . '),';
		}

		?>);
		break;
		<?php
	}
}

GF_Fields::register(new Membership());