<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_kgr-album', 'kgr_links_metabox_add' );
add_action( 'add_meta_boxes_kgr-song', 'kgr_links_metabox_add' );

function kgr_links_metabox_add( WP_Post $post ) {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	add_meta_box( 'kgr-links', __( 'Links', 'kgr' ), 'kgr_links_metabox_html', $post->post_type, 'normal' );
}

function kgr_links_metabox_html( WP_Post $post ) {
	$links = get_post_meta( $post->ID, 'kgr-links', TRUE );
	if ( $links === '' )
		$links = [];
	echo '<div class="kgr-control-container">' . "\n";
	echo '<div class="kgr-control-items">' . "\n";
	foreach ( $links as $link )
		kgr_links_metabox_div( $link );
	echo '</div>' . "\n";
	echo '<div class="kgr-control-item0">' . "\n";
	kgr_links_metabox_div();
	echo '</div>' . "\n";
	echo '<p>' . "\n";
	$nonce = wp_create_nonce( kgr_links_metabox_nonce( $post->ID ) );
	echo sprintf( '<button type="button" class="button button-primary" data-nonce="%s" data-post="%s">%s</button>', $nonce, $post->ID, __( 'save', 'kgr' ) ) . "\n";
	echo '<span class="spinner" style="float: none;"></span>' . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-add" style="float: right;">%s</button>', __( 'add', 'kgr' ) ) . "\n";
	echo '</p>' . "\n";
	echo '</div>' . "\n";
}

function kgr_links_metabox_div( array $link = [ 'url' => '', 'caption' => '', 'description' => '' ] ) {
	echo '<p class="kgr-control-item">' . "\n";
	echo sprintf( '<input type="url" class="kgr-link-url" placeholder="%s" autocomplete="off" value="%s" style="width: 100%%;" />', __( 'URL', 'kgr' ), $link['url'] ) . "\n";
	echo '<br />' . "\n";
	echo sprintf( '<input type="text" class="kgr-link-caption" placeholder="%s" autocomplete="off" value="%s" />', __( 'caption', 'kgr' ), $link['caption'] ) . "\n";
	echo sprintf( '<input type="text" class="kgr-link-description" placeholder="%s" autocomplete="off" value="%s" />', __( 'description', 'kgr' ), $link['description'] ) . "\n";
	echo '<span style="float: right;">' . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-delete">%s</button>', __( 'delete', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-up">%s</button>', __( 'up', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="button kgr-control-down">%s</button>', __( 'down', 'kgr' ) ) . "\n";
	echo '</span>' . "\n";
	echo '</p>' . "\n";
}

function kgr_links_metabox_nonce( int $post ): string {
	return sprintf( 'kgr-links-metabox-%d', $post );
}

add_action( 'wp_ajax_kgr_links_metabox', function() {
	if ( !array_key_exists( 'post', $_POST ) )
		exit( 'post' );
	$post = filter_var( $_POST['post'], FILTER_VALIDATE_INT );
	if ( $post === FALSE )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post ) )
		exit( 'role' );
	if ( !array_key_exists( 'nonce', $_POST ) )
		exit( 'nonce' );
	if ( !wp_verify_nonce( $_POST['nonce'], kgr_links_metabox_nonce( $post ) ) )
		exit( 'nonce' );
	if ( !array_key_exists( 'links', $_POST ) )
		delete_post_meta( $post, 'kgr-links' );
	else
		update_post_meta( $post, 'kgr-links', $_POST['links'] );
	exit;
} );

add_action( 'admin_enqueue_scripts', function( string $hook ) {
	if ( !current_user_can( 'edit_posts' ) )
		return;
	if ( !in_array( $hook, [ 'post.php', 'post-new.php' ] ) )
		return;
	wp_enqueue_style( 'kgr-control', KGR_URL . 'control.css' );
	wp_enqueue_script( 'kgr-control', KGR_URL . 'control.js', [ 'jquery' ] );
	wp_enqueue_script( 'kgr-links-metabox', KGR_URL . 'links-metabox.js', [ 'jquery' ] );
} );

function kgr_links_content_filter( string $content, string $post_type ): string {
	$links = get_post_meta( get_the_ID(), 'kgr-links', TRUE );
	if ( $links === '' )
		return $content;
	$params = [
		'youtube' => [
			'dashicon' => 'dashicons-media-video',
		],
		'vimeo' => [
			'dashicon' => 'dashicons-media-video',
		],
		'livefilestore.com' => [
			'dashicon' => 'dashicons-cloud',
		],
		'' => [
			'dashicon' => 'dashicons-media-default',
		],
	];
	if ( empty( $links ) )
		return $content;
	$content .= sprintf( '<h2 class="kgr-meta">%s</h2>', __( 'Links', 'kgr' ) ) . "\n";
	foreach ( $links as $link ) {
		$url = $link['url'];
		foreach ( $params as $key => $param )
			if ( $key === '' || mb_strpos( $url, $key ) !== FALSE )
				break;
		$caption = $link['caption'];
		$description = $link['description'];
		$content .= '<p>' . "\n";
		$content .= sprintf( '<span class="dashicons %s"></span>', $param['dashicon'] ) . "\n";
		$content .= sprintf( '<a href="%s" target="_blank">%s</a>', $url, $caption ) . "\n";
		if ( $key !== '' )
			$content .= sprintf( '<span>[%s]</span>', $key ) . "\n";
		if ( $description !== '' )
			$content .= '<br />' . "\n" . sprintf( '<i>%s</i>', $description ) . "\n";
		if ( $key === 'livefilestore.com' )
			$content .= sprintf( '<audio controls="controls" src="%s" style="display: block;"></audio>', $url ) . "\n";
		$content .= '</p>' . "\n";
	}
	return $content;
}
