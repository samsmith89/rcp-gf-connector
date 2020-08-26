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
			'status' => 'active',
			'gateway_customer_id' => $subscription['customer_id'],
			'gateway_subscription_id' => $subscription['subscription_id'],
		];

		$payment_gateway = gform_get_meta( $entry['id'], 'payment_gateway' );

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
				$defaults['gateway'] = '';
		};

		rcp_update_membership( GravityFeed::$membership_id, $defaults  );

		$membership = rcp_get_membership(GravityFeed::$membership_id);

		$pending_payments = [];
		$payments = $membership->get_payments();

		foreach ( $payments as $pmt ) {
			if ( 'pending' === $pmt->status ) {
				$pending_payments[] = $pmt;
			}
		}

		$latest_pending_payment = end($pending_payments);
		$payment_obj = new RCP_Payments(); // there are payment statuses in RCP. Use get_payments_membership from membership object

		// This will be a payment update not creation. rcp_log();
		//access the membership object then use method get_payments(). if there are payments and there is only 1 use it. Else grab last PHP "end($payments)"pending payment
		$payment_data = [
			'subscription_key'      => '',
			'transaction_id'        => '',
			'status'                => 'complete',
			'gateway'               => $membership->get_gateway(),
		];

		$payment_obj->update( $latest_pending_payment->id, $payment_data );
	}
}
