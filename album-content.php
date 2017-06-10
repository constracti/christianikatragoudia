<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-album' )
		return $content;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		return $content;
	$content .= '<ol>' . "\n";
	foreach ( $tracks as $track_id ) {
		if ( $track_id === 0 ) {
			$content .= '<li></li>' . "\n";
			continue;
		}
		$track = get_post( $track_id );
		$url = get_permalink( $track->ID );
		$title = $track->post_title;
		$content .= sprintf( '<li><a href="%s">%s</a></li>', $url, $title ) . "\n";
	}
	$content .= '</ol>' . "\n";
	return $content;
} );

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-album' )
		return $content;
	return kgr_links_content_filter( $content, 'kgr-album' );
} );
