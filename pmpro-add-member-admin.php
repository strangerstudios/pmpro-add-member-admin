<?php
/*
Plugin Name: Paid Memberships Pro - Add Member Admin
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-add-member-admin/
Description: Allow admins to add members in the WP dashboard.
Version: .2
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
Text Domain: pmpro-addmember
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
function pmproama_pmpro_add_pages()
{	
	$cap = apply_filters('pmpro_add_member_cap', 'edit_users');
	
	add_submenu_page('pmpro-membershiplevels', __('Add Member', 'pmpro-addmember'), __('Add Member', 'pmpro-addmember'), $cap, 'pmpro-addmember', 'pmpro_addmember');
}
add_action('admin_menu', 'pmproama_pmpro_add_pages');

function pmpro_addmember()
{
	require_once(dirname(__FILE__) . "/adminpages/addmember.php");
}

/*
	Admin Bar
*/
function pmproama_admin_bar_menu() {
	global $wp_admin_bar;		
	
	//view menu at all?
	if ( !current_user_can('pmpro_memberships_menu') || !is_admin_bar_showing() )
		return;
		
	$cap = apply_filters('pmpro_add_member_cap', 'edit_users');
	
	if(current_user_can($cap))
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-addmember',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Add Member', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-addmember') ) );
}
add_action('admin_bar_menu', 'pmproama_admin_bar_menu', 1001);

/*
Function to add links to the plugin action links
*/
function pmproama_add_action_links($links) {	
	$cap = apply_filters('pmpro_add_member_cap', 'edit_users');	
	if(current_user_can($cap))
	{
		$new_links = array(
			'<a href="' . get_admin_url(NULL, 'admin.php?page=pmpro-addmember') . '">' . __( 'Add Member', 'pmpro-addmember') . '</a>',
		);
	}
	return array_merge($new_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pmproama_add_action_links');

/*
Function to add links to the plugin row meta
*/
function pmproama_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-add-member-admin.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url( 'http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-addmember' ) ) . '">' . __( 'Support', 'pmpro-addmember' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmproama_plugin_row_meta', 10, 2);

/*
	Add action links
*/
function pmproama_action_links($actions, $user)
{
	$cap = apply_filters('pmpro_add_member_cap', 'edit_users');
	
	if(current_user_can($cap) && !empty($user->ID))
		$actions['addorder'] = '<a href="' . admin_url('admin.php?page=pmpro-addmember&user=' . $user->ID) . '">' . __( '+order', 'pmpro-addmember' ) . '</a>';
		
	return $actions;
}
add_filter('pmpro_memberslist_user_row_actions', 'pmproama_action_links', 10, 2);
add_filter('pmpro_orders_user_row_actions', 'pmproama_action_links', 10, 2);
add_filter('user_row_actions', 'pmproama_action_links', 10, 2);


/**
 * Load Plugin Text Domain for I18N.
 */
function pmproama_load_plugin_textdomain() {
	load_plugin_textdomain( 'pmpro-addmember', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'pmproama_load_plugin_textdomain' );