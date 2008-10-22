<?php


### Variables Variables Variables
$pluginPageOptions = 'admin.php?page='.$pluginPath;

$id = intval($_GET['id']);
$mode = trim($_GET['mode']);

$meneame_comments = get_option('meneame_comments');

### Form Processing 
if(!empty($_POST['do'])) {
	
	// Decide What To Do
	switch($_POST['do']) {
		
		case __('Update Options', 'beplugin'):
			
			// **** Update MENEAME URL values ****
				// Status to New Comments
				if ( $meneame_comments['new_comment_status'] != $_POST['meneame_comments__new_comment_status'] ) {
					$meneame_comments['new_comment_status'] = $_POST['meneame_comments__new_comment_status'];
					$text .= '<p>'.__('Status to New Comments Updated', 'beplugins').'</p>';
				}
				
			// **** Update MENEAME URL values ****
				// Update RSS URL
				if ( $meneame_comments['rss_url'] != $_POST['meneame_comments__rss_url'] ) {
					$meneame_comments['rss_url'] = $_POST['meneame_comments__rss_url'];
					$text .= '<p>'.__('Comments Feed URL Updated', 'beplugins').'</p>';
				}
				// Update URL ID Parameter
				if ( $meneame_comments['rss_param_id'] != $_POST['meneame_comments__rss_param_id'] ) {
					$meneame_comments['rss_param_id'] = $_POST['meneame_comments__rss_param_id'];
					$text .= '<p>'.__('URL ID Parameter Updated', 'beplugins').'</p>';
				}
				// Update URL Karma Parameter
				if ( $meneame_comments['rss_param_karma'] != $_POST['meneame_comments__rss_param_karma'] ) {
					$meneame_comments['rss_param_karma'] = $_POST['meneame_comments__rss_param_karma'];
					$text .= '<p>'.__('URL Karma Parameter Updated', 'beplugins').'</p>';
				}
				// Update URL ID Parameter
				if ( $meneame_comments['rss_min_karma'] != $_POST['meneame_comments__rss_min_karma'] ) {
					$meneame_comments['rss_min_karma'] = $_POST['meneame_comments__rss_min_karma'];
					$text .= '<p>'.__('Minimal Karma Updated', 'beplugins').'</p>';
				}
			
			// **** Update options ****
			$meneame_comments = update_option('meneame_comments', $meneame_comments);
			
			break;
		
		case __('Update All Comments', 'beplugin'):
		case __('Make First Load', 'beplugin'):
			
			meneame_comments__ajax__firstloadcomments('', false);
			break;
		
		case __('Update Comments', 'beplugin'):
			
			meneame_comments__ajax__updatecomments();
			break;
		
		case __('Delete Meneame Comments', 'beplugin'):
			
			// Borramos los comentarios de la BBDD
			$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_type = 'comment_meneame'");
			// Actualizamos la fecha de la ultima actualizacion
			meneame_comments__set_option('lastupdate', "-1");
			// Clear Cache de Queries
			update_option('meneame_comments__check_cache2',  meneame_comments__get_vardefault('check_cache2'));
			meneame_comments__set_option('single_cron',  meneame_comments__get_vardefault('single_cron'));
			meneame_comments__set_option('single_cron_lastupdate',  meneame_comments__get_vardefault('single_cron_lastupdate'));
			break;
		
	}
	
}

if ( !empty($text) ) {
	echo '<div id="message" class="updated fade">';
		echo '<p><strong>'.$text.'</strong></p>';
	echo '</div>';
}

?>

