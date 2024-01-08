<?php
	// only admins can get this
	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( $cap ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'pmpro-add-member-admin' ) );
}

	global $wpdb, $msg, $msgt, $pmpro_currency_symbol, $pmpro_required_user_fields, $pmpro_error_fields, $pmpro_msg, $pmpro_msgt;

	// BUG: Declare user_id to avoid Undefined variable warning
	$user_id = null;

	require_once( PMPRO_DIR . '/adminpages/admin_header.php' );

if ( ! empty( $_REQUEST['user'] ) ) {
	$user_id = intval( $_REQUEST['user'] );
	$user = get_userdata( $user_id );
	if ( empty( $user->ID ) ) {
		$user_id = false;
	}
} else {
		$user = get_userdata( 0 );
}

if ( ! empty( $user_id ) ) {
	$user_login = $user->user_login;
	$user_email = $user->user_email;
	$user_pass = '';
	$send_password = '';
	$user_notes = $user->user_notes;
	$role = '';
} else {
	if ( ! empty( $_POST['user_login'] ) ) {
		$user_login = sanitize_text_field( $_POST['user_login'] );
	} else {
		$user_login = '';
	}

	if ( ! empty( $_POST['user_email'] ) ) {
		$user_email = sanitize_text_field( $_POST['user_email'] );
	} else {
		$user_email = '';
	}

	if ( ! empty( $_POST['first_name'] ) ) {
		$first_name = sanitize_text_field( $_POST['first_name'] );
	} else {
		$first_name = '';
	}

	if ( ! empty( $_POST['last_name'] ) ) {
		$last_name = sanitize_text_field( $_POST['last_name'] );
	} else {
		$last_name = '';
	}

	if ( ! empty( $_POST['user_pass'] ) ) {
		$user_pass = sanitize_text_field( $_POST['user_pass'] );
	} else {
		$user_pass = '';
	}

	if ( ! empty( $_POST['send_password'] ) ) {
		$send_password = intval( $_POST['send_password'] );
	} else {
		$send_password = '';
	}

	if ( ! empty( $_POST['user_notes'] ) ) {
		$user_notes = sanitize_text_field( $_POST['user_notes'] );
	} else {
		$user_notes = '';
	}

	if ( ! empty( $_POST['role'] ) ) {
		$role = sanitize_text_field( $_POST['role'] );
	} else {
		$role = get_option( 'default_role' );
	}
}

if ( isset( $_POST['membership_level'] ) ) {
	$membership_level = intval( $_POST['membership_level'] );
} elseif ( ! empty( $user ) ) {
	$user->membership_level = pmpro_getMembershipLevelForUser( $user_id );
	if ( ! empty( $user->membership_level ) ) {
		$membership_level = $user->membership_level->id;
	} else {
		$membership_level = '';
	}
} else {
		$membership_level = '';
}

if ( ! empty( $_POST['payment'] ) ) {
	$payment = sanitize_text_field( $_POST['payment'] );
} else {
	$payment = 'payment';
}

if ( $payment == 'check' ) {
	$gateway = 'check';
} else {
	$gateway = 'free';
}

if ( ! empty( $_POST['total'] ) ) {
	$total = floatval( $_POST['total'] );
} else {
	$total = '';
}

if ( ! empty( $_POST['order_notes'] ) ) {
	$order_notes = sanitize_text_field( $_POST['order_notes'] );
} else {
	$order_notes = '';
}

