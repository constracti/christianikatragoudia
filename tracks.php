<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_post', function( WP_Post $post ) {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	if ( !has_category( 'albums', $post ) )
		return;
	add_meta_box( 'kgr-tracks', __( 'Tracks', 'kgr' ), 'kgr_tracks_html', $post->post_type, 'normal' );
} );

function kgr_tracks_html( WP_Post $album ) {
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	$tracks = get_post_meta( $album->ID, 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		$tracks = [];
	echo '<div class="kgr-control-container">' . "\n";
	echo '<ol class="kgr-control-items">' . "\n";
	foreach ( $tracks as $track )
		kgr_tracks_div( $songs, $track );
	echo '</ol>' . "\n";
	echo '<ol class="kgr-control-item0">' . "\n";
	kgr_tracks_div( $songs );
	echo '</ol>' . "\n";
	echo '<p>' . "\n";
	$nonce = wp_create_nonce( kgr_tracks_nonce( $album->ID ) );
	echo sprintf( '<button type="button" class="button button-primary" data-nonce="%s" data-album="%s">%s</button>', $nonce, $album->ID, __( 'save', 'kgr' ) ) . "\n";
	echo '<span class="spinner" style="float: none;"></span>' . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-add" style="float: right;">%s</button>', __( 'add', 'kgr' ) ) . "\n";
	echo '</p>' . "\n";
	echo '</div>' . "\n";
}

function kgr_tracks_div( array $songs, int $track = 0 ) {
	echo '<li class="kgr-control-item">' . "\n";
	echo '<select>' . "\n";
	echo sprintf( '<option value="%d">%s</option>', 0, 'none' ) . "\n";
	foreach ( $songs as $song ) {
		$selected = selected( $song->ID, $track, FALSE );
		$title = $song->post_title;
		$excerpt = $song->post_excerpt;
		$limit = 100;
		if ( mb_strlen( $excerpt ) > $limit )
			$excerpt = mb_substr( $excerpt, 0, $limit ) . '&hellip;';
		echo sprintf( '<option value="%d"%s>%s (%s)</option>', $song->ID, $selected, $title, $excerpt ) . "\n";
	}
	echo '</select>' . "\n";
	echo '<span style="float: right;">' . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-delete">%s</button>', __( 'delete', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-up">%s</button>', __( 'up', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-down">%s</button>', __( 'down', 'kgr' ) ) . "\n";
	echo '</span>' . "\n";
	echo '</li>' . "\n";
}

function kgr_tracks_nonce( int $album ): string {
	return sprintf( 'kgr-tracks-%d', $album );
}

add_action( 'wp_ajax_kgr_tracks', function() {
	if ( !array_key_exists( 'album', $_POST ) )
		exit( 'album' );
	$album = filter_var( $_POST['album'], FILTER_VALIDATE_INT );
	if ( $album === FALSE )
		exit( 'album' );
	if ( !current_user_can( 'edit_post', $album ) )
		exit( 'role' );
	if ( !array_key_exists( 'nonce', $_POST ) )
		exit( 'nonce' );
	if ( !wp_verify_nonce( $_POST['nonce'], kgr_tracks_nonce( $album ) ) )
		exit( 'nonce' );
	if ( !array_key_exists( 'tracks', $_POST ) )
		delete_post_meta( $album, 'kgr-tracks' );
	else
		update_post_meta( $album, 'kgr-tracks', array_map( 'intval', $_POST['tracks'] ) );
	exit;
} );

add_action( 'admin_enqueue_scripts', function( string $hook ) {
	if ( !current_user_can( 'edit_posts' ) )
		return;
	if ( !in_array( $hook, [ 'post.php', 'post-new.php' ] ) )
		return;
	if ( !has_category( 'albums' ) )
		return;
	wp_enqueue_style( 'kgr-control', KGR_URL . 'control.css' );
	wp_enqueue_script( 'kgr-control', KGR_URL . 'control.js', [ 'jquery' ] );
	wp_enqueue_script( 'kgr-tracks', KGR_URL . 'tracks.js', [ 'jquery' ] );
} );

add_filter( 'the_content', function( string $content ): string {
	if ( !has_category( 'albums' ) )
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
