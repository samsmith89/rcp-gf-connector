<?php

namespace GF_RCP;

use GFFeedAddOn;
use GFAPI;
use GFForms;
use GFCommon;
use RGCurrency;
use RCP_Levels;
use RCP_Payments;

GFForms::include_feed_addon_framework();

class GravityFeed extends GFFeedAddOn {

	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gfrcp';
	protected $_path = 'gfrcp/gfrcp.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms - Restrict Content Pro Addon';
	protected $_short_title = 'GF RCP Connector';

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GravityFeed();
		}

		return self::$_instance;
	}

	/* Feed settings fields */
	public function feed_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'RCP Connector Settings', 'rcp-gravity-forms' ),
				'fields' => array(
					array(
						'label'             => 'GFRCP Feed Name',
						'type'              => 'text',
						'name'              => 'gfrcp_feed_name',
						'tooltip'           => 'This is the name provided to the feed',
						'class'             => 'medium',
						'required'          => 'true',
						'feedback_callback' => array( $this, 'is_valid_setting' )
					),
					array(
						'label'   => 'Enable RCP Connector',
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'tooltip' => 'Enable this setting to connect your form submissions to RCP',
						'choices' => array(
							array(
								'label' => 'Enabled',
								'name'  => 'enabled'
							)
						)
					),
				),
			),
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
			)
		);
	}

	/* Mapped fields within feed settings */
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
				'default_value' => $this->get_first_field_by_type( 'email', 6 ),
			),
			array(
				'name'          => 'rcp_password',
				'label'         => esc_html__( 'Password', 'rcp-gravity-forms' ),
				'required'      => true,
				'field_type'    => array( 'rcp_password', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'rcp_password' ),
			),
			array(
				'label'         => 'Membership Level',
				'type'          => 'select',
				'name'          => 'membership',
				'required'      => 'true',
				'default_value' => $this->get_first_field_by_type( 'membership' ),
			),
		);
	}

	/* Display information in bulk feeds view */
	public function feed_list_columns() {
		return array(
			'gfrcp_feed_name' => __( 'Feed Name', 'gfrcp' ),
		);
	}

	public function process_feed( $feed, $entry, $form ) {
		/* Setting variables for use in feed */
		$username            = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_username' ) );
		$email               = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_useremail' ) );
		$password            = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_rcp_password' ) );
		$membership_level_id = $feed['meta']['RCPMembershipFields_membership'];
		$membership_level    = rgexplode( '|', $entry[ $membership_level_id ], 2 );

		/* Setting initial fee variable if set through payment gateway */
		$fee_field = GFAPI::get_fields_by_type( $form, array( 'product' ) );
		$fee       = $fee_field[0]->basePrice;

		/* Router to determine which payment gateway is being used */
		global $gf_payment_gateway;

		switch ( $gf_payment_gateway ) {
			case 'gravityformsstripe':
				$payment_gateway = 'stripe';
				break;
			case 'gravityformspaypal':
				$payment_gateway = 'paypal';
				break;
			case 'gravityformspaypalpaymentspro':
				$payment_gateway = 'paypal_pro';
				break;
			case 'gravityforms2checkout':
				$payment_gateway = 'twocheckout';
				break;
			default:
				$payment_gateway = '';
		};

		/* Create user */
		$user_id = wp_create_user( $username, $password, $email );

		/* Create RCP Customer */
		$customer_id = rcp_add_customer( [ 'user_id' => $user_id ] );

		/* Determine membership level */
		$level_id  = '';
		$levels_db = new RCP_Levels();
		$levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

		/* Loop through fields to determine the membership level */
		foreach ( $levels as $level ) {
			if ( $level->name === $membership_level[0] ) {
				$level_id = $level->id;
			}
		}

		/* Defaults for all memberships */
		$membership_defaults = [
			'inital_amount'    => $entry['payment_amount'],
			'recurring_amount' => $membership_level[1],
			'auto_renew'       => true,
			'times_billed'     => '1',
			'customer_id'      => $customer_id,
			'object_id'        => $level_id,
			'status'           => 'pending',
			'gateway'          => $payment_gateway,
			'activated_date'   => current_time( 'mysql' )
		];

		/* If membership level is trialing mark that here */
		if ( ! empty( Gateways::$gfrcp_trial ) ) {
			$customer        = rcp_get_customer( $customer_id );
			$has_trial       = true;
			$set_trial       = ( $has_trial && ! $customer->has_trialed() );
			$expiration_date = Gateways::$gfrcp_expiration_date;

			/* Auto calculate expiration */
			if ( empty( $membership_defaults['expiration_date'] ) && ! empty( $expiration_date ) ) {
				$membership_defaults['expiration_date'] = $expiration_date;
			}

			/*Auto calculate trial end date. */
			if ( $set_trial && empty( $membership_defaults['trial_end_date'] ) && ! empty( $expiration_date ) ) {
				$membership_defaults['trial_end_date'] = $expiration_date;
			}
		}

		/* Create the membership */
		$membership_id = rcp_add_membership( $membership_defaults );

		/* Get membership object */
		$membership = rcp_get_membership( $membership_id );

		/* Get payment object */
		$payment_obj = new RCP_Payments();

		$payment_data = [
			'subscription'     => $membership_level[0],
			'object_id'        => $membership->get_object_id(),
			'object_type'      => $membership->get_object_type(),
			'date'             => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'           => $membership_level[1],
			'user_id'          => $membership->get_user_id(),
			'customer_id'      => $membership->get_customer_id(),
			'membership_id'    => $membership->get_id(),
			'payment_type'     => '',
			'transaction_type' => 'new',
			'subscription_key' => '',
			'transaction_id'   => '',
			'status'           => 'pending',
			'subtotal'         => $membership_level[1],
			'credits'          => 0.00,
			'fees'             => 0.00,
			'discount_amount'  => 0.00,
			'discount_code'    => '',
			'gateway'          => $payment_gateway
		];

		/* Add meta to payment If there's a fee set through the payment gateway */
		if ( isset( $fee ) && ! empty( $fee ) ) {
			/* Get currency from Gravity Forms */
			$code = empty( $currency ) ? GFCommon::get_currency() : $currency;
			if ( empty( $code ) ) {
				$code = 'USD';
			}

			$currency = RGCurrency::get_currency( $code );

			$gf_pay_func = new RGCurrency( $currency );

			$clean_fee              = $gf_pay_func->to_number( $fee );
			$payment_data['fees']   = $clean_fee;
			$payment_data['amount'] = $membership_level[1] + $clean_fee;
		}

		/* Create payment */
		$payment_obj->insert( $payment_data );

		/* Add meta to entry for later functionality */
		gform_update_meta( $entry['id'], 'is_gfrcp_enabled', $feed['meta']['enabled'] );
		gform_update_meta( $entry['id'], 'gfrcp_membership_id', $membership_id );
	}
}