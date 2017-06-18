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

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-song' )
		return $content;
	return kgr_links_content_filter( $content, 'kgr-song' );
} );

add_filter( 'the_content', function( string $content ): string {
	if ( get_post_type() !== 'kgr-song' )
		return $content;
	$params = [
		'pdf' => [
			'dashicon' => 'dashicons-media-document',
		],
		'mid' => [
			'dashicon' => 'dashicons-media-audio',
		],
		'mp3' => [
			'dashicon' => 'dashicons-media-audio',
		],
		'xml' => [
			'dashicon' => 'dashicons-media-code',
		],
		'txt' => [
			'dashicon' => 'dashicons-media-text',
		],
	];
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
	] );
	if ( empty( $attachments ) )
		return $content;
	$content .= sprintf( '<h2 class="kgr-meta">%s</h2>', __( 'Files', 'kgr' ) ) . "\n";
	foreach ( $attachments as $attachment ) {
		$dir = get_attached_file( $attachment->ID );
		$ext = pathinfo( $dir, PATHINFO_EXTENSION );
		if ( !array_key_exists( $ext, $params ) )
			continue;
		$param = $params[ $ext ];
		$size = kgr_filesize( $dir );
		$url = wp_get_attachment_url( $attachment->ID );
		$caption = $attachment->post_excerpt;
		$description = $attachment->post_content;
		$content .= '<p>' . "\n";
		if ( array_key_exists( 'dashicon', $param ) )
			$content .= sprintf( '<span class="dashicons %s"></span>', $param['dashicon'] ) . "\n";
		$content .= sprintf( '<a href="%s" target="_blank">%s</a>', $url, $caption ) . "\n";
		$content .= sprintf( '<span>[%s, %s]</span>', $ext, $size ) . "\n";
		if ( $description !== '' )
			$content .= '<br />' . "\n" . sprintf( '<i>%s</i>', $description ) . "\n";
		if ( $ext === 'mp3' )
			$content .= sprintf( '<audio controls="controls" src="%s" style="display: block;"></audio>', $url ) . "\n";
		$content .= '</p>' . "\n";
	}
	return $content;
} );
