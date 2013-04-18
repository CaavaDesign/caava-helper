<?php /*
Plugin URI: http://woothemes.com/woodojo/
Description: Modified version of WooDojo's Instagram widget. No user facing widget interface.
Version: 1.5.2
Author: WooThemes
Author URI: http://woothemes.com/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
/*  Copyright 2012  WooThemes  (email : info@woothemes.com)

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


add_action('init','add_cv_woo_instagram');

function add_cv_woo_instagram(){
	global $cv_instagram;
	$cv_instagram = new Caava_WooDojo_Widget_Instagram();
}

class Caava_WooDojo_Widget_Instagram {

	/* Variable Declarations */
	protected $woo_widget_cssclass;
	protected $woo_widget_description;
	protected $woo_widget_idbase;
	protected $woo_widget_title;

	protected $transient_expire_time;
	private $client_id;
	private $client_secret;
	private $api_url = 'https://api.instagram.com/';

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @uses WooDojo
	 * @return void
	 */
	public function __construct () {
		$this->transient_expire_time = 60 * 60 * 24; // 1 day.
		$this->client_id = '79a1ad0924854bad93558757ff86c7f7';
		$this->client_secret = '2feefd865b5643909395d81135af7840';
	}

	public function set_credentials($user,$pw){
		$instagram_user = get_option('woo_instagram_u');
		$instagram_pw = get_option('woo_instagram_p');
		$instagram_access_token = get_option('woo_instagram_access_token');
		$instagram_profile_data = get_option('woo_instagram_profile_data');

		if(!$instagram_user || !$instagram_pw || !$instagram_access_token || !$instagram_profile_data){

			if(!$instagram_user)
				$instagram_user = update_option('woo_instagram_u',$user);

			if(!$instagram_access_token)
				$instagram_access_token = update_option('woo_instagram_access_token',$pw);

			if(!$instagram_pw)
				$instagram_pw = update_option('woo_instagram_p',$pw);

			if(!$instagram_access_token || !$instagram_profile_data){

				$response_data = $this->get_access_token( $instagram_user, $instagram_pw );
				if ( is_object( $response_data ) && isset( $response_data->access_token ) ) {
					$instagram_access_token = update_option('woo_instagram_access_token',$response_data->access_token);
					$instagram_profile_data = update_option('woo_instagram_profile_data',$response_data->user);
				}
			}
		}
	}


	/**
	 * display instagram html
	 * 
	 * @since 1.1
	 *
	 * @param    array $args
	 * @return   string
	 */	
	public function display_feed( $args = array() ){

		$instagram_user = get_option('woo_instagram_u');

		$instagram_pw = get_option('woo_instagram_p');

		$instagram_access_token = get_option('woo_instagram_access_token');

		$defaults = array(
			'access_token' => $instagram_access_token,
			'count'=>8,
			'float'=>'left',
			'image_size'=>'thumbnail',
			'custom_image_size'=>150,
			'enable_thickbox'=>false,
			'link_to_fullsize'=>true,
			'feed_class'=>'',
			'item_class'=>'',
			'element'=>'div'
		);


		$parsed = wp_parse_args( $args, $defaults );

		$data = $this->get_stored_data( $parsed );
		
		$html = $this->prepare_photos_html($data, $parsed);

		return $html;

	}

	/**
	 * Request an access token from the API.
	 * @param  string $username The username.
	 * @param  string $password The password.
	 * @return string           Access token.
	 */
	private function get_access_token ( $username, $password ) {
		$args = array(
				'username' => $username,
				'password' => $password,
				'grant_type' => 'password',
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret
			);

		$response = $this->request( 'oauth/access_token', $args );

		return $response;
	} // End get_access_token()


	/**
	 * Retrieve stored data, or query for new data.
	 * @param  array $args
	 * @return array
	 */
	public function get_stored_data ( $args ) {
		$data = '';
		$transient_key = 'woo-instagram-recent-photos';
		
		if ( false === ( $data = get_transient( $transient_key ) ) ) {
			$response = $this->request_recent_photos( $args );

			if ( isset( $response->data ) ) {
				$data = json_encode( $response );
				set_transient( $transient_key, $data, $this->transient_expire_time );
			}
		}

		return json_decode( $data );
	} // End get_stored_data()

	/**
	 * Retrieve recent photos for the specified user.
	 * @param  array $args
	 * @return array
	 */
	public function request_recent_photos ( $args ) {
		$data = array();

		$response = $this->request( 'v1/users/self/media/recent', $args, 'get' );

		if( is_wp_error( $response ) ) {
		   $data = new StdClass;
		} else {
		   if ( isset( $response->meta->code ) && ( $response->meta->code == 200 ) ) {
		   		$data = $response;
		   }
		}

		return $data;
	} // End request_recent_photos()

	/**
	 * Make a request to the API.
	 * @param  string $endpoint The endpoint of the API to be called.
	 * @param  array  $params   Array of parameters to pass to the API.
	 * @return object           The response from the API.
	 */
	private function request ( $endpoint, $params = array(), $method = 'post' ) {
		$return = '';

		

		if ( $method == 'get' ) {
			$url = $this->api_url . $endpoint;

			if ( count( $params ) > 0 ) {
				$url .= '?';
				$count = 0;
				foreach ( $params as $k => $v ) {
					$count++;

					if ( $count > 1 ) {
						$url .= '&';
					}

					$url .= $k . '=' . urlencode($v);
				}
			}
			
			$response = wp_remote_get( $url,
				array(
					'sslverify' => apply_filters( 'https_local_ssl_verify', false )
				)
			);
		} else {
			$response = wp_remote_post( $this->api_url . $endpoint,
				array(
					'body' => $params,
					'sslverify' => apply_filters( 'https_local_ssl_verify', false )
				)
			);
		}

		if ( ! is_wp_error( $response ) ) {
			$return = json_decode( $response['body'] );
		}

		return $return;
	} // End request()

