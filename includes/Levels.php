<?php

namespace GF_RCP;

use RCP_Levels;

/**
 * Handles functionality around Levels in RCP.
 *
 * @since 1.0.0
 *
 */

class Levels {
	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @since 1.0.0
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Levels ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Adds the actions related to RCP levels.
	 *
	 * @since 1.0.0
	 */

	protected function __construct() {
		add_action( 'rcp_edit_subscription_form', [ $this, 'edit_RCP_level_settings' ], 10 );
		add_action( 'rcp_add_subscription_form', [ $this, 'edit_add_RCP_level_settings' ], 10 );
		add_action( 'rcp_action_edit-subscription', [ $this, 'process_RCP_level_settings_change' ], 10 );
		add_action( 'rcp_action_add-level', [ $this, 'process_RCP_level_settings_add' ], 10 );
	}

	/**
	 * Summary.
	 *
	 * @since 1.0.0
	 *
	 * @see rcp_is_rcp_admin_page()
	 *
	 * @param object $level
	 */

	public function edit_RCP_level_settings($level) {
		$levels = new RCP_Levels();
		?><tr class="form-field">
				<th scope="row" valign="top">
					<label for="gfrcp_is_connected">Gravity Forms Connection</label>
				</th>
				<td>
					<input type="checkbox" value="1" name="is_gfrcp" id="gfrcp_is_connected" <?php echo $levels->get_meta( $level->id, 'is_gfrcp', true ) ? 'checked' : '' ?>/>
					<span class="description">Enable this setting to connect this membership level to Gravity Forms and have it shown as an available option in the membership field.</span>
				</td>
			</tr>
		<?php

		$is_rcp_page = rcp_is_rcp_admin_page();

		if ($is_rcp_page) {
			wp_enqueue_script( 'gfrcp-rcp', plugins_url( 'gfrcp/assets/js/rcp.js', 'gfrcp' ) );
		}
	}

	/**
	 * Summary.
     *
	 * @since 1.0.0
	 *
	 * @param object $level.
	 */

	public function edit_add_RCP_level_settings($level) {
		?><tr class="form-field">
				<th scope="row" valign="top">
					<label for="gfrcp_is_connected">Gravity Forms Connection</label>
				</th>
				<td>
					<input type="checkbox" value="1" name="is_gfrcp" id="gfrcp_is_connected" />
					<p class="description">Enable this setting to connect this membership level to Gravity Forms and have it shown as an available option in the membership field. If this setting is enabled the above Duration, Maximum Renewals, Free Trial Duration, and Signup Fee will need to be configured in your Gravity Form payment gateway feed settings.</p>
				</td>
			</tr>
		<?php
	}

	/**
	 * Functionality when a membership level is being edited.
	 *
	 * @since 1.0.0
	 *
	 * @see RCP_Levels::update();
	 * @see RCP_Levels::update_meta();
	 */

	public function process_RCP_level_settings_change() {
		if ( ! wp_verify_nonce( $_POST['rcp_edit_level_nonce'], 'rcp_edit_level_nonce' ) ) {
			wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}

		if ( ! current_user_can( 'rcp_manage_levels' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}

		$data = $_POST;

		// Disable payment plan if maximum renewals set to "Until Cancelled".
		if ( ! empty( $data['maximum_renewals_setting'] ) && 'forever' == $data['maximum_renewals_setting'] ) {
			$data['maximum_renewals'] = 0;
		}

		$levels = new RCP_Levels();
		$update = $levels->update( absint( $data['subscription_id'] ), $data );

		$levels->update_meta( $data['subscription_id'], 'is_gfrcp', $data['is_gfrcp'] );

		if ( $update && ! is_wp_error( $update ) ) {
			$url = admin_url( 'admin.php?page=rcp-member-levels&rcp_message=level_updated' );
		} else {
			if ( is_wp_error( $update ) ) {
				$url = add_query_arg( 'rcp_message', urlencode( $update->get_error_code() ), 'admin.php?page=rcp-member-levels' );
			} else {
				$url = admin_url( 'admin.php?page=rcp-member-levels&rcp_message=level_not_updated' );
			}
		}
	}

	/**
	 * Functionality when a membership level is being added.
	 *
	 * @since 1.0.0
	 *
	 * @see RCP_Levels::insert();
	 * @see RCP_Levels::update_meta();
	 */

	public function process_RCP_level_settings_add() {
		if ( ! wp_verify_nonce( $_POST['rcp_add_level_nonce'], 'rcp_add_level_nonce' ) ) {
			wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}

		if ( ! current_user_can( 'rcp_manage_levels' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}

		if ( empty( $_POST['name'] ) ) {
			rcp_log( 'Failed creating new membership level: empty membership name.', true );
			$url = admin_url( 'admin.php?page=rcp-member-levels&rcp_message=level_missing_fields' );
			wp_safe_redirect( esc_url_raw( $url ) );
			exit;
		}

		$data = $_POST;

		// Disable payment plan if maximum renewals set to "Until Cancelled".
		if ( ! empty( $data['maximum_renewals_setting'] ) && 'forever' == $data['maximum_renewals_setting'] ) {
			$data['maximum_renewals'] = 0;
		}

		$levels = new RCP_Levels();

		$level_id = $levels->insert( $data );

		$levels->update_meta( $level_id, 'is_gfrcp', $data['is_gfrcp'] );

		if ( $level_id && ! is_wp_error( $level_id ) ) {
			$url = admin_url( 'admin.php?page=rcp-member-levels&rcp_message=level_added' );
		} else {
			if ( is_wp_error( $level_id ) ) {
				$url = add_query_arg( 'rcp_message', urlencode( $level_id->get_error_code() ), 'admin.php?page=rcp-member-levels' );
			} else {
				$url = admin_url( 'admin.php?page=rcp-member-levels&rcp_message=level_not_added' );
			}
		}
		wp_safe_redirect( $url );
		exit;
	}
}