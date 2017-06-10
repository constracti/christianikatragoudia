<?php

if ( !defined( 'ABSPATH' ) )
	exit;

$kgr_widget = NULL;

add_filter( 'widget_title', function( string $title ): string {
	global $kgr_widget;
	if ( $title === 'kgr-song' ) {
		$kgr_widget = $title;
		$title = __( 'Songs', 'kgr' );
	} elseif ( $title === 'kgr-album' ) {
		$kgr_widget = $title;
		$title = __( 'Albums', 'kgr' );
	} else {
		$kgr_widget = NULL;
	}
	return $title;
} );

add_filter( 'widget_posts_args', 'kgr_widget_args_filter' );
add_filter( 'widget_archives_dropdown_args', 'kgr_widget_args_filter' );
add_filter( 'widget_archives_args', 'kgr_widget_args_filter' );
function kgr_widget_args_filter( array $args ): array {
	global $kgr_widget;
	if ( is_null( $kgr_widget ) )
		return $args;
	$args['post_type'] = $kgr_widget;
	return $args;
}
