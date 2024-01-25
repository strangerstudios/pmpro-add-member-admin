<?php
/*
Plugin Name: Paid Memberships Pro - Add Member From Admin
Plugin URI: https://www.paidmembershipspro.com/add-ons/add-member-admin-add-on/
Description: Allow admins to add members in the WP dashboard.
Version: 0.7.2
Author: Stranger Studios
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-add-member-admin
Domain Path: /languages
*/

/*
	* Add "Add Member" link under Memberships.
	* Form with fields, username, email (find user), password (random), name, level, expiration (auto), credit card
	* Add user, apply level, save order
	* Filters for adding additional fields.
*/

/*
	Add Menu Item
*/
function pmproama_pmpro_add_pages() {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
        return;
    }
	
	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );

	if( version_compare( PMPRO_VERSION, '2.0' ) >= 0 ) {
		add_submenu_page( 'pmpro-dashboard', __( 'Add Member', 'pmpro-add-member-admin' ), __( 'Add Member', 'pmpro-add-member-admin' ), $cap, 'pmpro-addmember', 'pmpro_addmember' );
	} else {
		add_submenu_page( 'pmpro-membershiplevels', __( 'Add Member', 'pmpro-add-member-admin' ), __( 'Add Member', 'pmpro-add-member-admin' ), $cap, 'pmpro-addmember', 'pmpro_addmember' );
	}
	
}
add_action( 'admin_menu', 'pmproama_pmpro_add_pages', 20 );

function pmpro_addmember() {
	require_once( dirname( __FILE__ ) . '/adminpages/addmember.php' );
}

/*
	Admin Bar
*/
function pmproama_admin_bar_menu() {
	global $wp_admin_bar;

	// view menu at all?
	if ( ! current_user_can( 'pmpro_memberships_menu' ) || ! is_admin_bar_showing() ) {
		return;
	}

	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );

	if ( current_user_can( $cap ) ) {

		//Adds menu item to the 'Memberships' tab in the admin bar
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-addmember',
				'parent' => 'paid-memberships-pro',
				'title' => esc_html__( 'Add Member', 'pmpro-add-member-admin' ),
				'href' => esc_url( get_admin_url( null, '/admin.php?page=pmpro-addmember' ) ),
			)
		);

		//Adds menu item to the 'New' tab in the admin bar
		$wp_admin_bar->add_node(
			array(
                'parent' => 'new-content',
                'id' => 'pmpro-addmember',
                'title' => esc_html__( 'Member', 'pmpro-add-member-admin' ),
                'href'   => esc_url( get_admin_url( null, '/admin.php?page=pmpro-addmember' ) ),
                'meta'   => false
            )
		);
	}
}
add_action( 'admin_bar_menu', 'pmproama_admin_bar_menu', 1001 );

/**
 * Sends an email to the administrator about the user.
 * Uses the template "admin_change_admin.html"
 */
function pmproama_send_admin_notification( $user_id, $user ) {
	$pmproemail = new PMProEmail();
	$pmproemail->sendAdminChangeAdminEmail( $user );	
}
add_action( 'pmpro_add_member_added', 'pmproama_send_admin_notification', 10, 2 );

/* Register activation hook. */
register_activation_hook( __FILE__, 'pmproama_admin_notice_activation_hook' );
/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 */
function pmproama_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmproama-admin-notice', true, 5 );
}
/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function pmproama_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmproama-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Visit the Add Member admin page</a> to add new members.', 'pmpro-add-member-admin' ), esc_url( get_admin_url( null, 'admin.php?page=pmpro-addmember' ) ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmproama-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmproama_admin_notice' );

/**
 * Integration with Paid Memberships Pro MailChimp Add On
 *
 * @since 0.6.0
 */
function pmproama_plugins_loaded() {
	if ( function_exists( 'pmpromc_processSubscriptions' ) ) {
		add_action( 'pmpro_add_member_added', 'pmpromc_processSubscriptions' );
	}
}
add_action( 'plugins_loaded', 'pmproama_plugins_loaded' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmproama_add_action_links( $links ) {
	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );
	if ( current_user_can( $cap ) ) {
		$new_links = array(
			'<a href="' . esc_url( get_admin_url( null, 'admin.php?page=pmpro-addmember' ) ) . '">' . esc_html__( 'Add Member', 'pmpro-add-member-admin' ) . '</a>',
		);
	}
	return ( ! empty( $new_links ) && is_array( $new_links ) ) ? array_merge( $new_links, $links ) : $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmproama_add_action_links' );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmproama_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-add-member-admin.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'http://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-add-member-admin' ) ) . '">' . esc_html__( 'Support', 'pmpro-add-member-admin' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmproama_plugin_row_meta', 10, 2 );

/*
	Add action links
*/
function pmproama_action_links( $actions, $user ) {
	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );

	if ( current_user_can( $cap ) && ! empty( $user->ID ) ) {
		$actions['addorder'] = '<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-addmember&user=' . (int) $user->ID ) ) . '">' . esc_html__( '+order', 'pmpro-add-member-admin' ) . '</a>';
	}

	return $actions;
}
add_filter( 'pmpro_memberslist_user_row_actions', 'pmproama_action_links', 10, 2 );
add_filter( 'pmpro_orders_user_row_actions', 'pmproama_action_links', 10, 2 );
add_filter( 'user_row_actions', 'pmproama_action_links', 10, 2 );


