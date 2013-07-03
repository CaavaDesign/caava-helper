<?php 
/**
 * Interact with the bugherd api
 * 
 * @since 1.3
 *
 */

require_once plugin_dir_path( __FILE__ ) . '/lib/BugHerd/Api.php';
require_once plugin_dir_path( __FILE__ ) . '/lib/BugHerd/Exception.php';
require_once plugin_dir_path( __FILE__ ) . '/lib/BugHerd/Project.php';

class CaavaBugHerd extends CaavaCommon
{

private $project_name;
public $project_name_clean;
public $project = '';
private $cache_expires = 604800;
	
	function __construct()
	{
		$this->project_name = get_bloginfo( 'name' );
		$this->project_name_clean = sanitize_title_with_dashes( $this->project_name );
		$this->project = $this->get_project();
		add_action('wp_head',array($this,'add_ui'));
	}

	public function get_project(){
		
		$data = get_transient('bugherd-'.$this->project_name_clean);
		if ( false === $data ) {

			$bugherd_status = $this->perform_remote_request( 'get-bugherd-status', array('post_type'=>'cv_api'),array(),'text' );
			
			if( !empty($bugherd_status) ){
				set_transient('bugherd-'.$this->project_name_clean, $bugherd_status, $this->cache_expires);
				return unserialize($bugherd_status);
			}
		}
		return unserialize($data);
	}

	public function add_ui(){
		$public_view_default = 'private';
		$public_view = apply_filters('cv_bugherd_visibility', $public_view_default);
		$embed_code = '<script type="text/javascript">
	(function (d,t) {
		var bh = d.createElement(t), s =
		d.getElementsByTagName(t)[0];
		bh.type = "text/javascript";
		bh.src = "//www.bugherd.com/sidebarv2.js?apikey='.$this->project->api_key.'";
		s.parentNode.insertBefore(bh, s);
	})(document, "script");
	</script>';

		if('private' != $public_view && $this->is_project_open()){
			echo $embed_code;
		}elseif($this->is_project_open() && is_user_logged_in() && current_user_can( 'activate_plugins' )){
			echo $embed_code;
		}
	}


	public function is_project_open(){
		return $this->project->active;
	}

}

$bugherd = new CaavaBugHerd;