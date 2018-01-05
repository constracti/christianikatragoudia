<?php

if ( !defined( 'ABSPATH' ) )
	exit;

$kgr_widget = NULL;

add_filter( 'widget_title', function( string $title ): string {
	global $kgr_widget;
	$pos = mb_strpos( $title, ':' );
	if ( $pos !== FALSE ) {
		$kgr_widget = mb_substr( $title, $pos + 1 );
		$title = mb_substr( $title, 0, $pos );
	} else
		$kgr_widget = NULL;
	return $title;
}, 5 );

add_filter( 'widget_posts_args', 'kgr_widget_args_filter' );
add_filter( 'widget_archives_dropdown_args', 'kgr_widget_args_filter' );
add_filter( 'widget_archives_args', 'kgr_widget_args_filter' );
function kgr_widget_args_filter( array $args ): array {
	global $kgr_widget;
	if ( is_null( $kgr_widget ) )
		return $args;
	return wp_parse_args( $kgr_widget, $args );
}
