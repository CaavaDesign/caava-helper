<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   PluginName
 * @author    Your Name <email@example.com>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2013 Your Name or Company Name
 */
// Activity Widget


	add_action('cv_dashboard_setup', array('My_Dashboard_Widget','init') );
	if ( is_blog_admin() ) {
		wp_add_dashboard_widget( 'dashboard_caava_feed', __( 'Caava Design Feed' ), 'cv_dashboard_caava_rss' );
	}

	function cv_dashboard_caava_rss( $widget_id ) {
		echo '<div class="rss-widget">';
		wp_widget_rss_output( 'http://www.caavadesign.com/feed/' );
		echo "</div>";
	}

wp_enqueue_script( 'dashboard' );
if ( current_user_can( 'edit_theme_options' ) )
	wp_enqueue_script( 'customize-loader' );
if ( current_user_can( 'install_plugins' ) )
	wp_enqueue_script( 'plugin-install' );
if ( current_user_can( 'upload_files' ) )
	wp_enqueue_script( 'media-upload' );
	add_thickbox();

if ( wp_is_mobile() )
	wp_enqueue_script( 'jquery-touch-punch' );

?>
<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php settings_errors(); ?>

	<div id="dashboard-widgets-wrap">
	<?php wp_dashboard(); ?>
	</div><!-- dashboard-widgets-wrap -->

</div>
