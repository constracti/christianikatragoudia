<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'the_excerpt', function( string $excerpt ): string {
	if ( get_post_type() !== 'kgr-album' )
		return $excerpt;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		return $excerpt;
	$count = 0;
	foreach ( $tracks as $track_id )
		if ( $track_id !== 0 )
			$count++;
	$excerpt .= sprintf( '<p>%d %s</p>', $count, __( 'songs', 'kgr' ) ) . "\n";
	return $excerpt;
} );
