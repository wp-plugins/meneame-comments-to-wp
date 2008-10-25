<?php
/*
Plugin Name: Meneame Comments
Plugin URI: http://blogestudio.com/plugins/meneame-comments/
Description: Automatic system to obtain the comments of your entries in Meneame
Author: Alejandro Carravedo (Blogestudio)
Author URI: http://blogestudio.com/
Version: 0.0.11
Date: 2008-10-25 19:00:00
*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

// Cogemos la ruta
$meneamec_wp_dirname = basename(dirname(dirname(__FILE__))); // for "plugins" or "mu-plugins"
$meneamec_pi_dirname = basename(dirname(__FILE__)); // plugin name

$meneamec_path = WP_CONTENT_DIR.'/'.$meneamec_wp_dirname.'/'.$meneamec_pi_dirname;
$meneamec_url = WP_CONTENT_URL.'/'.$meneamec_wp_dirname.'/'.$meneamec_pi_dirname;

// Load the location file
load_plugin_textdomain('beplugin', false, $meneamec_pi_dirname.'/langs');

require_once( 'meneame-comments-ajax.php' );
require_once( 'meneame-comments-cronfooter.php' );


$meneame_comments__defaults = meneame_comments__get_vardefault('defaults');
$meneame_comments__check_cache2 = meneame_comments__get_vardefault('check_cache2');


/* Function to INIT Plugin */
function meneame_comments__register_activation_hook() {
	
	// Get defaults
	$meneame_comments__defaults = meneame_comments__get_vardefault('defaults');
	// Recover options from DB
	$options_db = get_option('meneame_comments');
	// Merge default with DB, preference from DB
	$meneame_comments__defaults = array_merge($meneame_comments__defaults, $options_db);
	
	// Update options
	update_option('meneame_comments', $meneame_comments__defaults);
	delete_option('meneame_comments__check_cache');
	update_option('meneame_comments__check_cache2', meneame_comments__get_vardefault('check_cache2'));
	
}
register_activation_hook( __FILE__, 'meneame_comments__register_activation_hook' );


function meneame_comments__get_vardefault( $varName = '' ) {
	
	switch($varName) {
		case "defaults":
			$varValue = array(
				'rss_url' => 'http://meneame.net/comments_rss2.php',
				'rss_param_id' => 'id',
				'rss_param_karma' => 'min_karma',
				'rss_min_karma' => 0,
				'cron_time' => array(
					'active' => true,
					'minutes' => 0,
					'hours' => 0,
					'days' => 3,
				),
				'comment_author_email' => 'meneame@blogestudio.com',
				'lastupdate' => '-1',
				'new_comment_status' => '1',
			);
			break;
		
		case "check_cache2":
		case "single_cron":
		case "single_cron_lastupdate":
			$varValue = array();
			break;
		
		default:
			$varValue = '';
			break;
	}
	
	return $varValue;
}


/* Get Values from Array Options DB */
function meneame_comments__get_option( $name ) {
	
	$meneame_comments = get_option('meneame_comments');
	
	if ( isset($meneame_comments[$name]) ) {
		return $meneame_comments[$name];
	}else{
		return false;
	}
}

function meneame_comments__set_option( $name, $value = '' ) {
	
	$meneame_comments = get_option('meneame_comments');
	
	$meneame_comments[$name] = $value;
	
	update_option('meneame_comments', $meneame_comments);
	
}


/* Function to Check if plugin is installed */
function meneame_comments__plugininstalled() {
	return true;
}


/* Add Admin Menu to WP Administrator */
function meneame_comments__admin_menu() {
	global $meneamec_pi_dirname;
	
	if (function_exists('add_options_page')) {
		add_options_page(
			__('Men&eacute;ame Comments to WP', 'beplugin'),
			__('Men&eacute;ame Comments to WP', 'beplugin'),
			'manage_options',
			$meneamec_pi_dirname.'/meneame-comments-options.php') ;
	}
}
add_action('admin_menu', 'meneame_comments__admin_menu');