/**
 * Load Plugin Text Domain for I18N.
 */
function pmproama_load_plugin_textdomain() {
	load_plugin_textdomain( 'pmpro-add-member-admin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmproama_load_plugin_textdomain' );

/**
 * Creates email templates for when a member has been added
 *
 * @param array $pmproet_email_defaults Default email template arrays
 * @return array $pmproet_email_defaults Returns an updated array of email templates
 *
 * @since 0.7
 */
function pmproama_email_templates( $pmproet_email_defaults ) {

	//These emails can be based off of the checkout_check email

	$subject = "Your membership confirmation for !!sitename!!";

	$body = "<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>

	!!membership_level_confirmation_message!!

	<p>Below are details about your membership account and a receipt for your initial membership invoice.</p>

	<p>Account: !!display_name!! (!!user_email!!)</p>
	<p>Membership Level: !!membership_level_name!!</p>
	<p>Membership Fee: !!membership_cost!!</p>
	!!membership_expiration!!

	<p>
		Invoice #!!invoice_id!! on !!invoice_date!!<br />
		Total Billed: !!invoice_total!!
	</p>

	<p>Log in to your membership account here: !!login_url!!</p>";

    $pmproet_email_defaults['add_member_added'] = array(
	    'subject' => $subject,
	    'description' => __( 'Add Member from Admin Added', 'pmpro-customizations'),
	    'body' => $body,
	    'help_text' => __( 'This is a membership confirmation welcome email sent to a new member when adding them from the Add Member from Admin page.', 'pmpro-add-member-admin' )
    );

    $subject_admin = "Member checkout for !!membership_level_name!! at !!sitename!!";

    $body_admin = "<p>There was a new member checkout at !!sitename!!.</p>

	<p><strong>They have been added from the Add Member from Admin page.</strong></p>

	<p>Below are details about the new membership account and a receipt for the initial membership invoice.</p>

	<p>Account: !!display_name!! (!!user_email!!)</p>
	<p>Membership Level: !!membership_level_name!!</p>
	<p>Membership Fee: !!membership_cost!!</p>
	!!membership_expiration!!

	<p>
		Invoice #!!invoice_id!! on !!invoice_date!!<br />
		Total Billed: !!invoice_total!!
	</p>

	<p>Log in to your membership account here: !!login_url!!</p>";

    $pmproet_email_defaults['add_member_added_admin'] = array(
	    'subject' => $subject_admin,
	    'description' => __( 'Add Member from Admin Added (admin)', 'pmpro-customizations'),
	    'body' => $body_admin,
	    'help_text' => __( 'This is a membership confirmation notification email sent to the admin when adding them from the Add Member from Admin page.', 'pmpro-add-member-admin' )
    );
   
    return $pmproet_email_defaults;

}
add_filter( 'pmproet_templates', 'pmproama_email_templates', 10, 1 );

/**
 * Sends an email to the member that has been added
 *
 * @param object $user The user we want to send details to
 * @param MemberOrder $order The user's order details
 *
 * @return bool Successful email sent
 * 
 * @since 0.7
 */
function pmproada_send_added_email( $user = NULL, $order = NULL ){

	global $wpdb, $current_user;

	if( !$user ){
		$user = $current_user;
	}
	
	if( !$user ){
		return false;
	}

	if( !class_exists( 'PMProEmail' ) ) {
		return false;
	}

    $pmproemail = new PMProEmail();

	$pmproemail->email = $user->user_email;

	$confirmation_in_email = get_pmpro_membership_level_meta( $user->membership_level->id, 'confirmation_in_email', true );
	if ( ! empty( $confirmation_in_email ) ) {
		$confirmation_message = $user->membership_level->confirmation;
	} else {
		$confirmation_message = '';
	}

	if( $order->getDiscountCode() ) {
		$discount_code = "<p>" . esc_html__("Discount Code", 'pmpro-add-member-admin' ) . ": " . esc_html( $order->discount_code->code ) . "</p>\n";
	} else {
		$discount_code = "";
	}

	$pmproemail->data = array( 
		"user_email" => $user->user_email, 
		"display_name" => $user->display_name, 
		"user_login" => $user->user_login, 
		"sitename" => get_option( "blogname" ), 
		"siteemail" => pmpro_getOption( "from_email" ),
		"membership_level_confirmation_message" => $confirmation_message,
		"membership_cost" => pmpro_getLevelCost( $user->membership_level ),
		"discount_code" => $discount_code,
		"invoice_id" => $order->id,
		"invoice_date" => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
		"invoice_total" => pmpro_formatPrice( $order->total ),
	);

	if( !empty( $order ) && intval( $order->membership_id ) !== 0 ) {
		
		$membership_id = intval( $order->membership_id );

		$pmproemail->data['membership_id'] = $membership_id;
		$pmproemail->data['membership_level_name'] = pmpro_implodeToEnglish( $wpdb->get_col("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '". esc_sql( $membership_id ) ."'" ) );

	} else {
		$pmproemail->data['membership_id'] = '';
		$pmproemail->data['membership_level_name'] = __('All Levels', 'pmpro-add-member-admin' );
	}

	$pmproemail->template = apply_filters("pmpro_email_template", "add_member_added", $pmproemail );

	return $pmproemail->sendEmail();	
	
}

/**
 * Sends an email to the admin that a new member has been added
 *
 * @param object $user The user we want to send details to
 * @param MemberOrder $order The member's order details
 *
 * @return bool Successful email sent
 * 
 * @since 0.7
 */
function pmproada_send_added_email_admin( $user = NULL, $order = NULL ) {

	global $wpdb, $current_user;

	if ( ! $user ) {
		$user = $current_user;
	}
		
	
	if ( ! $user ) {
		return false;
	}
		

	if ( ! class_exists( 'PMProEmail' ) ) {
		return false;
	}

    $pmproemail = new PMProEmail();
	
	$pmproemail->email = get_bloginfo( 'admin_email' );

	$confirmation_in_email = get_pmpro_membership_level_meta( $user->membership_level->id, 'confirmation_in_email', true );
	if ( ! empty( $confirmation_in_email ) ) {
		$confirmation_message = wp_kses_post( $user->membership_level->confirmation );
	} else {
		$confirmation_message = '';
	}

	$pmproemail->data = array(
		'name' => $current_user->display_name,
		'user_login' => $user->user_login, 
		'user_email' => $user->user_email, 
		'display_name' => $user->display_name, 
		'sitename' => get_option( 'blogname' ), 
		'siteemail' => pmpro_getOption( 'from_email' ), 
		'membership_cost' => pmpro_getLevelCost( $user->membership_level ),
		'membership_level_confirmation_message' => $confirmation_message,
		'invoice_id' => $order->id,
		'invoice_date' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
		'invoice_total' => pmpro_formatPrice( $order->total ),
		'login_link' => pmpro_login_url(), 
		'login_url' => pmpro_login_url()
	);
	
	if ( ! empty( $order ) && intval( $order->membership_id ) !== 0 ) {

		$membership_id = intval( $order->membership_id );

		$pmproemail->data['membership_id'] = $membership_id;
		$pmproemail->data['membership_level_name'] = pmpro_implodeToEnglish( $wpdb->get_col("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '" . esc_sql( $membership_id ). "'" ) );

		//start and end date
		$startdate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(startdate, '+00:00', @@global.time_zone)) as startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . esc_sql( $user->ID ) . "' AND membership_id = '" . esc_sql( $membership_id ) . "' AND status IN('inactive', 'cancelled', 'admin_cancelled') ORDER BY id DESC");

		if( !empty( $startdate ) ) {
			$pmproemail->data['startdate'] = date_i18n(get_option('date_format'), $startdate);
		} else {
			$pmproemail->data['startdate'] = "";
		}

		$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) as enddate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . esc_sql( $user->ID ) . "' AND membership_id = '" . esc_sql( $membership_id ) . "' AND status IN('inactive', 'cancelled', 'admin_cancelled') ORDER BY id DESC");

		if( !empty( $enddate ) ) {
			$pmproemail->data['enddate'] = date_i18n(get_option('date_format'), $enddate);
		} else {
			$pmproemail->data['enddate'] = "";
		}

	} else {
		$pmproemail->data['membership_id'] = '';
		$pmproemail->data['membership_level_name'] = __('All Levels', 'pmpro-add-member-admin' );
		$pmproemail->data['startdate'] = '';
		$pmproemail->data['enddate'] = '';
	}

	$pmproemail->template = apply_filters("pmpro_email_template", "add_member_added_admin", $pmproemail);

	return $pmproemail->sendEmail();	

}

/**
 * Mark the plugin as MMPU-incompatible.
 */
function pmproama_mmpu_incompatible_add_ons( $incompatible ) {
	$incompatible[] = 'PMPro Add Member From Admin Add On';
	return $incompatible;
}
add_filter( 'pmpro_mmpu_incompatible_add_ons', 'pmproama_mmpu_incompatible_add_ons' );
