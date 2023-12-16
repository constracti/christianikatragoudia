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

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_patch_2
add_action( 'wp_ajax_nopriv_xt_app_patch_2', function(): void {
	$after = isset( $_GET['after'] ) ? intval( $_GET['after'] ) : 0;
	$after = wp_date( 'Y-m-d H:i:s', $after, new DateTimeZone( 'UTC' ) );
	$full = isset( $_GET['full'] ) ? $_GET['full'] === "true" : FALSE;
	$now = current_time( 'timestamp', TRUE );
	$date_query = [
		[
			'column' => 'post_modified_gmt',
			'after' => $after,
			'inclusive' => TRUE,
		],
	];
	$song_id_list = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
		'fields' => 'ids',
	] );
	$chord_id_list = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'text/plain',
		'fields' => 'ids',
	] );
	$song_list = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'date_query' => $date_query,
	] );
	$song_list = array_map( function( WP_Post $post ) use ( $full ): array {
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'content' => $full ? $post->post_content : '',
			'title' => $post->post_title,
			'excerpt' => $post->post_excerpt,
			'modified' => $post->post_modified_gmt,
			'permalink' => get_permalink( $post ),
		];
	}, $song_list );
	$chord_list = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'text/plain',
		'date_query' => $date_query,
	] );
	$chord_list = array_map( function( WP_Post $post ) use ( $full ): array {
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
		if ( !mb_ereg( '^([A-G])(bb?|#|x)?', $tonality, $m ) )
			return [];
		$tonality = $m[1] . $m[2];
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'modified' => $post->post_modified_gmt,
			'parent' => $post->post_parent,
			'content' => $full ? $text : '',
			'tonality' => $tonality,
		];
	}, $chord_list );
	header( 'content-type: application/json' );
	exit( json_encode( [
		'timestamp' => $now,
		'song_id_list' => $song_id_list,
		'chord_id_list' => $chord_id_list,
		'song_list' => $song_list,
		'chord_list' => $chord_list,
	] ) );
} );
