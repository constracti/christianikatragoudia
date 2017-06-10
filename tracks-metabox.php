<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_kgr-album', 'kgr_tracks_metabox_add' );

function kgr_tracks_metabox_add( WP_Post $post ) {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	add_meta_box( 'kgr-tracks', __( 'Tracks', 'kgr' ), 'kgr_tracks_metabox_html', $post->post_type, 'normal' );
}

function kgr_tracks_metabox_html( WP_Post $album ) {
	$songs = get_posts( [
		'post_type' => 'kgr-song',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	$tracks = get_post_meta( $album->ID, 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		$tracks = [];
	echo '<div class="kgr-control-container">' . "\n";
	echo '<div class="kgr-control-items">' . "\n";
	foreach ( $tracks as $track )
		kgr_tracks_metabox_div( $songs, $track );
	echo '</div>' . "\n";
	echo '<div class="kgr-control-item0">' . "\n";
	kgr_tracks_metabox_div( $songs );
	echo '</div>' . "\n";
	echo '<p>' . "\n";
	$nonce = wp_create_nonce( kgr_tracks_metabox_nonce( $album->ID ) );
	echo sprintf( '<button type="button" class="button button-primary" data-nonce="%s" data-album="%s">%s</button>', $nonce, $album->ID, __( 'save', 'kgr' ) ) . "\n";
	echo '<span class="spinner" style="float: none;"></span>' . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-add" style="float: right;">%s</button>', __( 'add', 'kgr' ) ) . "\n";
	echo '</p>' . "\n";
	echo '</div>' . "\n";
}

function kgr_tracks_metabox_div( array $songs, int $track = 0 ) {
	echo '<p class="kgr-control-item">' . "\n";
	echo '<select>' . "\n";
	echo sprintf( '<option value="%d">%s</option>', 0, 'none' ) . "\n";
	foreach ( $songs as $song ) {
		$selected = selected( $song->ID, $track, FALSE );
		$title = $song->post_title;
		$excerpt = $song->post_excerpt;
		$limit = 100;
		if ( mb_strlen( $excerpt ) > $limit )
			$excerpt = sprintf( '%s…', mb_substr( $excerpt, 0, $limit ) );
		echo sprintf( '<option value="%d"%s>%s (%s)</option>', $song->ID, $selected, $title, $excerpt ) . "\n";
	}
	echo '</select>' . "\n";
	echo '<span style="float: right;">' . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-delete">%s</button>', __( 'delete', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-up">%s</button>', __( 'up', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-down">%s</button>', __( 'down', 'kgr' ) ) . "\n";
	echo '</span>' . "\n";
	echo '</p>' . "\n";
}

function kgr_tracks_metabox_nonce( int $album ): string {
	return sprintf( 'kgr-tracks-metabox-%d', $album );
}

add_action( 'wp_ajax_kgr_tracks_metabox', function() {
	if ( !array_key_exists( 'album', $_POST ) )
		exit( 'album' );
	$album = filter_var( $_POST['album'], FILTER_VALIDATE_INT );
	if ( $album === FALSE )
		exit( 'album' );
	if ( !current_user_can( 'edit_post', $album ) )
		exit( 'role' );
	if ( !array_key_exists( 'nonce', $_POST ) )
		exit( 'nonce' );
	if ( !wp_verify_nonce( $_POST['nonce'], kgr_tracks_metabox_nonce( $album ) ) )
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
	if ( get_post_type() !== 'kgr-album' )
		return;
	wp_enqueue_style( 'kgr-control', KGR_URL . 'control.css' );
	wp_enqueue_script( 'kgr-control', KGR_URL . 'control.js', [ 'jquery' ] );
	wp_enqueue_script( 'kgr-tracks-metabox', KGR_URL . 'tracks-metabox.js', [ 'jquery' ] );
} );
