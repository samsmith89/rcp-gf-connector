<?php

namespace GF_RCP\Fields;

if ( ! class_exists( 'GFForms' ) ) {
    die();
}

use GF_Fields;
use GF_Field_Product;
use GF_RCP\Fields;

class Membership extends GF_Field_Product
{
    protected static $_instance;

    public static function get_instance() {
        if ( ! self::$_instance instanceof Membership ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public $type = 'membership';
	public $is_gfrcp = true;

	public function get_form_editor_inline_script_on_page_render() {
		$js = "gform.addFilter('gform_form_editor_can_field_be_added', function(result, type) {
            if (type === 'membership') {
                if (GetFieldsByType(['membership']).length > 0) {" .
		      sprintf( "alert(%s);", json_encode( esc_html__( 'Only one Membership field can be added to the form', 'gfrcp' ) ) )
		      . " result = false;
				}
            }
            if (type === 'product') {
                if ((GetFieldsByType(['membership']).length > 0 ) && (GetFieldsByType(['product']).length > 0)) {" .
		      sprintf( "alert(%s);", json_encode( esc_html__( 'Only one Product field can be added to the form to set up an initial fee', 'gfrcp' ) ) )
		      . " result = false;
				}
            }
            if (type === 'membership') {
                if (GetFieldsByType(['product']).length > 1 ) {" .
		      sprintf( "alert(%s);", json_encode( esc_html__( 'There can only be one addition Product field for the purpose of setting up an initial fee', 'gfrcp' ) ) )
		      . " result = false;
				}
            }
            
            return result;
        });";

		return $js;
	}

	/**
	 * Adds the field button to the specified group.
	 *
	 * @param array $field_groups
	 *
	 * @return array
	 */
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
//	        'product_field_type_setting',
	        'prepopulate_field_setting',
	        'label_setting',
	        'admin_label_setting',
	        'label_placement_setting',
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

}

GF_Fields::register(new Membership());