<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
	
	<div class="wrap">
		
		<h2><?php _e('Men&eacute;ame Comments to WP', 'beplugin'); ?></h2>
		
		
		<h3><?php _e('Trackbacks from Men&eacute;ame', 'beplugin'); ?></h3>
		
		<table class="form-table">
			<tr>
				<th><?php _e('Update', 'beplugin'); ?></th>
				<td>
					<?php $comments_trackbacked = meneame_comments__posts_trackbacked(); ?>
					<?php if ( sizeof($comments_trackbacked) > 0 ) : ?>
						
						<?php if ( meneame_comments__get_option('lastupdate') == "-1" ) : // If not make a First Load?>
							
							<?php echo '<div class="updated"><p><strong>'.__('This will be your first comment download, please be patient!!!', 'beplugin').'</strong></p></div>'; ?>
							<strong><?php _e('Comments with trackback', 'beplugin'); ?>:</strong> <?php echo sizeof($comments_trackbacked); ?>
							
							<p>
								<input class="button-secondary" type="submit" name="do" value="<?php echo attribute_escape(__('Make First Load', 'beplugin')); ?>" onclick="fnMeneameCommentsAjax(this, 'meneame_comments__ajax__infotext', 'makeFirstLoad'); return false;" />
								<span id="meneame_comments__ajax__infotext"><?php _e('Press button to update ALL posts with trackbacks.', 'beplugin'); ?></span>
							</p>
							
						<?php else: ?>
							
							<strong><?php _e('Comments from last week with trackback', 'beplugin'); ?>:</strong> <?php echo sizeof($comments_trackbacked); ?>
							
							<p>
								<input class="button-secondary" type="submit" name="do" value="<?php echo attribute_escape(__('Update Comments', 'beplugin')); ?>" onclick="fnMeneameCommentsAjax(this, 'meneame_comments__ajax__infotext', 'updateComments'); return false;" />
								<span id="meneame_comments__ajax__infotext"><?php _e('Press button to update ALL posts with trackbacks.', 'beplugin'); ?></span>
							</p>
							
						<?php endif; ?>
						
					<?php else: ?>
						
						<strong><?php _e('No trackbacks in date to download', 'beplugin'); ?></strong>
						
						<!-- <p>
							<input class="button-secondary" type="submit" name="do" value="<?php //echo attribute_escape(__('Update All Comments', 'beplugin')); ?>" onclick="fnMeneameCommentsAjax(this, 'meneame_comments__ajax__infotext', 'updateComments'); return false;" />
							<span id="meneame_comments__ajax__infotext"><?php //_e('Press button to update ALL posts with trackbacks.', 'beplugin'); ?></span>
						</p> -->
						
					<?php endif; ?>
				</td>
			</tr>
							
			<?php
				$single_cron = meneame_comments__get_option('single_cron');
				$single_cron_lastupdate = meneame_comments__get_option('single_cron_lastupdate');
				
				if ( $single_cron && sizeof($single_cron) > 0 ) {
					
					krsort($single_cron);
					
					echo '<tr>';
						echo '<th>'.__('Jobs', 'beplugin').'</th>';
						echo '<td>';
							foreach($single_cron as $kSingleC => $vSingleC ){
								echo '<p><strong><a href="'.get_permalink($kSingleC).'">'.get_the_title($kSingleC).'</a></strong> ('.date('d.m.Y H:i:s', $vSingleC).')</p>';
							}
							echo '<p><small>'.__('Server Time', 'beplugin').': '.date('d.m.Y H:i:s').'</small></p>';
						echo '</td>';
					echo '</tr>';
					
					
					
				}
			?>
		</table>
		
		<h3><?php _e('New Comments Options', 'beplugin'); ?></h3>
		
		<table class="form-table">
			<tr>
				<th><?php _e('Status', 'beplugin'); ?></th>
				<td>
					<p><label for="meneame_comments__new_comment_status_tb"><input type="radio" id="meneame_comments__new_comment_status_tb" name="meneame_comments__new_comment_status" value="tb" <?php if ( meneame_comments__get_option('new_comment_status') == 'tb' ) echo 'checked="checked"'; ?> /> <?php _e('Equal to Trackback','beplugin'); ?></label></p>
					<p><label for="meneame_comments__new_comment_status_1"><input type="radio" id="meneame_comments__new_comment_status_1" name="meneame_comments__new_comment_status" value="1" <?php if ( meneame_comments__get_option('new_comment_status') == '1' ) echo 'checked="checked"'; ?> /> <?php _e('Approve','beplugin'); ?></label></p>
					<p><label for="meneame_comments__new_comment_status_0"><input type="radio" id="meneame_comments__new_comment_status_0" name="meneame_comments__new_comment_status" value="0" <?php if ( meneame_comments__get_option('new_comment_status') == '0' ) echo 'checked="checked"'; ?> /> <?php _e('Moderate','beplugin'); ?></label></p>
					<p><label for="meneame_comments__new_comment_status_spam"><input type="radio" id="meneame_comments__new_comment_status_spam" name="meneame_comments__new_comment_status" value="spam" <?php if ( meneame_comments__get_option('new_comment_status') == 'spam' ) echo 'checked="checked"'; ?> /> <?php _e('Spam','beplugin'); ?></label></p>
				</td>
			</tr>
		</table>
		
		<h3><?php _e('Men&eacute;ame Configuration', 'beplugin'); ?></h3>
		
		<table class="form-table">
			<tr>
				<th><?php _e('Comments Feed URL', 'beplugin'); ?></th>
				<td><input name="meneame_comments__rss_url" id="meneame_comments__rss_url" value="<?php echo meneame_comments__get_option('rss_url'); ?>" size="50" type="text" /></td>
			</tr>
			<tr>
				<th><?php _e('URL ID Parameter', 'beplugin'); ?></th>
				<td><input name="meneame_comments__rss_param_id" id="meneame_comments__rss_param_id" value="<?php echo meneame_comments__get_option('rss_param_id'); ?>" size="20" type="text" /></td>
			</tr>
			<tr>
				<th><?php _e('URL Karma Parameter', 'beplugin'); ?></th>
				<td><input name="meneame_comments__rss_param_karma" id="meneame_comments__rss_param_karma" value="<?php echo meneame_comments__get_option('rss_param_karma'); ?>" size="20" type="text" /></td>
			</tr>
			<tr>
				<th><?php _e('Minimal Karma', 'beplugin'); ?></th>
				<td><input name="meneame_comments__rss_min_karma" id="meneame_comments__rss_min_karma" value="<?php echo meneame_comments__get_option('rss_min_karma'); ?>" size="4" type="text" /></td>
			</tr>
		</table>
		
		<p class="submit">
			<input type="submit" name="do" class="button" value="<?php _e('Update Options', 'beplugin'); ?>" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'beplugin'); ?>" class="button" onclick="javascript:history.go(-1)" /> 
		</p>
		
		<p>&nbsp;</p>
		
		<h2><?php _e('Management', 'beplugin'); ?></h2>
		
		<p>
			<input class="button-secondary" type="submit" name="do" value="<?php echo attribute_escape(__('Delete Meneame Comments', 'beplugin')); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete all Meneame Comments', 'beplugin'); ?>');" />
		</p>
		
		
		
		<?php //echo '<hr />'; meneame_comments__check_trackback(21); echo '<hr />'; ?>
	</div>
</form>