<?php

namespace GF_RCP;

use GFFeedAddOn;
use GFForms;
use RCP_Levels;

GFForms::include_feed_addon_framework();

class GravityFeed extends GFFeedAddOn {

//    protected $_version = GF_SIMPLE_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'simpleaddon';
	protected $_path = 'simpleaddon/simpleaddon.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Simple Add-On';
	protected $_short_title = 'Simple Add-On';

	private static $_instance = null;
	public static $membership_id = '';

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GravityFeed();
		}

		return self::$_instance;
	}

	public function init() {
		parent::init();
		add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
	}

	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'my_script_js',
				'src'     => $this->get_base_url() . '/js/my_script.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'rcp-gravity-forms' ),
					'second' => esc_html__( 'Second Choice', 'rcp-gravity-forms' ),
					'third'  => esc_html__( 'Third Choice', 'rcp-gravity-forms' )
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'simpleaddon'
					)
				)
			),

		);

		return array_merge( parent::scripts(), $scripts );
	}

	public function styles() {
		$styles = array(
			array(
				'handle'  => 'my_styles_css',
				'src'     => $this->get_base_url() . '/css/my_styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) )
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}

	function form_submit_button( $button, $form ) {
		$settings = $this->get_form_settings( $form );
		if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
			$text   = $this->get_plugin_setting( 'mytextbox' );
			$button = "<div>{$text}</div>" . $button;
		}

		return $button;
	}

	public function plugin_page() {
		echo 'This page appears in the Forms menu';
	}

	public function feed_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Restrict Content Pro Mapping', 'rcp-gravity-forms' ),
				'fields' => array(
					array(
						'name'      => 'RCPMembershipFields',
						'label'     => esc_html__( 'Map Fields', 'rcp-gravity-forms' ),
						'type'      => 'field_map',
						'field_map' => $this->standard_fields_for_feed_mapping(),
						'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'sometextdomain' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective third-party service fields.', 'sometextdomain' )
					)
				)
			),
