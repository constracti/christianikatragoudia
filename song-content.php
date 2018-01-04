<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-song' )
		return $content;
	$song = get_the_ID();
	$albums = get_posts( [
		'post_type' => 'kgr-album',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
		'meta_key' => 'kgr-tracks',
	] );
	$self = [];
	foreach ( $albums as $album ) {
		$tracks = get_post_meta( $album->ID, 'kgr-tracks', TRUE );
		if ( $tracks === '' )
			continue;
		$key = array_search( $song, $tracks, TRUE );
		if ( $key === FALSE )
			continue;
		$url = get_permalink( $album );
		$title = get_the_title( $album );
		$self[] = sprintf( '<p><a href="%s">%s</a> (%d)</p>', $url, $title, $key + 1 ) . "\n";
	}
	if ( !empty( $self ) ) {
		$content .= sprintf( '<h2 class="kgr-meta">%s</h2>', __( 'Albums', 'kgr' ) ) . "\n";
		$content .= implode( '', $self );
	}
	return $content;
} );

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-song' )
		return $content;
	$song = get_the_ID();
	// subjects
	$subjects = wp_get_post_terms( $song, 'kgr-subject' );
	if ( !empty( $subjects ) ) {
		$content .= sprintf( '<h2 class="kgr-meta">%s</h2>', __( 'Subjects', 'kgr' ) ) . "\n";
		$content .= '<div class="tagcloud">' . "\n";
		foreach ( $subjects as $subject )
			$content .= sprintf( '<a href="%s">%s</a>', get_term_link( $subject ), $subject->name ) . "\n";
		$content .= '</div>' . "\n";
	}
	// signatures
	$signatures = wp_get_post_terms( $song, 'kgr-signature' );
	if ( !empty( $signatures ) ) {
		$content .= sprintf( '<h2 class="kgr-meta">%s</h2>', __( 'Signatures', 'kgr' ) ) . "\n";
		$content .= '<div class="tagcloud">' . "\n";
		foreach ( $signatures as $signature )
			$content .= sprintf( '<a href="%s">%s</a>', get_term_link( $signature ), $signature->name ) . "\n";
		$content .= '</div>' . "\n";
	}
	return $content;
} );
