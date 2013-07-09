<?php 
/**
 * reusable functions.
 * 
 * @since 1.3
 *
 */

class CaavaCommon
{

	private $remote_url = 'http://www.caavadesign.com/';
	
	function __construct()
	{
		# code...
	}

	public function remote_get($key, $url, $args = array(), $expiration = 604800 ){

		if( empty( $expiration ) ){
			$results = get_option( $key );
		}else{
			$results = get_transient( $key );
		}
		
		if ( false === $results ) {
			$response = wp_remote_get($url, $args);
			$response_code = $response['response']['code'];
			$body = $response['body'];

			if($response_code == 200){

				if(!$expiration){
					update_option( $key, $body );
				}else{
					set_transient($key, $body, $expiration);
				}
				
				return $body;
			}else{
				return false;
			}
		}
		return $results;
	}

	public function is_local_dev(){
		$allowed_hosts = array('localhost', '127.0.0.1');
		
		if (!isset($_SERVER['HTTP_HOST']) || !in_array( $_SERVER['HTTP_HOST'], $allowed_hosts) ){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Queries the remote URL via wp_remote_post and returns a json decoded response.
	 *
	 * @since 1.3
	 *
	 * @param string $action The name of the $_POST action var
	 * @param array $body The content to retrieve from the remote URL
	 * @param array $headers The headers to send to the remote URL
	 * @param string $return_format The format for returning content from the remote URL
	 * @return string|bool Json decoded response on success, false on failure
	 */
	protected function perform_remote_request( $action, array $body = array(), array $headers = array(), $return_format = 'json' ) {

		// Build body
		$body = wp_parse_args( $body, array(
			'cv-action'     => $action,
			'cv-wp-version' => get_bloginfo( 'version' ),
			'cv-referer'    => site_url(),
			'cv-sitename'   => get_bloginfo( 'name' )
		) );
		$body = http_build_query( $body, '', '&' );

		// Build headers
		$headers = wp_parse_args( $headers, array(
			'Content-Type'   => 'application/x-www-form-urlencoded',
			'Content-Length' => strlen( $body )
		) );

		// Setup variable for wp_remote_post
		$post = array(
			'headers' => $headers,
			'body'    => $body
		);

		// Perform the query and retrieve the response
		$response      = wp_remote_post( esc_url_raw( $this->remote_url ), $post );

		$response_code = wp_remote_retrieve_response_code( $response );

		$response_body = wp_remote_retrieve_body( $response );

		// Bail out early if there are any errors
		if ( 200 != $response_code || is_wp_error( $response_body ) )
			return false;

		// Return body content if not json, else decode json
		if ( 'json' != $return_format )
			return $response_body;
		else
			return json_decode( $response_body );

		return false;

	}

	// Ajax Request
	public function is_ajax_request() {
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
		strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
	/**
	 * Shamelessly taken from wp-cli: https://github.com/wp-cli/wp-cli/blob/master/php/commands/scaffold.php#L289
	 *
	 * @since 1.4
	 *
	 */
	public function pluralize( $word ) {
		$plural = array(
			'/(quiz)$/i'                => '\1zes',
			'/^(ox)$/i'                 => '\1en',
			'/([m|l])ouse$/i'           => '\1ice',
			'/(matr|vert|ind)ix|ex$/i'  => '\1ices',
			'/(x|ch|ss|sh)$/i'          => '\1es',
			'/([^aeiouy]|qu)ies$/i'     => '\1y',
			'/([^aeiouy]|qu)y$/i'       => '\1ies',
			'/(hive)$/i'                => '\1s',
			'/(?:([^f])fe|([lr])f)$/i'  => '\1\2ves',
			'/sis$/i'                   => 'ses',
			'/([ti])um$/i'              => '\1a',
			'/(buffal|tomat)o$/i'       => '\1oes',
			'/(bu)s$/i'                 => '1ses',
			'/(alias|status)/i'         => '\1es',
			'/(octop|vir)us$/i'         => '1i',
			'/(ax|test)is$/i'           => '\1es',
			'/s$/i'                     => 's',
			'/$/'                       => 's'
		);

		$uncountable = array( 'equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep' );

		$irregular = array(
			'person'    => 'people',
			'man'       => 'men',
			'woman'     => 'women',
			'child'     => 'children',
			'sex'       => 'sexes',
			'move'      => 'moves'
		);

		$lowercased_word = strtolower( $word );

		foreach ( $uncountable as $_uncountable ) {
			if ( substr( $lowercased_word, ( -1 * strlen( $_uncountable ) ) ) == $_uncountable ) {
				return $word;
			}
		}

		foreach ( $irregular as $_plural=> $_singular ) {
			if ( preg_match( '/('.$_plural.')$/i', $word, $arr ) ) {
				return preg_replace( '/('.$_plural.')$/i', substr( $arr[0], 0, 1 ).substr( $_singular, 1 ), $word );
			}
		}

		foreach ( $plural as $rule => $replacement ) {
			if ( preg_match( $rule, $word ) ) {
				return preg_replace( $rule, $replacement, $word );
			}
		}
		return false;
	}

	public function post_type_labels( $arg="" ){
		$plural = ucwords($this->pluralize($arg));
		$single = ucwords($arg);
		$labels = array(
		'name' => $plural,
		'singular_name' => $single,
		'add_new' => 'Add New',
		'add_new_item' => 'Add New '.$single,
		'edit_item' => 'Edit '.$single,
		'new_item' => 'New '.$single,
		'all_items' => 'All '.$plural,
		'view_item' => 'View '.$single,
		'search_items' => 'Search '.$plural,
		'not_found' =>  'No '.$plural.' found',
		'not_found_in_trash' => 'No '.$plural.' found in Trash', 
		'parent_item_colon' => '',
		'menu_name' => $plural
		);
		return $labels;
	}

	/**
	 * Delete a post meta transient.
	 */
	public static function delete_post_meta_transient( $post_id, $transient = null, $value = null ) {
		global $_wp_using_ext_object_cache, $wpdb;

		$post_id = (int) $post_id;

		do_action( 'cv_delete_post_meta_transient_' . $transient, $post_id, $transient );
		
		if ( $_wp_using_ext_object_cache ) {
			$result = wp_cache_delete( "{$transient}-{$post_id}", "post_meta_transient-{$post_id}" );
		} else {
			$meta_timeout = 'cv_transient_timeout_' . $transient;
			$meta = 'cv_transient_' . $transient;
			if(empty($transient)){
				$prepare = $wpdb->prepare( 
						"DELETE FROM $wpdb->postmeta
						WHERE post_id = %d
						AND meta_key LIKE %s
						",
						$post_id, $meta.'%'
					);
				
				$wpdb->query( $prepare );
			}else{
				$result = delete_post_meta( $post_id, $meta, $value );
				if ( $result )
					delete_post_meta( $post_id, $meta_timeout, $value );
			}
		}
	 
		if ( $result )
			do_action( 'deleted_post_meta_transient', $transient, $post_id, $transient );
		return $result;
	}
	 
	/**
	 * Get the value of a post meta transient.
	 */
	public static function get_post_meta_transient( $post_id, $transient ) {
		global $_wp_using_ext_object_cache;
	 
		$post_id = (int) $post_id;
	 
	 
		if ( $_wp_using_ext_object_cache ) {
			$value = wp_cache_get( "{$transient}-{$post_id}", "post_meta_transient-{$post_id}" );
		} else {
			$meta_timeout = 'cv_transient_timeout_' . $transient;
			$meta = 'cv_transient_' . $transient;
			$value = get_post_meta( $post_id, $meta, true );
			if ( !empty($value) && ! defined( 'WP_INSTALLING' ) ) {

				if ( get_post_meta( $post_id, $meta_timeout, true ) < time() ) {
					self::delete_post_meta_transient( $post_id, $transient );
					return false;
				}
			}
			return false;
		}
		return apply_filters( 'post_meta_transient_' . $transient, $value, $post_id );
	}
	 
	/**
	 * Set/update the value of a post meta transient.
	 */
	public static function set_post_meta_transient( $post_id, $transient, $value, $expiration = 0 ) {
		global $_wp_using_ext_object_cache;
	 
		$post_id = (int) $post_id;
	 
		$value = apply_filters( 'pre_set_post_meta_transient_' . $transient, $value, $post_id, $transient );
	 
		if ( $_wp_using_ext_object_cache ) {
			$result = wp_cache_set( "{$transient}-{$post_id}", $value, "post_meta_transient-{$post_id}", $expiration );
		} else {
			$meta_timeout = 'cv_transient_timeout_' . $transient;
			$meta = 'cv_transient_' . $transient;
			if ( $expiration ) {
				add_post_meta( $post_id, $meta_timeout, time() + $expiration, true );
			}
			$result = add_post_meta( $post_id, $meta, $value, true );
		}
		if ( $result ) {
			do_action( 'set_post_meta_transient_' . $transient, $post_id, $transient );
			do_action( 'setted_post_meta_transient', $transient, $post_id, $transient );
		}
		return $result;
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
	public function wp_remote_get($key, $expiration, $url, $args = array()){

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
	public static function wp_oembed_get($key, $expiration, $url, $args = array()){
		global $post;

		if ( false === ( $results = self::get_post_meta_transient( $post->ID, $key ) ) ) {
			$response = wp_oembed_get($url, $args);
			if($response){
				$transient = $response;
				self::set_post_meta_transient($post->ID, $key, $transient, $expiration);
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
	public static function resize( $id=0, $width=50, $height=50, $crop=true){ 

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

	/* implement getTweets */
	public function get_tweets($count = 20, $username = false, $options = false) {

		$config['key'] = get_option('cv_twitter_consumer_key');
		$config['secret'] = get_option('cv_twitter_consumer_secret');
		$config['token'] = get_option('cv_twitter_access_token');
		$config['token_secret'] = get_option('cv_twitter_access_token_secret');
		$config['screenname'] = (!empty($username)) ? $username : get_option('cv_twitter_user_timeline');
		$config['cache_expire'] = intval(get_option('cv_twitter_cache_expire'));
		if ($config['cache_expire'] < 1) $config['cache_expire'] = 3600;
		$config['directory'] = plugin_dir_path(__FILE__);


		if ( false === $timeline = get_transient( 'cv_twitter_feed-'.$config['screenname'] ) ) {
			eden()->setLoader();

			$set_timeline = eden('twitter')->timeline($config['key'], $config['secret'], $config['token'], $config['token_secret']);
			$set_timeline->setCount($count);
			
			$timeline = $set_timeline->getUserTimelines($config['screenname']);
			set_transient( 'cv_twitter_feed-'.$config['screenname'], $timeline, $config['cache_expire'] );
		}
		
		return $timeline;
	}
}