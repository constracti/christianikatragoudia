<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'the_excerpt', function( string $excerpt ): string {
	if ( get_post_type() !== 'kgr-song' )
		return $excerpt;
	$song = get_the_ID();
	$subjects = wp_get_post_terms( $song, 'kgr-subject' );
	if ( !empty( $subjects ) ) {
		$excerpt .= '<div class="tagcloud">' . "\n";
		foreach ( $subjects as $subject )
			$excerpt .= sprintf( '<a href="%s">%s</a>', get_term_link( $subject ), $subject->name ) . "\n";
		$excerpt .= '</div>' . "\n";
	}
	return $excerpt;
} );
