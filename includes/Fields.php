<?php

namespace GF_RCP;

use RCP_Levels;
use GFAPI;
use GFForms;
use GFAddOn;
use RGFormsModel;

/**
 * Handles functionality related to Gravity Form fields.
 *
 * @since 1.0.0
 */

class Fields {
	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @since 1.0.0
	 *
	 * @return self
	 */

	public static function get_instance() {
		if ( ! self::$_instance instanceof Fields ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Adds actions and filters related to Gravity Forms fields.
	 *
	 * @since 1.0.0
	 */

	protected function __construct() {
		add_filter( 'gform_product_info', [ $this, 'add_membership_to_product' ], 10, 3 );
		add_action( 'gform_field_standard_settings', [ $this, 'membership_standard_settings' ], 10, 2 );
		add_action( 'gform_editor_js', [ $this, 'editor_script' ] );
		add_filter( 'gform_tooltips', [ $this, 'add_gfrcp_tooltips' ] );
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_defaults' ] );
		add_filter( 'gform_field_css_class', [ $this, 'custom_class' ], 10, 3 );
		add_filter( 'gform_gravityformsstripe_feed_settings_fields', [
			$this,
			'update_feed_settings_fields_stripe'
		], 10 ); // $feed_settings_fields = apply_filters( "gform_{$this->_slug}_feed_settings_fields", $feed_settings_fields, $this ); line: 1361
		add_filter( 'gform_gravityformspaypal_feed_settings_fields', [
			$this,
			'update_feed_settings_fields_paypal'
		], 10 ); // $feed_settings_fields = apply_filters( "gform_{$this->_slug}_feed_settings_fields", $feed_settings_fields, $this ); line: 1361
		add_filter( 'gform_gravityformspaypalpaymentspro_feed_settings_fields', [
			$this,
			'update_feed_settings_fields_paypal_pro'
		], 10 ); // $feed_settings_fields = apply_filters( "gform_{$this->_slug}_feed_settings_fields", $feed_settings_fields, $this ); line: 1361
		add_filter( 'gform_gravityforms2checkout_feed_settings_fields', [
			$this,
			'update_feed_settings_fields_2checkout'
		], 10 ); // $feed_settings_fields = apply_filters( "gform_{$this->_slug}_feed_settings_fields", $feed_settings_fields, $this ); line: 1361
		add_action( 'gform_editor_js_set_default_values', [ $this, 'enqueue_form_editor_script' ] );
		add_filter( 'gform_noconflict_scripts', [ $this, 'register_script' ], 10 );
//		add_filter( 'gform_submission_data_pre_process_payment', [ $this, 'add_membership_field_subscription' ], 10, 4 );
	}

	/**
	 * Adds the membership fields to the products array.
	 *
	 * @since 1.0.0
	 *
	 * @see gf_apply_filters( array( 'gform_product_info', $form['id'] ), $product_info, $form, $lead );
     *
	 * @param array $product_info The selected products, options, and shipping details for the current entry.
	 * @param array $form         The form object used to generate the current entry.
	 * @param array $lead         The current entry object.
	 * @return array $product_info.
	 */

	public static function add_membership_to_product( $product_info, $form, $lead ) {
		$membership_field                                       = GFAPI::get_fields_by_type( $form, [ 'membership' ] );
		$membership_level                                       = rgexplode( '|', $lead[ $membership_field[0]['id'] ], 2 );
		$product_info['products'][ $membership_field[0]['id'] ] = [
			'name'     => $membership_level[0],
			'price'    => $membership_level[1],
			'quantity' => '1',
			'options'  => []

		];
		return $product_info;
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since 1.0.0
	 *
	 * @see RCP_Levels::get_levels();
	 * @see RCP_Levels::update_meta();
     *
	 * @return array The RCP levels that are connected to gfrcp.
	 */

	public function gfrcp_get_rcp_levels() {
		$levels_db       = new RCP_Levels();
		$levels          = $levels_db->get_levels( array( 'status' => 'active' ) );
		$is_gfrcp_levels = [];

		foreach ( $levels as $level ) {
			if ( $levels_db->get_meta( $level->id, 'is_gfrcp', true ) == 1 ) {
				$is_gfrcp_levels[] = $level;
			}
		}
		return $is_gfrcp_levels;
	}

	/**
	 * Adds membership settings to the Membership field.
     *
     * Inserts additional content within the General field settings.
     * Note: This action fires multiple times.  Use the first parameter to determine positioning on the list.
	 *
	 * @since 1.0.0
     *
     * @see do_action( 'gform_field_standard_settings', 0, $form_id );
	 *
	 * @param int 0        The placement of the action being fired
	 * @param int $form_id The current form ID
	 */

	public function membership_standard_settings( $position, $form_id ) {
		if ( $position == 25 ) {
			?>
            <li class="membership_product_field_type_setting field_setting">
                <label for="membership_product_field_type" class="section_label">
					<?php esc_html_e( 'Membership Field Type', 'gravityforms' ); ?>
					<?php gform_tooltip( 'form_field_type' ) ?>
                </label>
                <select id="membership_product_field_type"
                        onchange="if(jQuery(this).val() == '') return; jQuery('#field_settings').slideUp(function(){StartChangeProductType(jQuery('#membership_product_field_type').val());});">
                    <option value=""><?php esc_html_e( 'Select Type', 'gravityforms' ); ?></option>
                    <option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
                    <option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
                </select>
            </li>
			<?php
		}
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

					foreach ( $this->gfrcp_get_rcp_levels() as $level ) {
						echo '<option value="' . $level->name . '" data-price="' . rcp_currency_filter( $level->price ) . '">' . $level->name . ' - ' . rcp_currency_filter( $level->price ) . '</option>';
					}

					?>
                </select>
            </li>
			<?php
		}
	}

	/**
	 * Adds JavaScript to the form editor
	 *
	 * @since 1.0.0
	 *
	 * @see do_action( 'gform_editor_js' );
	 */

	public function editor_script() {
		?>
        <script type='text/javascript'>
            //adding setting to fields of type "text"
            fieldSettings.membership += ', .gfrcp_setting';
            fieldSettings.membership += ', .membership_product_field_type_setting';

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).on('gform_load_field_settings', function (event, field, form) {
                jQuery('#field_gfrcp_value').attr('checked', field.gfrcpField == true);
            });
        </script>
		<?php
	}

	/**
	 * Adds Tooltips to the form editor.
	 *
	 * @since 1.0.0
	 *
	 * @see add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );
	 *
	 * @param array $tooltips Array containing the available tooltips.
	 * @return array $tooltips.
	 */

	public function add_gfrcp_tooltips( $tooltips ) {
		$tooltips['form_field_gfrcp_value'] = "<h6>Memberships</h6>Select from your available memberships here";

		return $tooltips;
	}

	/**
	 * Adds a custom classes to the Membership field.
	 *
	 * @since 1.0.0
	 *
	 * @see gf_apply_filters( array( 'gform_field_css_class', $form_id ), trim( $css_class ), $field, $form );
     *
	 * @param array $classes
	 * @param object $field
	 * @param object $form
	 * @return array The array of classes for the field types.
	 */

	public function custom_class( $classes, $field, $form ) {
		if ( $field->type == 'membership' ) {
			$classes .= ' gfrcp-membership-field';
			$classes .= ' gfield_price gfield_price_' . $field->formId . '_' . $field->id . ' gfield_product_' . $field->formId . '_' . $field->id;
		}

		return $classes;
	}

	/**
	 * Sets the default choices of the Membership field to the connected membership levels in RCP.
     *
     * Note: This hook fires in the middle of a JavaScript switch statement.
	 *
	 * @since 1.0.0
	 *
	 * @see Fields::gfrcp_get_rcp_levels()
	 * @see rcp_currency_filter()
	 */

	public function set_defaults() {
		$choices = [];
		foreach ( $this->gfrcp_get_rcp_levels() as $level ) {
			$choice    = 'new Choice("' . $level->name . ' - ' . rcp_currency_filter( $level->price ) . '", ' . json_encode( $level->name ) . ', "' . rcp_currency_filter( $level->price ) . '")';
			$choices[] = $choice;
		}
		$text = implode( ", ", $choices );
		?>
        //this hook is fired in the middle of a switch statement,
        //so we need to add a case for our new field type
        case "membership" :
        field.label = "Membership"; //setting the default field label
        field.choices = new Array(<?php echo $text; ?>);
        break;
		<?php
	}

	/**
	 * Adds the useremail field to the list of available choices in the Stripe feed settings.
	 *
	 * @since 1.0.0
	 *
	 * @see apply_filters( "gform_{$this->_slug}_feed_settings_fields", $feed_settings_fields, $this );
	 *
	 * @param array $feed_settings_fields An array of feed settings fields which will be displayed on the Feed Settings edit view.
	 * @return array
	 */

	public function update_feed_settings_fields_stripe( $feed_settings_fields ) {
		$feed_settings_fields[4]['fields'][0]['field_map'][0]['field_type'][] = "useremail";

		$form    = GFAddOn::get_current_form();
		$fields  = GFAPI::get_fields_by_type( $form, array( 'product', 'membership', 'total' ) );
		$choices = array(
			array( 'label' => esc_html__( 'Select a product field', 'gravityforms' ), 'value' => '' ),
		);

		foreach ( $fields as $field ) {
			$field_id    = $field->id;
			$field_label = RGFormsModel::get_label( $field );
			$choices[]   = array( 'value' => $field_id, 'label' => $field_label );
		}
		$feed_settings_fields[1]['fields'][1]['choices'] = $choices;

		return $feed_settings_fields;
	}

	public function update_feed_settings_fields_paypal( $feed_settings_fields ) {
		return $this->insert_form_settings_fields($feed_settings_fields);
	}

	public function update_feed_settings_fields_paypal_pro( $feed_settings_fields ) {
		return $this->insert_form_settings_fields($feed_settings_fields);
	}

	public function update_feed_settings_fields_2checkout( $feed_settings_fields ) {
		return $this->insert_form_settings_fields( $feed_settings_fields );
	}

	public function insert_form_settings_fields( $feed_settings_fields ) {
		$form    = GFAddOn::get_current_form();
		$fields  = GFAPI::get_fields_by_type( $form, array( 'product', 'membership', 'total' ) );
		$choices = array(
			array( 'label' => esc_html__( 'Select a product field', 'gravityforms' ), 'value' => '' ),
		);

		foreach ( $fields as $field ) {
			$field_id    = $field->id;
			$field_label = RGFormsModel::get_label( $field );
			$choices[]   = array( 'value' => $field_id, 'label' => $field_label );
		}
		$feed_settings_fields[1]['fields'][0]['choices'] = $choices;

		return $feed_settings_fields;
	}

	public function enqueue_form_editor_script() {

		if ( GFForms::is_gravity_page() ) {
			//enqueing my script on gravity form pages
			wp_enqueue_script( 'gfrcp-gravityforms', plugins_url( 'gfrcp/assets/js/gravityforms.js', 'gfrcp' ) );
		}
	}

	public function register_script( $scripts ) {

		//registering my script with Gravity Forms so that it gets enqueued when running on no-conflict mode
		$scripts[] = 'gfrcp-gravityforms';

		return $scripts;
	}

//	public function add_membership_field_subscription($submission_data, $feed, $form, $entry) {
//		$fee = gform_get_meta( $entry['form_id'], 'gfrcp_initial_fee' );
//		$recurring_amount = gform_get_meta( $entry['form_id'], 'gfrcp_recurring_amount' );
//
//        $submission_data['setup_fee'] = $fee;
//        $submission_data['payment_amount'] = $recurring_amount;
//        return $submission_data;
//	}
}


