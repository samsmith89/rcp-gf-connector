<?php

namespace GF_RCP;

use RCP_Levels;
use GFAPI;
use GFForms;
use GFAddOn;
use RGFormsModel;

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
		add_filter( 'gform_product_info', [ $this, 'add_membership_to_product' ], 10, 3 );
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

	public static function gfrcp_get_rcp_levels() {
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

	public function my_standard_settings( $position, $form_id ) {
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

					foreach ( self::gfrcp_get_rcp_levels() as $level ) {
						echo '<option value="' . $level->name . '" data-price="' . rcp_currency_filter( $level->price ) . '">' . $level->name . '</option>';
					}

					?>
                </select>
            </li>
			<?php
		}
	}

	public function editor_script() {
		?>
        <script type='text/javascript'>
            //adding setting to fields of type "text"
            fieldSettings.membership += ', .gfrcp_setting';
            fieldSettings.membership += ', .membership_product_field_type_setting';

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).on('gform_load_field_settings', function (event, field, form) {
                jQuery('#field_gfrcp_value').attr('checked', field.gfrcpField == true);
                // jQuery('#field_gfrcp_value').attr('checked', field.gfrcpField == true);
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
			$classes .= ' gfield_price gfield_price_' . $field->formId . '_' . $field->id . ' gfield_product_' . $field->formId . '_' . $field->id;
		}

		return $classes;
	}

	public function set_defaults() {
		$choices = [];
		foreach ( self::gfrcp_get_rcp_levels() as $level ) {
			$choice    = 'new Choice("' . $level->name . '", ' . json_encode( $level->name ) . ', "' . rcp_currency_filter( $level->price ) . '")';
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


