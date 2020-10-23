<?php

namespace GF_RCP\Gateways;

use RCP_Payments;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Stripe {
	/**
	 * @var
	 */
	protected static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Stripe ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_filter( 'gform_stripe_webhook', [ $this, 'gfrcp_stripe_webhook' ], 10, 2 );
	}

	public static function gfrcp_stripe_webhook($action, $event) {

		$membership_id = gform_get_meta( $action['entry_id'], 'gfrcp_membership_id' );

		$membership = rcp_get_membership($membership_id);

		$payment = new RCP_Payments();

		$payment_data = [
			'subscription'          => $membership->get_membership_level_name(),
			'object_id'             => $membership->get_object_id(),
			'object_type'           => 'subscription',
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => $action['amount'], // Total amount after fees/credits/discounts are added.
			'user_id'               => $membership->get_user_id(),
			'customer_id'           => $membership->get_customer_id(), // want RCP id
			'membership_id'         => $membership->get_id(),
			'payment_type'          => '',
			'transaction_type'      => 'renewal',
			'subscription_key'      => $membership->get_subscription_key(),
			'transaction_id'        => '', //get this from incoming JSON
			'status'                => 'complete',
			'gateway'               => $membership->get_gateway(),
			'subtotal'              => $action['amount'], // Base price of the membership level.
		];

		$payment->insert( $payment_data );

		return $action;
	}
}
