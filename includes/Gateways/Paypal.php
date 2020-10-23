<?php

namespace GF_RCP\Gateways;

use RCP_Payments;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Paypal {
	/**
	 * @var
	 */
	protected static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Paypal ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'gform_paypal_post_ipn', [ $this, 'gfrcp_paypal_ipn' ], 10, 2);
	}

	public function gfrcp_paypal_ipn( $entry, $feed ) {

		$membership_id = gform_get_meta( $feed['id'], 'gfrcp_membership_id' );

		$membership = rcp_get_membership($membership_id);

		$payment = new RCP_Payments();

		$payment_data = [
			'subscription'          => $membership->get_membership_level_name(),
			'object_id'             => $membership->get_object_id(),
			'object_type'           => 'subscription',
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => $entry['mc_gross'], // Total amount after fees/credits/discounts are added.
			'user_id'               => $membership->get_user_id(),
			'customer_id'           => $membership->get_customer_id(), // want RCP id
			'membership_id'         => $membership->get_id(),
			'payment_type'          => '',
			'transaction_type'      => 'renewal',
			'subscription_key'      => $membership->get_subscription_key(),
			'transaction_id'        => $entry['txn_id'], //get this from incoming IPN
			'status'                => 'complete',
			'gateway'               => $membership->get_gateway(),
			'subtotal'              => $entry['mc_gross'], // Base price of the membership level.
		];

		$payment->insert( $payment_data );
	}
}
