<?php

namespace GF_RCP;

use GF_RCP\GravityFeed as GravityFeed;
use RCP_Payments;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Gateways {
	/**
	 * @var
	 */
	protected static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Gateways ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'gform_post_subscription_started', [ $this, 'gfrcp_process_payment' ], 10, 2 );
	}
	// gform_post_subscription_started
	// gform_post_payment_completed

	public static function gfrcp_process_payment($entry, $subscription) {
		$defaults = [
			'inital_amount' => $entry['payment_amount'], //in feed
			'recurring_amount' => $entry['payment_amount'], //in feed
			'auto_renew' => true, //in feed
			'times_billed' => '1', //in feed
			'status' => 'active',
			'gateway_customer_id' => $subscription['customer_id'],
			'gateway_subscription_id' => $subscription['subscription_id'],
		];

		$payment_gateway = gform_get_meta( $entry['id'], 'payment_gateway' );

		//set up the switch to add the gateway specific args from above
		switch ( $payment_gateway ) {
			case 'gravityformsstripe':
				$defaults['gateway'] = 'stripe';
				break;
			case 'gravityformspaypal':
				$defaults['gateway'] = 'paypal';
				break;
			case 'gravityformspaypalpaymentspro':
				$defaults['gateway'] = 'paypal_pro';
				break;
			case 'gravityforms2checkout':
				$defaults['gateway'] = 'twocheckout';
				break;
			default:
				$defaults['gateway'] = 'generic';
		};

//		$merged = wp_parse_args( $args, $defaults );

		rcp_update_membership( GravityFeed::$membership_id, $defaults  );

		$membership = rcp_get_membership(GravityFeed::$membership_id);

		$payment = new RCP_Payments(); // there are payment statuses in RCP. Use get_payments_membership from membership object

		// This will be a payment update not creation. rcp_log();
		//access the membership object then use method get_payments(). if there are payments and there is only 1 use it. Else grab last PHP "end($payments)"pending payment
		$payment_data = [
			'subscription'          => $subscription['subscription_id'],
			'object_id'             => $membership->get_object_id(),
			'object_type'           => $membership->get_object_type(),
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => $entry['payment_amount'], // Total amount after fees/credits/discounts are added.
			'user_id'               => $membership->get_user_id(),
			'customer_id'           => $membership->get_customer_id(), // want RCP id
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
