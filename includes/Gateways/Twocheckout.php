<?php

namespace GF_RCP\Gateways;

use RCP_Payments;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Twocheckout {
	/**
	 * @var
	 */
	protected static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Twocheckout ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

	}

	public static function gfrcp_stripe_webhook($action, $event) {

		$membership_id = gform_get_meta( $action['entry_id'], 'gfrcp_membership_id' );

		$membership = rcp_get_membership($membership_id);

		$payment = new RCP_Payments();

		$payment_data = [
			'subscription'          => $action['subscription_id'],
			'object_id'             => $membership->get_object_id(),
			'object_type'           => $membership->get_object_type(),
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => $action['amount'], // Total amount after fees/credits/discounts are added.
			'user_id'               => $membership->get_user_id(),
			'customer_id'           => $membership->get_customer_id(), // want RCP id
			'membership_id'         => $membership->get_id(),
			'payment_type'          => '',
			'transaction_type'      => 'new',
			'subscription_key'      => '',
			'transaction_id'        => '',
			'status'                => 'complete',
			'gateway'               => $membership->get_gateway(),
			'subtotal'              => $action['amount'], // Base price of the membership level.
			'credits'               => 0.00, // Proration credits.
			'fees'                  => 0.00, // Fees.
			'discount_amount'       => 0.00, // Discount amount from discount code.
			'discount_code'         => ''
		];

		$payment->insert( $payment_data );

		return $action;
	}
}