/**
	 * Prepare the returned data into HTML.
	 * @param  object $data The data retrieved from the API.
	 * @param  array $instance The settings for the current widget instance.
	 * @return string       The rendered HTML.
	 */
	private function prepare_photos_html ( $data, $instance = array() ) {

		$element = ( in_array( $instance['element'], array('div','ul') ) ) ? $instance['element'] : 'div';
		$sub_element = ( 'ul' == $element ) ? 'li' : 'div';
		$html = '';

		if ( is_object( $data ) && isset( $data->data ) && is_array( $data->data ) && ( count( $data->data ) > 0 ) ) {
			$html .= '<'.$element.' class="instagram-photos align' . strtolower( $instance['float'] ) . ' '.$instance['feed_class'].'">' . "\n";

			$params = '';
			$anchor_params = '';
			$size_token = $instance['image_size'];

			if ( $instance['custom_image_size'] == '' || in_array( $instance['custom_image_size'], array( 150, 306, 612 ) ) ) {} else {
				$size_token = $this->determine_image_by_size( $instance['custom_image_size'] );
				$params = ' style=" width: ' . intval( $instance['custom_image_size'] ) . 'px; height: ' . intval( $instance['custom_image_size'] ) . 'px;"';
			}

			$class = 'instagram-photo-link';

			if ( $instance['enable_thickbox'] == true ) {
				$class .= ' thickbox';
				$anchor_params .= ' rel="instagram-thickbox"';
			}
			$x=0;
			foreach ( $data->data as $k => $v ) {
				if($x >= $instance['count'])
					break;
				$caption = '';
				if ( isset( $v->caption->text ) && ( $v->caption->text != '' ) ) {
					$caption = $v->caption->text;
				}

				if ( $caption == '' ) {
					$caption = sprintf( __( 'Instagram by %s', 'woodojo' ), $v->user->full_name );
				}
				$item_class = (!empty($instance['item_class'])) ? 'class="'.$instance['item_class'].'"' : '';
				$html .= '<'.$sub_element.' '.$item_class.'>' . "\n";
				
				if ( $instance['link_to_fullsize'] == true ) {
					$html .= '<a href="' . esc_url( $v->link ) . '" title="' . esc_attr( $caption ) . '" class="' . esc_attr( $class ) . '"' . $anchor_params . '>' . "\n";
				}
					$html .= '<img src="' . esc_url( $v->images->$size_token->url ) . '"' . $params . ' alt="' . esc_attr( $caption ) . '" />' . "\n";
				if ( $instance['link_to_fullsize'] == true ) {
					$html .= '</a>' . "\n";
				}
				$html .= '</'.$sub_element.'>';
				$x++;
			}
			$html .= '</'.$element.'>' . "\n";
		}

		return $html;
	} // End prepare_photos_html()

		private function determine_image_by_size ( $size ) {
		$token = 'thumbnail';

		if ( $size <= 150 ) { $token = 'thumbnail'; }
		if ( $size <= 306 && $size > 150 ) { $token = 'low_resolution'; }
		if ( ( $size <= 612 || $size > 612 ) && $size > 306 ) { $token = 'standard_resolution'; }

		return $token;
	} // End determine_image_by_size()

}