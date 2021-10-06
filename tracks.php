<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_post', function( WP_Post $post ): void {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	if ( !has_category( 'albums', $post ) )
		return;
	add_meta_box( 'xt-tracks', __( 'Tracks', 'xt' ), 'xt_tracks_html', $post->post_type, 'normal' );
} );

function xt_tracks_html( WP_Post $album ): void {
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	$tracks = get_post_meta( $album->ID, 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		$tracks = [];
	$nonce = wp_create_nonce( xt_tracks_nonce( $album->ID ) );
?>
<div class="multi-control-home">
	<input type="hidden" class="multi-control-action" value="xt_tracks">
	<input type="hidden" class="multi-control-id" value="<?= $album->ID ?>">
	<input type="hidden" class="multi-control-nonce" value="<?= $nonce ?>">
	<ol class="multi-control-list">
<?php
	foreach ( $tracks as $track )
		xt_tracks_div( $songs, $track );
?>
	</ol>
	<ol class="multi-control-new">
<?php
	xt_tracks_div( $songs );
?>
	</ol>
	<p>
		<button type="button" class="button button-primary"><?= esc_html__( 'save', 'xt' ) ?></button>
		<span class="spinner" style="float: none;"></span>
		<button type="button" class="button multi-control-add" style="float: right;"><?= esc_html__( 'add', 'xt' ) ?></button>
	</p>
</div>
<?php
}

function xt_tracks_div( array $songs, int $track = 0 ): void {
?>
		<li class="multi-control-item">
			<select data-multi-control-name="id">
				<option value="0"></option>
<?php
	foreach ( $songs as $song ) {
		$selected = selected( $song->ID, $track, FALSE );
		$title = $song->post_title;
		$excerpt = $song->post_excerpt;
		$limit = 100;
		if ( mb_strlen( $excerpt ) > $limit )
			$excerpt = mb_substr( $excerpt, 0, $limit ) . '&hellip;';
?>
				<option value="<?= $song->ID ?>"<?= $selected ?>><?= esc_html( sprintf( '%s ( %s )', $title, $excerpt ) ) ?></option>
<?php
	}
?>
			</select>
			<span style="float: right;">
				<button type="button" class="button multi-control-delete"><?= esc_html__( 'delete', 'xt' ) ?></button>
				<button type="button" class="button multi-control-up"><?= esc_html__( 'up', 'xt' ) ?></button>
				<button type="button" class="button multi-control-down"><?= esc_html__( 'down', 'xt' ) ?></button>
			</span>
		</li>
<?php
}

function xt_tracks_nonce( int $album ): string {
	return sprintf( 'xt-tracks-%d', $album );
}

add_action( 'wp_ajax_xt_tracks', function(): void {
	if ( !array_key_exists( 'id', $_POST ) )
		exit( 'album' );
	$album = filter_var( $_POST['id'], FILTER_VALIDATE_INT );
	if ( $album === FALSE )
		exit( 'album' );
	if ( !current_user_can( 'edit_post', $album ) )
		exit( 'role' );
	if ( !array_key_exists( 'nonce', $_POST ) )
		exit( 'nonce' );
	if ( !wp_verify_nonce( $_POST['nonce'], xt_tracks_nonce( $album ) ) )
		exit( 'nonce' );
	if ( !array_key_exists( 'values', $_POST ) )
		delete_post_meta( $album, 'kgr-tracks' );
	else
		update_post_meta( $album, 'kgr-tracks', array_map( 'intval', array_column( $_POST['values'], 'id' ) ) );
	exit;
} );

add_action( 'admin_enqueue_scripts', function( string $hook ): void {
	if ( !current_user_can( 'edit_posts' ) )
		return;
	if ( $hook !== 'post.php' )
		return;
	if ( !has_category( 'albums' ) )
		return;
	wp_enqueue_script( 'xt-control', XT_URL . 'multi-control/script.js', [ 'jquery', ], xt_version() );
	wp_enqueue_script( 'xt-control-save', XT_URL . 'control-save.js', [ 'jquery', ], xt_version() );
} );
