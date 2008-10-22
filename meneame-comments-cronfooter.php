<?php


function meneame_comments__wp_footer() {
	global $post, $meneamec_url;
	
	$single_cron = meneame_comments__get_option('single_cron');
	
	if ( $single_cron && isset($single_cron[$post->ID]) ) {
		echo "\n";
			echo '<script type="text/javascript" src="'.$meneamec_url.'/meneame-comments-upload-js.php?mc_pid='.$post->ID.'"></script>';
		echo "\n";
	}
}
add_action('wp_footer', 'meneame_comments__wp_footer');

?>
