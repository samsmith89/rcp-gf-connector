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
		add_action( 'gform_post_add_subscription_payment', [ $this, 'gfrcp_process_subscription' ], 10, 2 );
	}
	// gform_post_subscription_started: FOR SUBSCRIPTION PAYMENTS
	// gform_post_payment_completed: FOR SINGLE PAYMENTS

	public function gfrcp_process_payment( $entry, $subscription ) {
		if ( gform_get_meta( $entry['id'], 'is_gfrcp_enabled' ) == true ) {
			$defaults = [
				'status'                  => 'active',
				'gateway_customer_id'     => $subscription['customer_id'],
				'gateway_subscription_id' => $subscription['subscription_id'],
			];

			rcp_update_membership( GravityFeed::$membership_id, $defaults );
		}
	}

	public function gfrcp_process_subscription( $entry, $action ) {
		if ( gform_get_meta( $entry['id'], 'is_gfrcp_enabled' ) == true ) {
			$membership_id = gform_get_meta( $entry['id'], 'rcp_membership_id' );
			$membership    = rcp_get_membership( $membership_id );
			$db_payment    = $this->gfrcp_get_transaction_id( $entry );

			if ( $db_payment !== $action['transaction_id'] ) {
				$payment = new RCP_Payments();

				$payment_data = [
					'subscription'     => $membership->get_membership_level_name(),
					'object_id'        => $membership->get_object_id(),
					'object_type'      => 'subscription',
					'date'             => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
					'amount'           => $action['amount'],
					'user_id'          => $membership->get_user_id(),
					'customer_id'      => $membership->get_customer_id(),
					'membership_id'    => $membership->get_id(),
					'payment_type'     => '',
					'transaction_type' => 'renewal',
					'subscription_key' => $membership->get_subscription_key(),
					'transaction_id'   => $action['transaction_id'],
					'status'           => 'complete',
					'gateway'          => $membership->get_gateway(),
					'subtotal'         => $action['amount']
				];

				$payment->insert( $payment_data );
			} else {
				$pending_payments = [];
				$payments         = $membership->get_payments();

				foreach ( $payments as $pmt ) {
					if ( 'pending' === $pmt->status ) {
						$pending_payments[] = $pmt;
					}
				}

				$latest_pending_payment = end( $pending_payments );
				$payment_obj            = new RCP_Payments(); // there are payment statuses in RCP. Use get_payments_membership from membership object

				// This will be a payment update not creation. rcp_log();
				//access the membership object then use method get_payments(). if there are payments and there is only 1 use it. Else grab last PHP "end($payments)"pending payment
				$payment_data = [
					'subscription_key' => '',
					'transaction_id'   => $this->gfrcp_get_transaction_id( $entry ),
					'status'           => 'complete',
				];

				$payment_obj->update( $latest_pending_payment->id, $payment_data );
			}
		}
	}

	public function gfrcp_get_transaction_id( $entry ) {
		global $wpdb;

		if ( $entry['id'] ) {
			$transaction_id = $wpdb->get_var( $wpdb->prepare(
				" SELECT transaction_id FROM {$wpdb->prefix}gf_addon_payment_transaction WHERE lead_id = %d ",
				$entry['id']
			) );
		} else if ( ( ! isset( $transaction_id ) ) && ( $entry['entry_id'] ) ) {
			$transaction_id = $wpdb->get_var( $wpdb->prepare(
				" SELECT transaction_id FROM {$wpdb->prefix}gf_addon_payment_transaction WHERE lead_id = %d ",
				$entry['entry_id']
			) );
		} else {
			$transaction_id = 'nope';
		}

		return $transaction_id;
	}
}