if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'add_member' ) {
	// only if we don't have a user yet
	if ( empty( $user ) && empty( $user->ID ) ) {
		// check for required fields
		$pmpro_required_user_fields = apply_filters(
			'pmpro_add_member_required_user_fields', array(
				'user_login' => $user_login,
				'user_email' => $user_email,
			)
		);
		$pmpro_error_fields = array();
		foreach ( $pmpro_required_user_fields as $key => $value ) {
			if ( empty( $value ) ) {
				$pmpro_error_fields[] = $key;
			}
		}

		if ( ! empty( $pmpro_error_fields ) ) {
			pmpro_setMessage( esc_html__( 'Please fill out all required fields:', 'pmpro-add-member-admin' ) . ' ' . implode( ', ', $pmpro_error_fields ), 'pmpro_error' );
		}

		// check if user exists
		$oldusername = $wpdb->get_var( "SELECT user_login FROM $wpdb->users WHERE user_login = '" . esc_sql( $user_login ) . "' LIMIT 1" );
		$oldemail = $wpdb->get_var( "SELECT user_email FROM $wpdb->users WHERE user_email = '" . esc_sql( $user_email ) . "' LIMIT 1" );
		// this hook can be used to allow multiple accounts with the same email address
		$oldemail = apply_filters( 'pmpro_checkout_oldemail', $oldemail );

		if ( ! empty( $oldusername ) ) {
			pmpro_setMessage( esc_html__( 'That username is already taken. Please try another.', 'pmpro-add-member-admin' ), 'pmpro_error' );
			$pmpro_error_fields[] = 'username';
		}
		if ( ! empty( $oldemail ) ) {
			pmpro_setMessage( esc_html__( 'That email address is already taken. Please try another.', 'pmpro-add-member-admin' ), 'pmpro_error' );
			$pmpro_error_fields[] = 'bemail';
			$pmpro_error_fields[] = 'bconfirmemail';
		}

		// okay so far?
		if ( $pmpro_msgt != 'pmpro_error' ) {
			// random password if needed
			if ( empty( $user_pass ) ) {
				$user_pass = wp_generate_password();
				$send_password = true; // Force this option to be true, if the password field was empty so the email may be sent.
			}

			// add user
			$user_id = wp_insert_user(
				array(
					'user_login' => $user_login,
					'user_pass' => $user_pass,
					'user_email' => $user_email,
					'first_name' => $first_name,
					'last_name' => $last_name,
					'role' => $role,
				)
			);
		}
	}

	if ( ! $user_id ) {
		pmpro_setMessage( esc_html__( 'Error creating user.', 'pmpro-add-member-admin' ), 'pmpro_error' );
	} else {
		// other user meta
		update_user_meta( $user_id, 'user_notes', $user_notes );

		// figure out start date
		$now = current_time( 'timestamp' );
		$startdate = date( 'Y-m-d', $now );

		// figure out end date
		if ( ! empty( $_REQUEST['expires'] ) ) {
			// update the expiration date
			$enddate = intval( $_REQUEST['expires_year'] ) . '-' . str_pad( intval( $_REQUEST['expires_month'] ), 2, '0', STR_PAD_LEFT ) . '-' . str_pad( intval( $_REQUEST['expires_day'] ), 2, '0', STR_PAD_LEFT );
		} else {
			$enddate = '';
		}

		// add membership level
		$custom_level = array(
			'user_id' => $user_id,
			'membership_id' => $membership_level,
			'code_id' => '',
			'initial_payment' => $total,
			'billing_amount' => '',
			'cycle_number' => '',
			'cycle_period' => '',
			'billing_limit' => '',
			'trial_amount' => '',
			'trial_limit' => '',
			'startdate' => $startdate,
			'enddate' => $enddate,
		);
		pmpro_changeMembershipLevel( $custom_level, $user_id );

		// add order
		// blank order for free levels
		if ( empty( $morder ) ) {
			$morder = new MemberOrder();
			$morder->InitialPayment = $total;
			$morder->Email = $user_email;
			$morder->gateway = $gateway;
			$morder->status = 'success';
		}
		// add an item to the history table, cancel old subscriptions
		if ( ! empty( $morder ) ) {
			$morder->user_id = $user_id;
			$morder->membership_id = $membership_level;
			$morder->notes = $order_notes;
			$morder->saveOrder();
		}

		$user = get_userdata( $user_id );
		$user->membership_level = pmpro_getSpecificMembershipLevelForUser( $user_id, $membership_level );
		do_action( 'pmpro_add_member_added', $user_id, $user, $morder );

		//Send user a welcome email
		pmproada_send_added_email( $user, $morder );

		//Send admin a notification of a new user
		pmproada_send_added_email_admin( $user, $morder );

		// notify user
		if ( $send_password ) {
			wp_new_user_notification( $user_id, null, 'user' );
		}

		// got here with no errors
		if ( $pmpro_msgt != 'pmpro_error' ) {
			// set message
			if ( ! empty( $_REQUEST['user'] ) ) {
				$pmpro_msg = esc_html__( 'Order added.', 'pmpro-add-member-admin' );
			} else {
				$pmpro_msg = esc_html__( 'Member added.', 'pmpro-add-member-admin' );
			}

			$pmpro_msgt = 'pmpro_success';

			// clear vars
			$payment = '';
			$gateway = '';
			$total = '';
			$order_notes = '';

			// clear user vars too if one wasn't passed in
			if ( empty( $_REQUEST['user'] ) ) {
				$user = get_userdata( 0 );
				$user_id = false;
				$user_login = '';
				$user_email = '';
				$first_name = '';
				$last_name = '';
				$user_pass = '';
				$user_notes = '';
			}
		} else {
			global $pmpro_msg;
			$pmpro_msg = esc_html__( 'The user account has been created, but there were other errors setting up membership: ', 'pmpro-add-member-admin' ) . $pmpro_msg;
		}
	}
}
?>
<style>
	form.pmpro-add-member tr {border-bottom: 1px solid #CCC;}
	input.pmpro_error {background-image: none; background-color: #F9D6CB}
</style>

<h2>
<?php
echo __( 'Add', 'pmpro-add-member-admin' ) . ' ';
if ( ! empty( $_REQUEST['user'] ) ) {
	esc_html_e( 'Order', 'pmpro-add-member-admin' );
} else {
	esc_html_e( 'Member', 'pmpro-add-member-admin' );
}
?>
</h2>

<?php
// If running PMPro v3.0, we want to show a message about the new Edit Member page.
if ( class_exists( 'PMPro_Subscription') ) {
?>
	<div class="notice notice-warning">
		<p>
			<?php
			if ( ! empty( $_REQUEST['user'] ) ) {
				echo sprintf( esc_html__( "As of Paid Memberships Pro v3.0, you can now manage a user's membership and order information from the new Edit Member page. Click %s to manage this user's membership data.", 'pmpro-add-member-admin' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-member&user_id=' . intval( $_REQUEST['user'] ) ) ) . '">' . esc_html__( 'here', 'pmpro-add-member-admin' ) . '</a>' );
			} else {
				echo sprintf( esc_html__( 'As of Paid Memberships Pro v3.0, you can now create a new member from the new Add Member page. Click %s to add a new member.', 'pmpro-add-member-admin' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-member' ) ) . '">' . esc_html__( 'here', 'pmpro-add-member-admin' ) . '</a>' );
			}
			?>
		</p>
	</div>
<?php
}
?>

<?php
if ( $pmpro_msg ) {
?>
	<div id="pmpro_message" class="pmpro_message <?php echo esc_attr( $pmpro_msgt ); ?>"><?php echo esc_html( $pmpro_msg ); ?></div>
<?php
} else {
?>
<div id="pmpro_message" class="pmpro_message" style="display: none;"></div>
<?php
}
?>

<form class="pmpro-add-member" action="" method="post">
	<input name="saveid" type="hidden" value="<?php echo isset( $edit ) ? $edit : null; ?>" />
		<table class="form-table">
		<tbody>
			<?php if ( ! empty( $user_id ) ) { ?>
				<tr class="user">
					<th scope="row" valign="top"><label for="user_id"><?php esc_html_e( 'User', 'pmpro-add-member-admin' ); ?></label></th>
					<td>
						<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ); ?>"><?php echo $user->display_name; ?></a>
						<input name="user_id" type="hidden" value="<?php echo esc_attr( $user_id ); ?>" />

						&nbsp;&nbsp;

						<a href="javascript: jQuery('.user-info').show(); jQuery('tr.user').hide(); void(0);"><?php esc_html_e( 'show more information', 'pmpro-add-member-admin' ); ?></a>
					</td>
				</tr>
			<?php } ?>

			<tr class="user-info" 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="user_login"><?php esc_html_e( 'Username', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
					if ( ! empty( $user_id ) ) {
						echo $user->user_login; } else {
?>
												<input name="user_login" type="text" autocomplete="off" size="50" class="<?php echo pmpro_getClassForField( 'user_login' ); ?>" value="<?php echo esc_attr( $user_login ); ?>" />
											<?php } ?>
				</td>
			</tr>
			<tr class="user-info" 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="user_email"><?php esc_html_e( 'Email', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
					if ( ! empty( $user_id ) ) {
						echo $user->user_email; } else {
?>
												<input name="user_email" id="user_email" type="text" autocomplete="off" size="50" class="<?php echo pmpro_getClassForField( 'user_email' ); ?>" value="<?php echo esc_attr( $user_email ); ?>" />
											<?php } ?>
				</td>
			</tr>
			<tr class="user-info" 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="first_name"><?php esc_html_e( 'First Name', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
					if ( ! empty( $user_id ) ) {
						echo $user->first_name; } else {
?>
												<input name="first_name" id="first_name" type="text" autocomplete="off" size="50" class="<?php echo pmpro_getClassForField( 'first_name' ); ?>" value="<?php echo esc_attr( $first_name ); ?>" />
											<?php } ?>
				</td>
			</tr>
			<tr class="user-info" 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="last_name"><?php esc_html_e( 'Last Name', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
					if ( ! empty( $user_id ) ) {
						echo $user->last_name; } else {
?>
												<input name="last_name" id="last_name" type="text" autocomplete="off" size="50" class="<?php echo pmpro_getClassForField( 'last_name' ); ?>" value="<?php echo esc_attr( $last_name ); ?>" />
											<?php } ?>
				</td>
			</tr>
			<tr 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="user_pass"><?php esc_html_e( 'Password', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<input name="user_pass" id="user_pass" type="password" autocomplete="off" size="25" class="<?php echo pmpro_getClassForField( 'user_pass' ); ?>" value="<?php echo esc_attr( $user_pass ); ?>" />
					<br />
					<small><?php esc_html_e( 'If blank, a random password will be generated and emailed to the new member.', 'pmpro-add-member-admin' ); ?></small>
				</td>
			</tr>
			<tr 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row"><label for="send_password"><?php esc_html_e( 'Send Password?', 'pmpro-add-member-admin' ); ?></label></th>
				<td><label for="send_password"><input type="checkbox" name="send_password" id="send_password" value="1" <?php checked( $send_password, 1 ); ?>> <?php esc_html_e( 'Send the user an email notification to set their password.', 'pmpro-add-member-admin' ); ?></label></td>
			</tr>
			<?php
				do_action( 'pmpro_add_member_fields', $user, $user_id );
			?>
			<tr class="user-info" 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="user_notes"><?php esc_html_e( 'User Notes', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
					if ( ! empty( $user_id ) ) {
						echo wpautop( $user->user_notes ); } else {
?>
												<textarea name="user_notes" id="user_notes" rows="5" cols="80" class="<?php echo pmpro_getClassForField( 'user_notes' ); ?>"><?php echo esc_textarea( $user_notes ); ?></textarea>
											<?php } ?>
				</td>
			</tr>
			<tr class="user-info" 
			<?php
			if ( ! empty( $user_id ) ) {
?>
style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label for="role"><?php esc_html_e( 'Role', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php if ( ! empty( $user_id ) ) { ?>
						<?php
						if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
							echo implode( ', ', $user->roles );
						}
						?>
					<?php } else { ?>
					<select name="role" id="role" class="<?php echo pmpro_getClassForField( 'role' ); ?>">
					<?php
						// print the full list of roles with the primary one selected.
						wp_dropdown_roles( $role );
					?>
					</select>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="membership_level"><?php esc_html_e( 'Membership Level', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<select name="membership_level" id="membership_level">
						<option value="" <?php selected( '', $membership_level ); ?> class="<?php echo pmpro_getClassForField( 'membership_level' ); ?>"><?php esc_html_e( 'No Level', 'pmpro-add-member-admin' ); ?></option>
						<?php
							$levels = pmpro_getAllLevels( true, true );
						foreach ( $levels as $level ) {
							?>
							<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $level->id, $membership_level ); ?>><?php echo esc_html( $level->name ); ?></option>
							<?php
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="expires_date"><?php esc_html_e( 'Expiration', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
						// is there an end date?
					if ( ! empty( $user->membership_level ) && ! empty( $user->membership_level->enddate ) ) {
						$end_date = 1;
					} else {
						$end_date = '';
					}

						// some vars for the dates
						$current_day = date( 'j' );
					if ( isset( $_POST['expires_day'] ) ) {
						$expires_day = sanitize_text_field( $_POST['expires_day'] );
					} elseif ( ! empty( $user->membership_level ) ) {
						$expires_day = date( 'j', $user->membership_level->enddate );
					} else {
						$expires_day = $current_day;
					}

						$current_month = date( 'M' );
					if ( isset( $_POST['expires_month'] ) ) {
						$expires_month = sanitize_text_field( $_POST['expires_month'] );
					} elseif ( ! empty( $user->membership_level ) ) {
						$expires_month = date( 'm', $user->membership_level->enddate );
					} else {
						$expires_month = date( 'm' );
					}

						$current_year = date( 'Y' );
					if ( isset( $_POST['expires_year'] ) ) {
						$expires_year = sanitize_text_field( $_POST['expires_year'] );
					} elseif ( ! empty( $user->membership_level ) ) {
						$expires_year = date( 'Y', $user->membership_level->enddate );
					} else {
						$expires_year = (int) $current_year + 1;
					}
					?>
					<select id="expires" name="expires">
						<option value="0" 
						<?php
						if ( ! $end_date ) {
?>
selected="selected"<?php } ?>><?php esc_html_e( 'No', 'pmpro-add-member-admin' ); ?></option>
						<option value="1" 
						<?php
						if ( $end_date ) {
?>
selected="selected"<?php } ?>><?php esc_html_e( 'Yes', 'pmpro-add-member-admin' ); ?></option>
					</select>
					<span id="expires_date" 
					<?php
					if ( ! $end_date ) {
?>
style="display: none;"<?php } ?>>
						on
						<select name="expires_month">
							<?php
							for ( $i = 1; $i < 13; $i++ ) {
								?>
								<option value="<?php echo $i; ?>" 
															<?php
															if ( $i == $expires_month ) {
							?>
							selected="selected"<?php } ?>><?php echo date( 'M', strtotime( $i . '/15/' . $current_year, current_time( 'timestamp' ) ) ); ?></option>
								<?php
							}
							?>
						</select>
						<input name="expires_day" type="text" size="2" value="<?php echo esc_attr( $expires_day ); ?>" />
						<input name="expires_year" type="text" size="4" value="<?php echo esc_attr( $expires_year ); ?>" />
					</span>
					<script>
						jQuery('#expires').change(function() {
							if(jQuery(this).val() == 1)
								jQuery('#expires_date').show();
							else
								jQuery('#expires_date').hide();
						});
					</script>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="payment"><?php esc_html_e( 'Payment', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<select name="payment" id="payment">
						<option value="" <?php selected( '', $payment ); ?>><?php esc_html_e( 'None', 'pmpro-add-member-admin' ); ?></option>
						<option value="check" <?php selected( 'check', $payment ); ?>><?php esc_html_e( 'Check/Cash', 'pmpro-add-member-admin' ); ?></option>
						<option value="gateway" <?php selected( 'gateway', $payment ); ?>><?php esc_html_e( 'Gateway (Not Functional)', 'pmpro-add-member-admin' ); ?></option>
						<option value="credit" <?php selected( 'credit', $payment ); ?>><?php esc_html_e( 'Credit Card (Not Functional)', 'pmpro-add-member-admin' ); ?></option>
					</select>
				</td>
			</tr>

			<tr class="payment payment-check payment-gateway payment-credit">
				<th scope="row" valign="top"><label for="total"><?php esc_html_e( 'Order Total', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<?php
					global $pmpro_currency_symbol;
					if ( pmpro_getCurrencyPosition() == 'left' ) {
						echo $pmpro_currency_symbol;
					}
					?>
					<input name="total" id="total" type="text" autocomplete="off" size="50" class="<?php echo pmpro_getClassForField( 'total' ); ?>" value="<?php echo esc_attr( $total ); ?>" />
					<?php
					if ( pmpro_getCurrencyPosition() == 'right' ) {
						echo $pmpro_currency_symbol;
					}
					?>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="order_notes"><?php esc_html_e( 'Order Notes', 'pmpro-add-member-admin' ); ?></label></th>
				<td>
					<textarea name="order_notes" id="order_notes" rows="5" cols="80" class="<?php echo pmpro_getClassForField( 'order_notes' ); ?>"><?php echo esc_textarea( $order_notes ); ?></textarea>
				</td>
			</tr>
		</tbody>
		</table>
		<div>
			<input type="hidden" name="action" value="add_member" />
			<?php

			// Adjust submit button text for Add Member or Add Order page.
			$submit_button_text = ( empty( $user_id ) ) ? esc_html__( 'Add Member', 'pmpro-add-member-admin' ) : esc_html__( 'Add Order', 'pmpro-add-member-admin' );
			submit_button( $submit_button_text );

			?>
		</div>
</form>
<script>
	//add required to required fields
	jQuery('.pmpro_required').after('<span class="pmpro_asterisk"> *</span>');
</script>

<?php
	require_once( PMPRO_DIR . '/adminpages/admin_footer.php' );
?>
