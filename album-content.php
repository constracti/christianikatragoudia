<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-album' )
		return $content;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		return $content;
	$ids = [];
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
		$attachments = get_children( [
			'post_parent' => $track->ID,
			'post_type' => 'attachment',
			'order' => 'ASC',
		] );
		foreach ( $attachments as $attachment ) {
			if ( $attachment->post_excerpt !== '' )
				continue;
			$dir = get_attached_file( $attachment->ID );
			$ext = pathinfo( $dir, PATHINFO_EXTENSION );
			if ( $ext !== 'mp3' )
				continue;
			$ids[] = $attachment->ID;
		}
	}
	$content .= '</ol>' . "\n";
	if ( !empty( $ids ) )
		$content .= do_shortcode( sprintf( '[playlist artists="false" ids="%s"]', implode( ',', $ids ) ) );
	return $content;
} );