//			array(
//				'description' => '',
//				'fields'      => array(
//					array(
//						'name'     => 'feedName',
//						'label'    => esc_html__( 'Name', 'gravityforms' ),
//						'type'     => 'text',
//						'class'    => 'medium',
//						'required' => true,
//						'tooltip'  => '<h6>' . esc_html__( 'Name', 'gravityforms' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityforms' )
//					),
//					array(
//						'name'     => 'transactionType',
//						'label'    => esc_html__( 'Transaction Type', 'gravityforms' ),
//						'type'     => 'select',
//						'onchange' => "jQuery(this).parents('form').submit();",
//						'choices'  => array(
//							array(
//								'label' => esc_html__( 'Select a transaction type', 'gravityforms' ),
//								'value' => ''
//							),
//							array(
//								'label' => esc_html__( 'Products and Services', 'gravityforms' ),
//								'value' => 'product'
//							),
//							array( 'label' => esc_html__( 'Subscription', 'gravityforms' ), 'value' => 'subscription' ),
//						),
//						'tooltip'  => '<h6>' . esc_html__( 'Transaction Type', 'gravityforms' ) . '</h6>' . esc_html__( 'Select a transaction type.', 'gravityforms' )
//					),
//				)
//			),
//			array(
//				'title'      => esc_html__( 'Subscription Settings', 'gravityforms' ),
//				'dependency' => array(
//					'field'  => 'transactionType',
//					'values' => array( 'subscription' )
//				),
//				'fields'     => array(
//					array(
//						'name'     => 'recurringAmount',
//						'label'    => esc_html__( 'Recurring Amount', 'gravityforms' ),
//						'type'     => 'select',
//						'choices'  => $this->recurring_amount_choices(),
//						'required' => true,
//						'tooltip'  => '<h6>' . esc_html__( 'Recurring Amount', 'gravityforms' ) . '</h6>' . esc_html__( "Select which field determines the recurring payment amount, or select 'Form Total' to use the total of all pricing fields as the recurring amount.", 'gravityforms' )
//					),
//					array(
//						'name'    => 'billingCycle',
//						'label'   => esc_html__( 'Billing Cycle', 'gravityforms' ),
//						'type'    => 'billing_cycle',
//						'tooltip' => '<h6>' . esc_html__( 'Billing Cycle', 'gravityforms' ) . '</h6>' . esc_html__( 'Select your billing cycle.  This determines how often the recurring payment should occur.', 'gravityforms' )
//					),
//					array(
//						'name'    => 'recurringTimes',
//						'label'   => esc_html__( 'Recurring Times', 'gravityforms' ),
//						'type'    => 'select',
//						'choices' => array(
//							             array(
//								             'label' => esc_html__( 'infinite', 'gravityforms' ),
//								             'value' => '0'
//							             )
//						             ) + $this->get_numeric_choices( 1, 100 ),
//						'tooltip' => '<h6>' . esc_html__( 'Recurring Times', 'gravityforms' ) . '</h6>' . esc_html__( 'Select how many times the recurring payment should be made.  The default is to bill the customer until the subscription is canceled.', 'gravityforms' )
//					),
//					array(
//						'name'  => 'setupFee',
//						'label' => esc_html__( 'Setup Fee', 'gravityforms' ),
//						'type'  => 'setup_fee',
//					),
//					array(
//						'name'    => 'trial',
//						'label'   => esc_html__( 'Trial', 'gravityforms' ),
//						'type'    => 'trial',
//						'hidden'  => $this->get_setting( 'setupFee_enabled' ),
//						'tooltip' => '<h6>' . esc_html__( 'Trial Period', 'gravityforms' ) . '</h6>' . esc_html__( 'Enable a trial period.  The user\'s recurring payment will not begin until after this trial period.', 'gravityforms' )
//					),
//				)
//			),
//			array(
//				'title'      => esc_html__( 'Products &amp; Services Settings', 'gravityforms' ),
//				'dependency' => array(
//					'field'  => 'transactionType',
//					'values' => array( 'product', 'donation' )
//				),
//				'fields'     => array(
//					array(
//						'name'          => 'paymentAmount',
//						'label'         => esc_html__( 'Payment Amount', 'gravityforms' ),
//						'type'          => 'select',
//						'choices'       => $this->product_amount_choices(),
//						'required'      => true,
//						'default_value' => 'form_total',
//						'tooltip'       => '<h6>' . esc_html__( 'Payment Amount', 'gravityforms' ) . '</h6>' . esc_html__( "Select which field determines the payment amount, or select 'Form Total' to use the total of all pricing fields as the payment amount.", 'gravityforms' )
//					),
//				)
//			),
//			array(
//				'title'      => esc_html__( 'Other Settings', 'gravityforms' ),
//				'dependency' => array(
//					'field'  => 'transactionType',
//					'values' => array( 'subscription', 'product', 'donation' )
//				),
//				'fields'     => $this->other_settings_fields()
//			),
		);
	}

	public function settings_my_custom_field_type( $field, $echo = true ) {
		echo '<div>' . esc_html__( 'My custom field contains a few settings:', 'rcp-gravity-forms' ) . '</div>';

// get the text field settings from the main field and then render the text field
		$text_field = $field['args']['text'];
		$this->settings_text( $text_field );

// get the checkbox field settings from the main field and then render the checkbox field
		$checkbox_field = $field['args']['checkbox'];
		$this->settings_checkbox( $checkbox_field );
	}

	public function is_valid_setting( $value ) {
		return strlen( $value ) > 5;
	}

	public function standard_fields_for_feed_mapping() {
		return array(
			array(
				'name'          => 'username',
				'label'         => esc_html__( 'Username', 'rcp-gravity-forms' ),
				'required'      => true,
				'field_type'    => array( 'name', 'username', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', 3 ),
			),
			array(
				'name'          => 'useremail',
				'label'         => esc_html__( 'Email', 'rcp-gravity-forms' ),
				'required'      => true,
				'field_type'    => array( 'name', 'useremail', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', 6 ),
			),
			array(
				'name'          => 'rcp_password',
				'label'         => esc_html__( 'Password', 'rcp-gravity-forms' ),
				'required'      => false,
				'field_type'    => array( 'rcp_password', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'password' ),
			),
			array(
				'label'   => 'Membership Level',
				'type'    => 'select',
				'name'    => 'membership_level',
				'tooltip' => 'This is the tooltip',
				'choices' => array(
					array(
						'label' => 'First Choice',
						'value' => '1'
					),
					array(
						'label' => 'Second Choice',
						'value' => '2'
					),
					array(
						'label' => 'Third Choice',
						'value' => '3'
					)
				)
			),
		);
	}

	public function process_feed( $feed, $entry, $form ) {
		$username         = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_username' ) );
		$email            = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_useremail' ) );
		$password         = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_rcp_password' ) );
		$membership_level = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_membership_level' ) );

		$user_id = wp_create_user( $username, $password, $email );

		$customer_id = rcp_add_customer( array(
			'user_id' => $user_id
		) );

		$level_id  = '';
		$levels_db = new RCP_Levels();
		$levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

		foreach ( $levels as $level ) {
			if ( $level->name === $membership_level ) {
				$level_id = $level->id;
			}
		}

		self::$membership_id = rcp_add_membership( array(
			'customer_id' => $customer_id,
			'object_id'   => $level_id,
			'status'      => 'pending'
		) );

		//create inital pending payment

		gform_update_meta( $entry['id'], 'rcp_membership_id', self::$membership_id );

	}

}