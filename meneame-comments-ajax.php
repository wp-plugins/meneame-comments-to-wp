<?php

/* ************** */
/* AJAX FUNCTIONS */
/* ************** */

/* JS Code */
function meneame_comments__admin_print_scripts() { // this is a PHP function
	global $meneamec_url;
	
	// use JavaScript SACK library for Ajax
	wp_print_scripts( array( 'sack' ));
	
	// Define custom JavaScript function
	echo '
		<script type="text/javascript">
			//<![CDATA[
			
			fnMeneameCommentsAjax__objBtn = false;
			fnMeneameCommentsAjax__objBtn_PreValue = "";
			
			function fnMeneameCommentsAjax(objBtn, results_div_id, fnValue) {
  	  	var mysack = new sack("'. get_bloginfo( 'wpurl' ) .'/wp-admin/admin-ajax.php");
				
				if ( objBtn ) {
					document.getElementById(results_div_id).innerHTML = \'<img src="'.$meneamec_url.'/loading.gif" />\';
					
					fnMeneameCommentsAjax__objBtn = objBtn;
					fnMeneameCommentsAjax__objBtn_PreValue = objBtn.value;
					objBtn.value = "'.__('Executing ...', 'beplugin').' ...";
				}
				
				mysack.execute = 1;
				mysack.method = "POST";
				
				switch(fnValue) {
					case "makeFirstLoad":
						mysack.setVar("action", "meneame_comments__ajax__firstloadcomments" );
						break;
					case "updateComments":
						mysack.setVar("action", "meneame_comments__ajax__updatecomments" );
						break;
				}
				
				mysack.setVar("results_div_id", results_div_id);
				mysack.encVar("cookie", document.cookie, false );
				mysack.onError = function() { alert("Ajax error!!") };
				if ( fnMeneameCommentsAjax__objBtn_PreValue != "" ) mysack.onCompletion = fnMeneameCommentsAjax__complet;
				mysack.runAJAX();
				
				return false;
			} // end of JavaScript function myplugin_ajax_elevation
			
			function fnMeneameCommentsAjax__complet() {
				
				if ( fnMeneameCommentsAjax__objBtn && fnMeneameCommentsAjax__objBtn_PreValue != "" ) {
					fnMeneameCommentsAjax__objBtn.value = fnMeneameCommentsAjax__objBtn_PreValue;
				}
				
			}
			//]]>
		</script>
	';
} // end of PHP function myplugin_js_admin_header
add_action('admin_print_scripts', 'meneame_comments__admin_print_scripts' );



/* Function executed when press the "Make First Load" button */
function meneame_comments__ajax__firstloadcomments( $results_div_id = '', $bolForceReload = true ) {
	
	// Capa de resultados (AJAX)
	$results_div_id = $_POST['results_div_id'];
	
	// Cuando iniciamos el proceso??
	$tInitTime = time();
	
	
	/* Iniciamos el proceso */
		/*
		// Inicializamos la fecha del plugin
		meneame_comments__set_option('lastupdate', '-1');
		
		// Conseguimos todos los trackbacks de meneame
		$comments_trackbacked_all = meneame_comments__posts_trackbacked();
		
		// Si tenemos trackbacks de meneame ...
		if ( is_array($comments_trackbacked_all) && sizeof($comments_trackbacked_all) > 0 ) {
			
			// Inicializamos una variable para guardar los trackbacks que van a quedar pendientes de actualizar
			$comments_trackbacked_firstload = array();
			
			// Fecha minima que debe tenerse para utilizar los trackbacks
			$limitTime = time() - meneame_comments__refresh_seconds();
			
			foreach ( $comments_trackbacked_all as $kTb => $vTb ) {
				// Comprobamos la fecha de cada entrada, si esta por debajo de la fecha de actualizacion lo incluimos en la lista
				if ( $limitTime > mysql2date('U', $vTb['comment_date']) ) {
					$comments_trackbacked_firstload[] = $vTb['comment_ID'];
				}
			}
			
			$text .= sprintf( __('(Pendents %s trackbacks)', 'beplugin'), sizeof($comments_trackbacked_firstload) );
			
			// Guardamos la lista, para la posterior actualización
			meneame_comments__set_option('firstload', $comments_trackbacked_firstload);
			
			// Actualizamos la fecha de ultima actualizacion con la fecha limite
			meneame_comments__set_option('lastupdate', time());
			
			// Actualizamos los comentarios
			meneame_comments__ajax__updatecomments($results_div_id, true);
			
		}
		*/
		
		// Fecha de actualizacion a -1, para que cargue todos
		meneame_comments__set_option('lastupdate', '-1');
		// Actualizamos los comentarios
		meneame_comments__ajax__updatecomments($results_div_id, true);
		
	// Cuando hemos acabado el proceso??
	$tEndTime = time();
	
	// Cuanto hemos tardado??
	$tTotalTime = $tEndTime - $tInitTime;
	
	// Mensaje de respuesta
	$text = ''.__('Comments First Load Finished', 'beplugin').' ('.$tTotalTime.' s)' . ' ' . $text;
	
	
	$forceReload = ( $bolForceReload ) ? ' document.location = document.location.hash; ' : '';
	
	
	// Si hemos pedido capa (ajax) devolvemos el mensaje por JS ...
	if ( $results_div_id != '' ) {
		die( "document.getElementById('$results_div_id').innerHTML = '$text'; ".$forceReload."" );
	}else{
		echo ( '<div class="updated fade"><p><strong>'.$text.'</strong></p></div>' );
	}
	
}
add_action('wp_ajax_meneame_comments__ajax__firstloadcomments', 'meneame_comments__ajax__firstloadcomments' );





