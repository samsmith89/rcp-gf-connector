<?php

namespace GF_RCP;

use RCP_Payments;
use GFFormsModel;
use GFAPI;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Gateways {
	/**
	 * @var
	 */
	protected static $_instance;
	public static $transaction_id = '';
	public static $gfrcp_trial_enabled = '';
	public static $gfrcp_expiration_date = '';
	public static $gfrcp_fee_enabled = '';
	public static $gfrcp_fee_amount = '';

	public static function get_instance() {
		if ( ! self::$_instance instanceof Gateways ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'gform_post_subscription_started', [ $this, 'gfrcp_process_subscription_payment' ], 10, 2 );
		add_action( 'gform_post_payment_completed', [ $this, 'gfrcp_process_single_payment' ], 10, 2 );
		add_action( 'gform_post_add_subscription_payment', [ $this, 'gfrcp_process_subscription' ], 10, 2 );
		add_filter( 'gform_submission_data_pre_process_payment', [ $this, 'gfrcp_process_trial' ], 10, 4 );
		add_filter( 'gform_submission_data_pre_process_payment', [ $this, 'gfrcp_process_fee' ], 10, 4 );
	}
	// gform_post_subscription_started: FOR SUBSCRIPTION PAYMENTS
	// gform_post_payment_completed: FOR SINGLE PAYMENTS

	public function gfrcp_process_subscription_payment( $entry, $subscription ) {
		if ( gform_get_meta( $entry['id'], 'is_gfrcp_enabled' ) == true ) {
			$defaults = [
				'status'                  => 'active',
				'gateway_customer_id'     => $subscription['customer_id'],
				'gateway_subscription_id' => $subscription['subscription_id'],
			];

			rcp_update_membership( gform_get_meta( $entry['id'], 'gfrcp_membership_id' ), $defaults );
		}
	}

	public function gfrcp_process_single_payment( $entry, $action ) {
		if ( gform_get_meta( $entry['id'], 'is_gfrcp_enabled' ) == true ) {

			$membership_id    = gform_get_meta( $entry['id'], 'gfrcp_membership_id' );
			$membership       = rcp_get_membership( $membership_id );
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
				'transaction_id'   => $action['transaction_id'],
				'status'           => 'complete',
			];

			$payment_obj->update( $latest_pending_payment->id, $payment_data );

			$defaults = [
				'status' => 'active',
			];

			rcp_update_membership( gform_get_meta( $entry['id'], 'gfrcp_membership_id' ), $defaults );
		}
	}

	public function gfrcp_process_subscription( $entry, $action ) {
		if ( gform_get_meta( $entry['id'], 'is_gfrcp_enabled' ) == true ) {
			$membership_id = gform_get_meta( $entry['id'], 'gfrcp_membership_id' );
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

//			$defaults = [
//				'status' => 'active',
//			];
//
//			rcp_update_membership( gform_get_meta( $entry['id'], 'gfrcp_membership_id' ), $defaults );
		}
	}

	public function gfrcp_process_trial( $submission_data, $feed, $form, $entry ) {
		if ( $this->check_if_gfrcp( $form ) ) {
			self::$gfrcp_trial_enabled = $feed['meta']['trial_enabled'];
			if ( self::$gfrcp_trial_enabled == true ) {
				if ( $feed['addon_slug'] === 'gravityformsstripe' ) {
					$trial_length                = $feed['meta']['trialPeriod'];
					$expiration_unit             = $feed['meta']['billingCycle_unit'];
					self::$gfrcp_expiration_date = $this->gfrcp_calculate_trial( $feed, $expiration_unit, $trial_length );
				}
				if ( $feed['addon_slug'] === 'gravityformspaypal' ) {
					$trial_length                = $feed['meta']['trialPeriod_length'];
					$expiration_unit             = $feed['meta']['trialPeriod_unit'];
					self::$gfrcp_expiration_date = $this->gfrcp_calculate_trial( $feed, $expiration_unit, $trial_length );
				}
			}
		}

		return $submission_data;
	}

	public function gfrcp_process_fee( $submission_data, $feed, $form, $entry ) {
		if ( $this->check_if_gfrcp( $form ) ) {
			if ( $feed['addon_slug'] === 'gravityformsstripe' ) {
				self::$gfrcp_fee_enabled = $feed['meta']['setupFee_enabled'];
				if ( self::$gfrcp_fee_enabled == true ) {
					$field_id               = $feed['meta']['setupFee_product'];
					$field                  = GFAPI::get_field( $form, $field_id );
					self::$gfrcp_fee_amount = GFFormsModel::get_field_value( $field )[ $field_id . '.2' ];
//					self::$gfrcp_fee_amount = $feed['meta']['setupFee_product'];
				}
			}
			if ( $feed['addon_slug'] === 'gravityformspaypal' ) {
				if ( self::$gfrcp_trial_enabled == true ) {
					$trial_product = $feed['meta']['trial_product'];
					$trial_amount  = $feed['meta']['trial_amount'];
					if ( $trial_product == 'enter_amount' ) {
						self::$gfrcp_fee_amount  = $trial_amount;
						self::$gfrcp_fee_enabled = true;
					} elseif ( ! empty( $trial_amount ) ) {
						$field_id                = $trial_product;
						$field                   = GFAPI::get_field( $form, $field_id );
						self::$gfrcp_fee_amount  = GFFormsModel::get_field_value( $field )[ $field_id . '.2' ];
						self::$gfrcp_fee_enabled = true;
					}
				}
			}
			if ( $feed['addon_slug'] === 'gravityformspaypalpaymentspro' ) {
				self::$gfrcp_fee_enabled = $feed['meta']['setupFee_enabled'];
				if ( self::$gfrcp_fee_enabled == true ) {
					self::$gfrcp_fee_amount = $feed['meta']['setupFee_product'];
				}
			}
		}

		return $submission_data;
	}

	public function gfrcp_get_transaction_id( $entry ) {
		global $wpdb;
		$entry_id = '';

		if ( isset( $entry['id'] ) && ! empty( $entry['id'] ) ) {
			$entry_id = $entry['id'];
		}

		if ( isset( $entry['entry_id'] ) && ! empty( $entry['entry_id'] ) ) {
			$entry_id = $entry['entry_id'];
		}

		if ( ! empty( $entry_id ) ) {
			self::$transaction_id = $wpdb->get_var( $wpdb->prepare(
				" SELECT transaction_id FROM {$wpdb->prefix}gf_addon_payment_transaction WHERE lead_id = %d ",
				$entry_id
			) );
		}

		return self::$transaction_id;
	}

	public function gfrcp_calculate_trial( $feed, $expiration_unit, $expiration_length ) {

		$current_time = current_time( 'timestamp' );

		$expiration_timestamp = strtotime( '+' . $expiration_length . ' ' . $expiration_unit . ' 23:59:59', $current_time );
		$expiration_date      = date( 'Y-m-d H:i:s', $expiration_timestamp );

		$extension_days = array( '29', '30', '31' );

		if ( in_array( date( 'j', $expiration_timestamp ), $extension_days ) && 'month' === $expiration_unit ) {
			/*
			 * Here we extend the expiration date by 1-3 days in order to account for "walking" payment dates in PayPal.
			 *
			 * See https://github.com/pippinsplugins/restrict-content-pro/issues/239
			 */

			$month = date( 'n', $expiration_timestamp );

			if ( $month < 12 ) {
				$month += 1;
				$year  = date( 'Y', $expiration_timestamp );
			} else {
				$month = 1;
				$year  = date( 'Y', $expiration_timestamp ) + 1;
			}

			$timestamp       = mktime( 0, 0, 0, $month, 1, $year );
			$expiration_date = date( 'Y-m-d 23:59:59', $timestamp );
		}

		return $expiration_date;
	}

	public function check_if_gfrcp( $form ) {
		if ( empty( $form ) || ! isset( $form['fields'] ) ) {
			return false;
		}
		foreach ( $form['fields'] as $field ) {
			if ( isset( $field['is_gfrcp'] ) ) {
				return true;
			}
		}

		return false;
	}
}
