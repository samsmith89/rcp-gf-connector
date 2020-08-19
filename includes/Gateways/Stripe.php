<?php

namespace GF_RCP\Gateways;

use GF_RCP\GravityFeed as GravityFeed;
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
		add_action( 'gform_post_subscription_started', [ $this, 'gfrcp_stripe_get_payment' ], 10, 2 );
		// gform_post_subscription_started
		// gform_post_payment_completed
	}

	public static function gfrcp_stripe_get_payment($entry, $subscription) {
		$args = [
			'inital_amount' => $entry['payment_amount'],
			'recurring_amount' => $entry['payment_amount'],
			'auto_renew' => true,
			'times_billed' => '1',// see if this can be configured from the settings
			'status' => 'active',
			'gateway_customer_id' => $subscription['customer_id'],
			'gateway_subscription_id' => $subscription['subscription_id'],
			'gateway' => 'stripe',


		];
		rcp_update_membership( GravityFeed::$membership_id, $args );

		$membership = rcp_get_membership(GravityFeed::$membership_id);

		$payment = new RCP_Payments();

		$payment_data = [
			'subscription'          => $subscription['subscription_id'],
			'object_id'             => $membership->get_object_id(),
			'object_type'           => $membership->get_object_type(),
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => $entry['payment_amount'], // Total amount after fees/credits/discounts are added.
			'user_id'               => $membership->get_user_id(),
			'customer_id'           => $subscription['customer_id'],
			'membership_id'         => $membership->get_id(),
			'payment_type'          => '',
			'transaction_type'      => 'new',
			'subscription_key'      => '',
			'transaction_id'        => '',
			'status'                => 'complete',
			'gateway'               => $membership->get_gateway(),
			'subtotal'              => $entry['payment_amount'], // Base price of the membership level.
			'credits'               => 0.00, // Proration credits.
			'fees'                  => 0.00, // Fees.
			'discount_amount'       => 0.00, // Discount amount from discount code.
			'discount_code'         => ''
		];

		$payment->insert( $payment_data );
	}
}
