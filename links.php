<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_post', function( WP_Post $post ): void {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	add_meta_box( 'kgr-links', __( 'Links', 'kgr' ), 'kgr_links_html', $post->post_type, 'normal' );
} );

function kgr_links_html( WP_Post $post ): void {
	$links = get_post_meta( $post->ID, 'kgr-links', TRUE );
	if ( $links === '' )
		$links = [];
	echo '<div class="multi-control-home">' . "\n";
	echo sprintf( '<input type="hidden" class="multi-control-action" value="%s">', 'kgr_links' ) . "\n";
	echo sprintf( '<input type="hidden" class="multi-control-id" value="%s">', $post->ID ) . "\n";
	echo sprintf( '<input type="hidden" class="multi-control-nonce" value="%s">', wp_create_nonce( kgr_links_nonce( $post->ID ) ) ) . "\n";
	echo '<div class="multi-control-list">' . "\n";
	foreach ( $links as $link )
		kgr_links_div( $link );
	echo '</div>' . "\n";
	echo '<div class="multi-control-new">' . "\n";
	kgr_links_div();
	echo '</div>' . "\n";
	echo '<p>' . "\n";
	echo sprintf( '<button type="button" class="button button-primary">%s</button>', __( 'save', 'kgr' ) ) . "\n";
	echo '<span class="spinner" style="float: none;"></span>' . "\n";
	echo sprintf( '<button type="button" class="button multi-control-add" style="float: right;">%s</button>', __( 'add', 'kgr' ) ) . "\n";
	echo '</p>' . "\n";
	echo '</div>' . "\n";
}

function kgr_links_div( array $link = [ 'url' => '', 'caption' => '', 'description' => '', ] ): void {
	echo '<p class="multi-control-item">' . "\n";
	echo sprintf( '<input type="url" data-multi-control-name="url" placeholder="%s" autocomplete="off" value="%s" style="width: 100%%;" />', __( 'URL', 'kgr' ), $link['url'] ) . "\n";
	echo '<br />' . "\n";
	echo sprintf( '<input type="text" data-multi-control-name="caption" placeholder="%s" autocomplete="off" value="%s" />', __( 'caption', 'kgr' ), $link['caption'] ) . "\n";
	echo sprintf( '<input type="text" data-multi-control-name="description" placeholder="%s" autocomplete="off" value="%s" />', __( 'description', 'kgr' ), $link['description'] ) . "\n";
	echo '<span style="float: right;">' . "\n";
	echo sprintf( '<button type="button" class="button multi-control-delete">%s</button>', __( 'delete', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button multi-control-up">%s</button>', __( 'up', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button multi-control-down">%s</button>', __( 'down', 'kgr' ) ) . "\n";
	echo '</span>' . "\n";
	echo '</p>' . "\n";
}

function kgr_links_nonce( int $post ): string {
	return sprintf( 'kgr-links-%d', $post );
}

add_action( 'wp_ajax_kgr_links', function(): void {
	if ( !array_key_exists( 'id', $_POST ) )
		exit( 'post' );
	$post = filter_var( $_POST['id'], FILTER_VALIDATE_INT );
	if ( $post === FALSE )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post ) )
		exit( 'role' );
	if ( !array_key_exists( 'nonce', $_POST ) )
		exit( 'nonce' );
	if ( !wp_verify_nonce( $_POST['nonce'], kgr_links_nonce( $post ) ) )
		exit( 'nonce' );
	if ( !array_key_exists( 'values', $_POST ) )
		delete_post_meta( $post, 'kgr-links' );
	else
		update_post_meta( $post, 'kgr-links', $_POST['values'] );
	exit;
} );

add_action( 'admin_enqueue_scripts', function( string $hook ): void {
	if ( !current_user_can( 'edit_posts' ) )
		return;
	if ( !in_array( $hook, [ 'post.php', 'post-new.php' ] ) )
		return;
	wp_enqueue_script( 'kgr-control', KGR_URL . 'multi-control/script.js', [ 'jquery' ], kgr_version() );
	wp_enqueue_script( 'kgr-control-save', KGR_URL . 'control-save.js', [ 'jquery' ], kgr_version() );
} );
