<?php
/*
Plugin Name: Paid Memberships Pro - Add Member From Admin
Plugin URI: https://www.paidmembershipspro.com/add-ons/add-member-admin-add-on/
Description: Allow admins to add members in the WP dashboard.
Version: .5
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
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-addmember',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Add Member', 'pmpro-add-member-admin' ),
				'href' => get_admin_url( null, '/admin.php?page=pmpro-addmember' ),
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
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Visit the Add Member admin page</a> to add new members.', 'pmpro-add-member-admin' ), get_admin_url( null, 'admin.php?page=pmpro-addmember' ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmproama-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmproama_admin_notice' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmproama_add_action_links( $links ) {
	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );
	if ( current_user_can( $cap ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'admin.php?page=pmpro-addmember' ) . '">' . __( 'Add Member', 'pmpro-add-member-admin' ) . '</a>',
		);
	}
	return array_merge( $new_links, $links );
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
			'<a href="' . esc_url( 'http://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-add-member-admin' ) ) . '">' . __( 'Support', 'pmpro-add-member-admin' ) . '</a>',
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
		$actions['addorder'] = '<a href="' . admin_url( 'admin.php?page=pmpro-addmember&user=' . $user->ID ) . '">' . __( '+order', 'pmpro-add-member-admin' ) . '</a>';
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