/* Function to get TRACKBACKS from Meneame, all posts or one post */
function meneame_comments__posts_trackbacked( $post_id = '' ) {
	global $wpdb;
	
	$lastupdate = meneame_comments__get_option('lastupdate');
	
	return $wpdb->get_results("
		SELECT *
		FROM $wpdb->comments
		WHERE comment_author_url LIKE 'http://meneame.net/story/%'
			". ( ( $post_id != '' ) ? ' AND comment_post_ID = '.$post_id.' ' : '' ) ."
			". ( ( $lastupdate > 0 ) ? " AND comment_date >= '". date('Y-m-d H:i:s', ($lastupdate - meneame_comments__refresh_seconds()) )."'" : "" ) ."
			AND ( comment_type = 'trackback' )
			AND comment_approved = '1'
		ORDER BY comment_post_id DESC
	", ARRAY_A);
} //  OR comment_type = 'pingback'


/* Function to get especific trackback, checking "meneame.net" host */
function meneame_comments__check_trackback( $tb_id ) {
	
	// Get info from trackback
	if ( is_array($tb_id) ) $commentdata = $tb_id;
	else $commentdata = get_commentdata( $tb_id, 1, true );
	
	// Solo se gestionan los tracbacks, no los pingbacks, si lo haces
	// te bajas los comentarios de otras entradas meneadas
	if ( $commentdata['comment_type'] != 'trackback' )
		return false;
	
	$parsedCommentAuthorURL = parse_url($commentdata['comment_author_url']);
	
	if ( $parsedCommentAuthorURL['host'] == 'meneame.net' ) {
		return meneame_comments__parseNEWS( $commentdata['comment_author_url'], $commentdata['comment_post_ID'], $commentdata['comment_approved'] ); //$commentdata['comment_author_url']);
	}
	
	return false;
}


/* Parse HTML from trackback URL */
function meneame_comments__parseNEWS( $url, $post_id = 0, $approved = '' ) {
	global $meneame_comments__defaults;
	
	require_once(ABSPATH . WPINC . '/rss.php');
	
	$htmlObj = _fetch_remote_file($url);
	$htmlCode = $htmlObj->results;
	
	// Search Patterns
	$tag_pattern = '/<link.*?>/i';
	$reAttribute = '/%s *= *["\']([^"\']*?)["\']/i';
	
	// Search LINK Tags from HTML
	if (preg_match_all ($tag_pattern, $htmlCode, $matches, PREG_OFFSET_CAPTURE)) {
		
		// For each search result (match)
		foreach($matches[0] as $match) {
			
			// Get LINK Tag
			$linkTag = $match[0];
			// Search REL Attribute
			if ( preg_match( sprintf($reAttribute, 'rel'), $linkTag, $relAtt, PREG_OFFSET_CAPTURE) ) {
				// If REL Attribute eq "alternate"
				if ( reset(end($relAtt)) == 'alternate' ) {
					// Search TYPE Attribute
					if ( preg_match( sprintf($reAttribute, 'type'), $linkTag, $typeAtt, PREG_OFFSET_CAPTURE) ) {
						// If TYPE Attribute eq "application/rss+xml"
						if ( reset(end($typeAtt)) == 'application/rss+xml' ) {
							// Search HREF Attribute
							if ( preg_match( sprintf($reAttribute, 'href'), $linkTag, $hrefAtt, PREG_OFFSET_CAPTURE) ) {
								// If HREF Attribute contains "Meneame Comments RSS URL"
								$hrefAttValue = reset(end($hrefAtt));
								if ( strpos( $hrefAttValue, $meneame_comments__defaults['rss_url'] ) !== false ) {
									// Parse URL
									$parsedURL = parse_url($hrefAttValue);
									// Parse QUery
									parse_str($parsedURL['query'], $parsedQuery);
									
									// If exists ID as parameter
									if ( $parsedQuery[$meneame_comments__defaults['rss_param_id']] != '' ) {
										
										// Mount Meneame Comments URL with ID and KARMA
										$rssURL = $meneame_comments__defaults['rss_url'];
											$rssURL .= '?';
											$rssURL .= $meneame_comments__defaults['rss_param_id'].'='.$parsedQuery[$meneame_comments__defaults['rss_param_id']];
											$rssURL .= '&';
											$rssURL .= $meneame_comments__defaults['rss_param_karma'].'='.$meneame_comments__defaults['rss_min_karma'];
										
										// Load RSS
										return meneame_comments__getRSS($rssURL, $post_id, $approved);
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	return false;
}


/* Download RSS Comments from Meneame */
function meneame_comments__getRSS( $url, $post_id, $approved = '' ) {
	global $meneame_comments__defaults, $post;
	
	$post_id = ( $post ) ? $post->ID : $post_id;
	
	require_once(ABSPATH . WPINC . '/rss.php');
	
	$oldCacheRSS = MAGPIE_CACHE_ON;
	define('MAGPIE_CACHE_ON', 0);
	$rss = @fetch_rss( $url );
	define('MAGPIE_CACHE_ON', $oldCacheRSS);
	
	if ( !isset($rss->items) || 0 == count($rss->items) )
		return false;
	
	//$rss->items = array_slice($rss->items, 0, $items);
	foreach ($rss->items as $item ) {
		meneame_comments__insert($item, $post_id, $approved);
	}
	
	return true;
}


/* Insert Comments in DB */
function meneame_comments__insert( $item, $post_id, $approved = '' ) {
	global $wpdb;
	
	$charset = 'ASCII, UTF-8, ISO-8859-1, JIS, EUC-JP, SJIS';
	
	if ( $post_id ) {
		
		// Create VARS for COMMENT function
		$comment_post_ID = (int) $post_id;
		$comment_author = $item['dc']['creator'];
		$comment_author_email = meneame_comments__get_option('comment_author_email');
		$comment_author_url = clean_url($item['link']);
		$comment_content = meneame_comments__clean_content($item['description']);
		$comment_date = date("Y-m-d H:i:s", strtotime($item['pubdate']));
		$comment_type = 'comment_meneame';
		
		if ( function_exists('mb_convert_encoding') ) { // For international 
			$comment_content   = mb_convert_encoding($comment_content, get_option('blog_charset'), $charset);
		}
		
		// Now that mb_convert_encoding() has been given a swing, we need to escape these three
		$comment_content   = $wpdb->escape($comment_content);
		
		// Comment to Moderate
		switch( meneame_comments__get_option('new_comment_status') ) {
			case "tb":
				$comment_approved = $approved;
				break;
			case "1":
				$comment_approved = 1;
				break;
			case "0":
			default:
				$comment_approved = 0;
				break;
		}
		
		// Comment User Agent
		$comment_agent = $_SERVER['HTTP_USER_AGENT'];
		
		// Crop TITLE and EXCERPT
		//$mncm_excerpt = wp_html_excerpt( $mncm_excerpt, 252 ).'...';
		
		// Array to Insert Comment in DB of WordPress
		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_date', 'comment_type', 'comment_approved', 'comment_agent');
		
		// Check Duplicity of Comment in DB
		$duplicatedBol = meneame_comments__check_duplicate($commentdata);
		
		if ( !$duplicatedBol ) {
			return true;
		}else{
			return false;
		}
	}
}


function meneame_comments__check_duplicate( $commentdata ) {
	global $wpdb, $meneame_comments__check_cache2;
	
	update_option('meneame_comments__check_cache2', array());
	
	if ( !$meneame_comments__check_cache2 ) {
		$meneame_comments__check_cache2 = get_option('meneame_comments__check_cache2');
	}
	
	$updateComment = false;
	
	$dupe  = "SELECT comment_ID ";
	$dupe .= "FROM $wpdb->comments ";
	$dupe .= "WHERE comment_post_ID = '".$commentdata['comment_post_ID']."' ";
		$dupe .= "AND (";
			$dupe .= "comment_author = '".$commentdata['comment_author']."'";
			$dupe .= ( $comment_author_email ) ? " OR comment_author_email = '".$commentdata['comment_author_email']."'" : "";
		$dupe .= ") ";
		$dupe .= "AND comment_author_url = '".$commentdata['comment_author_url']."' ";
		// $dupe .= "AND comment_content = '".$commentdata['comment_content']."' ";
	$dupe .= "LIMIT 1";
	
	if ( isset($meneame_comments__check_cache2[$dupe]) ) {
		// If comment found update comment, for "text" changes
		$comment_ID = $meneame_comments__check_cache2[$dupe];
		if ( $comment_ID ) $updateComment = true;
		
	}else{
		// Execute Query
		$comment_ID = $wpdb->get_var($dupe);
		
		// Cache the query
		$meneame_comments__check_cache2[$dupe] = $comment_ID;
		update_option('meneame_comments__check_cache2', $meneame_comments__check_cache2);
		
		// If comment found update comment, for "text" changes
		if ( $comment_ID ) $updateComment = true;
	}
	
	if ( $updateComment ) {
		
		// Add comment_ID to data
		$commentdata['comment_ID'] = $comment_ID;
		unset($commentdata['comment_approved']);
		
		// Update comment
		wp_update_comment($commentdata);
		
		// Clean CACHE plugins
		meneame_comments__clean_cache_post($commentdata['comment_post_ID']);
		
		return true;
	}else{
		
		// Get ID from new comment
		$commend_ID = wp_insert_comment($commentdata);
		
		// Assign ID to query cached
		$meneame_comments__check_cache2[$dupe] = $comment_ID;
		update_option('meneame_comments__check_cache2', $meneame_comments__check_cache2);
		
		// Clean CACHE plugins
		meneame_comments__clean_cache_post($commentdata['comment_post_ID']);
		
		return false;
	}
}


function meneame_comments__clean_cache_post( $post_id ) {
	
	// Si tenemos el WP-Super-Cache, lo limpiamos...
	if ( function_exists('wp_cache_post_edit') ) wp_cache_post_edit($post_id);
	// Si no esta, es posible que tengamos WP Cache ...
	else if ( function_exists('wp_cache_post_change') ) wp_cache_post_change($post_id);
	
}


function meneame_comments__clean_content( $comment_content = '' ) {
	
	if ( preg_match_all('/&#(\d+);/', $comment_content, $chars) ) {
		foreach ( (array) $chars[1] as $char ) {
			// If it's an encoded char in the normal ASCII set, reject
			if ( 38 == $char )
				continue; // Unless it's &
			if ( $char >= 32 && $char < 128 )
				$comment_content = str_replace("&#".$char.";", chr($char), $comment_content);
		}
	}
	
	return $comment_content;
}



function meneame_comments__trackback_post( $tb_id ) {
	
	// Get info from trackback
	if ( is_array($tb_id) ) $commentdata = $tb_id;
	else $commentdata = get_commentdata( $tb_id, 1, true );
	
	if ( $commendata->comment_type == 'trackback' ) {
		$parsedCommentAuthorURL = parse_url($commentdata['comment_author_url']);
		
		if ( $parsedCommentAuthorURL['host'] == 'meneame.net' ) {
			
			// Cogemos los tiempos de actualizacion de cada POST
			$single_cron = meneame_comments__get_option('single_cron');
			if ( !$single_cron ) $single_cron = array();
			
			$single_cron[$commentdata['comment_post_ID']] = time() + (30 * 60);
			meneame_comments__set_option('single_cron', $single_cron);
			
			
			// Cogemos los tiempos de la ULTIMA ACTUALIZACION de cada POST
			$single_cron_lastupdate = meneame_comments__get_option('single_cron_lastupdate');
			if ( !$single_cron_lastupdate ) $single_cron_lastupdate = array();
			
			$single_cron_lastupdate[$commentdata['comment_post_ID']] = time() + meneame_comments__refresh_seconds();
			meneame_comments__set_option('single_cron_lastupdate', $single_cron_lastupdate);
		}
	}
}

add_action('trackback_post', 'meneame_comments__trackback_post');



function meneame_comments__refresh_seconds( $days = 8, $hours = 0, $mins = 0, $secs = 0 ) {
	
	$time = $secs + 60; // segundos
	$time += ( $mins * 60 ); // minutos
	$time += ( $hours * 60 * 60 ); // horas
	$time += ( $days * 24 * 60 * 60 ); // dias
	
	return $time;
}

function meneame_comments__refresh_cron_seconds( $days = 0, $hours = 0, $mins = 15, $secs = 0 ) {
	return meneame_comments__refresh_seconds( $days, $hours, $mins, $secs );
}


/* ***************** */
/* Template Funcions */
/* ***************** */

/* Get array with comments from meneame */
function meneame_comments__only_meneame( $args = '' ) {
	global $post, $wpdb;
	
	// Argumentos por defecto de la funcion
	$defaults = array(
		'post_id' => '',
	);
	
	// Parseamos los argumentos
	$args = wp_parse_args( $args, $defaults );
	
	if ( $args['post_id'] == '' && !$post )
		return false;
	
	$args['post_id'] = ( $args['post_id'] == '' ) ? $post->ID : $args['post_id'];
	
	$only_meneame = $wpdb->get_results("
		SELECT *
		FROM $wpdb->comments
		WHERE comment_post_ID = '".$args['post_id']."'
			AND comment_approved = '1'
			AND comment_type = 'comment_meneame'
		ORDER BY comment_date
	");
	
	return $only_meneame;
}

/* Get array without comments from meneame */
function meneame_comments__without_meneame( $args = '' ) {
	global $post, $wpdb;
	
	// Argumentos por defecto de la funcion
	$defaults = array(
		'post_id' => '',
		'comments' => 1,
		'trackbacks' => 1,
		'pinbacks' => 1,
	);
	
	// Parseamos los argumentos
	$args = wp_parse_args( $args, $defaults );
	
	if ( $args['post_id'] == '' && !$post )
		return false;
	
	$args['post_id'] = ( $args['post_id'] == '' ) ? $post->ID : $args['post_id'];
	
	$sqlTypes = array();;
	if ( $args['comments'] ) $sqlTypes[] = ' comment_type = "" ';
	if ( $args['trackbacks'] ) $sqlTypes[] = ' comment_type = "trackback" ';
	if ( $args['pinbacks'] ) $sqlTypes[] = ' comment_type = "pingback" ';
	
	$without_meneame = $wpdb->get_results("
		SELECT *
		FROM $wpdb->comments
		WHERE comment_post_ID = '".$args['post_id']."'
			AND comment_approved = '1'
			" . ( sizeof($sqlTypes) ? ' AND (' . implode(" OR ", $sqlTypes) . ') ' : '' ) . "
			AND comment_type != 'comment_meneame'
		ORDER BY comment_date
	");
	
	return $without_meneame;
}

?>
