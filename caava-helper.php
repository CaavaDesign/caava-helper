<?php /*
Plugin Name: Caava Helper Functions
Plugin URI: http://caavadesign.com
Description: A series of developer facing functionality created to optimize or enhance a WordPress site.
Version: 1.0
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