<?php

// If post_id set, count a view
if ( intval($_GET['mc_pid']) > 0 ) {
	
	### Include wp-config.php
	@require('../../../wp-config.php');
	cache_javascript_headers();
	
	// Cogemos los tiempos de actualizacion de cada POST
	$single_cron = meneame_comments__get_option('single_cron');
	if ( !$single_cron ) $single_cron = array();
	
	// Cogemos los tiempos de la ULTIMA ACTUALIZACION de cada POST
	$single_cron_lastupdate = meneame_comments__get_option('single_cron_lastupdate');
	if ( !$single_cron_lastupdate ) $single_cron_lastupdate = array();
	
	// Si tiene que hacer actualizacion y esta dentro del plazo final de actualizaciones ...
	if ( isset($single_cron[$_GET['mc_pid']])
		&& time() >= $single_cron[$_GET['mc_pid']]
		&& isset($single_cron_lastupdate[$_GET['mc_pid']])
		&& time() <= $single_cron_lastupdate[$_GET['mc_pid']]
	) {
		
		// Inicializamos la ultima actualizacion (por mantenimiento de versiones), fuerza la revision
		meneame_comments__set_option('lastupdate', 0);
		
		// Conseguimos los trackbacks de meneame del post concreto
		$comments_trackbacked = meneame_comments__posts_trackbacked($_GET['mc_pid']);
		
		// Paramos el "reconteo" de Comentario, que tarda un monton
		wp_defer_comment_counting(true);
		
		// Por cada trackback de meneame ...
		foreach($comments_trackbacked as $commentdata) {
			
			// Si no tenemos informacion del trackback, lo obtenemos
			if ( is_array($commentdata) ) $commentdata_info = $commentdata;
			else $commentdata_info = get_commentdata( $commentdata, 1, true );
			
			// Cogemos la fecha del trackback
			$comment_date_tocheck = mysql2date('U', $commentdata_info['comment_date']);
			// Calculamos la fecha en la que se deberia parar el refresco.
			$comment_date_tocheck_top = $comment_date_tocheck + meneame_comments__refresh_seconds();
			
			// Actualizamos el SINGLE CRON
			$single_cron[$_GET['mc_pid']] = time() + meneame_comments__refresh_cron_seconds();
			
			// Si ya debiera haberse parado...
			if ( $comment_date_tocheck_top < time() ) {
				// Quitamos las tareas del post
				unset($single_cron[$_GET['mc_pid']]);
				meneame_comments__set_option('single_cron', $single_cron);
				
				unset($single_cron_lastupdate[$_GET['mc_pid']]);
				meneame_comments__set_option('single_cron_lastupdate', $single_cron_lastupdate);
				
				// Salimos del loop
				break;
			}
			
			// Refrescamos los posts
			meneame_comments__check_trackback($commentdata);
		}
		
		// Re-Encendemos el "reconteo" de Comentario, que tarda un monton
		wp_defer_comment_counting(NULL);
		
		// Actualizamos datos
		meneame_comments__set_option('single_cron', $single_cron);
		meneame_comments__set_option('lastupdate', time());
		
	// Si ha pasado el tiempo de actualizar este post....
	}elseif ( isset($single_cron_lastupdate[$_GET['mc_pid']])
		&& time() >= $single_cron_lastupdate[$_GET['mc_pid']]
	) {
		
		// Quitamos las tareas del post
		unset($single_cron[$_GET['mc_pid']]);
		meneame_comments__set_option('single_cron', $single_cron);
		
		unset($single_cron_lastupdate[$_GET['mc_pid']]);
		meneame_comments__set_option('single_cron_lastupdate', $single_cron_lastupdate);
		
		meneame_comments__set_option('lastupdate', time());
	}
	
}

?>