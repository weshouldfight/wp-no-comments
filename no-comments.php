<?php

/*
Plugin Name: No Comments
Description: Turns off comments, pingbacks and trackbacks everywhere and removes them from the backend
Version:     1.0
Author:      Christoph Mandl
Author URI:  https://mandl.design
Update URI:  false
*/


// Closed for everything that accepts comments or pings: comment form, wp-comments-post.php,
// REST API, XML-RPC, wp-trackback.php. Also drops the X-Pingback header and comment feed links.
// Block editor notes (WP 6.9+) are comments too, but ignore this, so they keep working.

add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );


// Existing comments stay in the database, but no longer show up on the site

add_filter( 'comments_array', '__return_empty_array', 20 );
add_filter( 'get_comments_number', '__return_zero', 20 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

add_action( 'template_redirect', function () {
	if ( is_comment_feed() ) {
		wp_die( 'Kommentare sind abgeschaltet.', '', array( 'response' => 404 ) );
	}
}, 9 );

foreach ( array( 'core/comments', 'core/latest-comments', 'core/post-comments-form' ) as $block ) {
	add_filter( 'render_block_' . $block, '__return_empty_string' );
}

add_action( 'widgets_init', function () {
	unregister_widget( 'WP_Widget_Recent_Comments' );
} );


// New posts are saved closed, and the site sends no pingbacks to the sites it links to

add_filter( 'pre_option_default_comment_status', function () {
	return 'closed';
} );

add_filter( 'pre_option_default_ping_status', function () {
	return 'closed';
} );

add_filter( 'pre_option_default_pingback_flag', '__return_zero' );


// XML-RPC answers "method not found" instead of looking at the request

add_filter( 'xmlrpc_methods', function ( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'], $methods['wp.newComment'] );
	return $methods;
} );


// Backend: no discussion box, comment column, menu, admin bar item or discussion settings

add_action( 'wp_loaded', function () {
	foreach ( get_post_types() as $post_type ) {
		remove_post_type_support( $post_type, 'comments' );
		remove_post_type_support( $post_type, 'trackbacks' );
	}
} );

add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
} );

foreach ( array( 'edit-comments.php', 'comment.php', 'options-discussion.php' ) as $page ) {
	add_action( 'load-' . $page, function () {
		wp_safe_redirect( admin_url() );
		exit;
	} );
}

add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}, 100 );