/* Function executed when press the "update comments" button */
function meneame_comments__ajax__updatecomments($results_div_id = '', $doReturn = false) {
	global $meneame_comments__defaults, $meneame_comments__check_cache;
	
	$bolForceReload = false; // Por si necesitamos recargar la pagina
	
	// Capa de resultados (AJAX)
	$results_div_id = $_POST['results_div_id'];
	
	// Cuando iniciamos el proceso??
	$tInitTime = time();
	
	// Que comentarios son trackbacks a meneame.
	$comments_trackbacked = meneame_comments__posts_trackbacked();
	
	
	// Como estamos forzando actualizacion, ningun post quedara sin probar, quitamos la hora de ejecucion
	$single_cron = array();
	// Obtenemos las fechas de ultimas actualizaciones (se mantiene, pq esta no puede variar) ...
	$single_cron_lastupdate = meneame_comments__get_option('single_cron_lastupdate');
	if ( !$single_cron_lastupdate ) $single_cron_lastupdate = array();
	$single_cron_lastupdate_count = sizeof($single_cron_lastupdate);
	
	// Si tenemos trackbacks de meneame dentro de periodo ...
	if ( is_array($comments_trackbacked) && sizeof($comments_trackbacked) > 0 ) {
		
		// Obtenemos la fecha de la ultima actualizacion
		$lastupdate = meneame_comments__get_option('lastupdate');
		if ( !$lastupdate ) $lastupdate = "-1";
		
		// Paramos el "reconteo" de Comentario, que tarda un monton
		wp_defer_comment_counting(true);
		
		// Por cada trackback obtenido hacemos los "calculos"...
		foreach($comments_trackbacked as $commentdata) {
			
			// Cogemos la fecha del trackback
			$comment_date_tocheck = mysql2date('U', $commentdata['comment_date']);
			// Calculamos la fecha en la que se deberia parar el refresco, cierran en Meneame.
			$comment_date_tocheck_top = $comment_date_tocheck + meneame_comments__refresh_seconds();
			
			if ( ( $comment_date_tocheck_top < time() && $lastupdate > 0 )
				|| ( isset($single_cron_lastupdate[$commentdata['comment_post_ID']]) && $single_cron_lastupdate[$commentdata['comment_post_ID']] < time() )
			) {
				
				unset($single_cron[$commentdata['comment_post_ID']]);
				unset($single_cron_lastupdate[$commentdata['comment_post_ID']]);
				$single_cron_lastupdate_count--;
				$bolForceReload = true;
				
			}else{
				
				// Comprobamos el trackback
				$text .= ( meneame_comments__check_trackback($commentdata) ) ? '#' : 'X';
				
				// Proxima actualizacion en 30 minutos
				$single_cron[$commentdata['comment_post_ID']] = time() + meneame_comments__refresh_cron_seconds();
				
				// Si no se ha actualizado aun, primero vez, calculamos 8 dias para cada entrada, 1 mas que en Meneame.
				if ( $lastupdate == "-1"  || !isset($single_cron_lastupdate[$commentdata['comment_post_ID']]) ) {
					if ( $comment_date_tocheck_top > time() ) {
						$single_cron_lastupdate[$commentdata['comment_post_ID']] = time() + meneame_comments__refresh_seconds();
					}else{
						unset($single_cron[$commentdata['comment_post_ID']]);
					}
				}
			}
		}
		
		// Si el numero inicial de actualizables es distinto del actual, recargamos la pagina
		if ( $single_cron_lastupdate_count != sizeof($single_cron_lastupdate) ) {
			$bolForceReload = true;
		}
		
		// Re-Encendemos el "reconteo" de Comentario, que tarda un monton
		wp_defer_comment_counting(NULL);
		
		// Guardamos las fechas en el sistema, para despues
		meneame_comments__set_option('single_cron', $single_cron);
		meneame_comments__set_option('single_cron_lastupdate', $single_cron_lastupdate);
	}else{
		
		// No tenemos trackbacks que procesar
		if ( sizeof($single_cron_lastupdate) > 0 ) {
			$bolForceReload = true;
		}
		
		// Guardamos las fechas en el sistema, para despues
		meneame_comments__set_option('single_cron', meneame_comments__get_vardefault('single_cron'));
		meneame_comments__set_option('single_cron_lastupdate', meneame_comments__get_vardefault('single_cron_lastupdate'));
		
	}
	
	// Guardamos la fecha de actualizacion
	meneame_comments__set_option('lastupdate', time());
	
	// Cuando hemos acabado el proceso??
	$tEndTime = time();
	
	// Cuanto hemos tardado??
	$tTotalTime = $tEndTime - $tInitTime;
	
	// Mensaje de respuesta
	$text = ''.__('Comments updated', 'beplugin').' ('.$tTotalTime.' s)' . ' ' . $text;
	
	if ( $doReturn ) return;
	
	$forceReload = ( $bolForceReload ) ? ' document.location = document.location.hash; ' : '';
	
	// Si hemos pedido capa (ajax) devolvemos el mensaje por JS ...
	if ( $results_div_id != '' ) {
		die( "document.getElementById('$results_div_id').innerHTML = '$text'; ".$forceReload."" );
	}else{
		echo ( '<div class="updated fade"><p><strong>'.$text.'</strong></p></div>' );
	}
}
add_action('wp_ajax_meneame_comments__ajax__updatecomments', 'meneame_comments__ajax__updatecomments' );

