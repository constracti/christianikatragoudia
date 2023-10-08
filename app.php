<?php

if ( !defined( 'ABSPATH' ) )
	exit;

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_songs_1
add_action( 'wp_ajax_nopriv_xt_app_songs_1', function(): void {
	$posts = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	$posts = array_map( function( WP_Post $post ): array {
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'content' => $post->post_content,
			'title' => $post->post_title,
			'excerpt' => $post->post_excerpt,
			'modified' => $post->post_modified_gmt,
			'permalink' => get_permalink( $post ),
		];
	}, $posts );
	header( 'content-type: application/json' );
	exit( json_encode( $posts ) );
} );

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_chords_1
add_action( 'wp_ajax_nopriv_xt_app_chords_1', function(): void {
	$posts = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'text/plain',
	] );
	$posts = array_map( function( WP_Post $post ): array {
		$path = get_attached_file( $post->ID );
		if ( $path === FALSE )
			return [];
		$text = file_get_contents( $path );
		if ( $text === FALSE )
			return [];
		$tonality = mb_split( '\s', $post->post_content );
		$tonality = array_pop( $tonality );
		if ( is_null( $tonality ) )
			return [];
		$tonality = mb_ereg_replace( '♭', 'b', $tonality );
		$tonality = mb_ereg_replace( '♯', '#', $tonality );
		if ( !mb_ereg( '^([A-G])(bb?|#|x)?', $tonality, $m ) )
			return [];
		$tonality = $m[1] . $m[2];
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'modified' => $post->post_modified_gmt,
			'parent' => $post->post_parent,
			'content' => $text,
			'tonality' => $tonality,
		];
	}, $posts );
	$posts = array_filter( $posts, function( array $post ): bool {
		return !empty( $post );
	} );
	header( 'content-type: application/json' );
	exit( json_encode( $posts ) );
} );
