<?php if($_GET['settings-updated']=='true')
	delete_transient('cv_twitter_feed');

 ?>

<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php settings_errors(); ?>

	<?php $settings = CaavaHelper::twitter_settings(); ?>


		<p>Most of this configuration can found on the application overview page on the <a href="http://dev.twitter.com/apps">http://dev.twitter.com</a> website.</p>
		<p>When creating an application for this plugin, you don\'t need to set a callback location and you only need read access.</p>
		<p>You will need to generate an oAuth token once you\'ve created the application. The button for that is on the bottom of the application overview page.</p>
		<p>Once configured, you then need to call getTweets() anywhere in your template. getTweets supports 3 parameters - the number of tweets to load (max 20), the username of the twitter feed you want to load, and any additional parameters you want to send to Twitter. An example code usage is shown under the debug information below.</p>
		<p>The format of the response from getTweets will either be an array of arrays containing tweet objects, as described on the official Twitter documentation <a href="https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline">here</a>, or an 1D array containing an "error" key, with a value of the error that occurred.</p>

		<hr />

		<form method="post" action="options.php">

    <?php settings_fields('cv_twitter_settings');

		echo '<table>';
			foreach($settings as $setting) {
				echo '<tr>';
					echo '<td>'.$setting['label'].'</td>';
					echo '<td><input type="text" style="width: 400px" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" /></td>';
				echo '</tr>';
				if ($setting['name'] == 'tdf_user_timeline') {
  				echo '<tr>';
  				  echo '<td colspan="2" style="font-size:10px; font-style: italic">This option is no longer required. You may define the screen name to load as part of the getTweets() call as detailed above.</td>';
  				echo '</tr>';
				}
			}
		echo '</table>';

		submit_button(); ?>

		</form>

		<hr />

		<h3>Debug Information</h3>
		<?php $last_error = get_option('tdf_last_error');
		if (empty($last_error)) $last_error = "None";
		echo 'Last Error: '.$last_error.'</p>';
		?>

</div>
