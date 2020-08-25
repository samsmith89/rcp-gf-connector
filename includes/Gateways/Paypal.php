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
//		add_action( 'gform_post_payment_status', [ $this, 'gfrcp_paypal_ipn' ], 10, 8);
	}

	public static function gfrcp_paypal_ipn( $feed, $entry, $status, $transaction_id, $subscriber_id, $amount, $pending_reason, $reason ) {

		$membership_id = gform_get_meta( $entry['id'], 'rcp_membership_id' );

		$membership = rcp_get_membership($membership_id);

		$payment = new RCP_Payments();

		$payment_data = [
//			'subscription'          => $action['subscription_id'], Need to create in ipn action
			'object_id'             => $membership->get_object_id(),
			'object_type'           => $membership->get_object_type(),
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => $amount, // Total amount after fees/credits/discounts are added.
			'user_id'               => $membership->get_user_id(),
			'customer_id'           => $membership->get_customer_id(), // want RCP id
			'membership_id'         => $membership->get_id(),
			'payment_type'          => '',
			'transaction_type'      => 'new',
			'subscription_key'      => '',
			'transaction_id'        => '',
			'status'                => 'complete',
			'gateway'               => $membership->get_gateway(),
			'subtotal'              => $amount, // Base price of the membership level.
			'credits'               => 0.00, // Proration credits.
			'fees'                  => 0.00, // Fees.
			'discount_amount'       => 0.00, // Discount amount from discount code.
			'discount_code'         => ''
		];

		$payment->insert( $payment_data );

//		return $form['id'];
	}
}
