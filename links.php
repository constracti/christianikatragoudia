<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_post', function( WP_Post $post ): void {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	add_meta_box( 'xt-links', __( 'Links', 'xt' ), 'xt_links_html', $post->post_type, 'normal' );
} );

function xt_links_html( WP_Post $post ): void {
	$links = get_post_meta( $post->ID, 'kgr-links', TRUE );
	if ( $links === '' )
		$links = [];
	$nonce = wp_create_nonce( xt_links_nonce( $post->ID ) );
?>
<div class="multi-control-home">
	<input type="hidden" class="multi-control-action" value="xt_links">
	<input type="hidden" class="multi-control-id" value="<?= $post->ID ?>">
	<input type="hidden" class="multi-control-nonce" value="<?= $nonce ?>">
	<div class="multi-control-list">
<?php
	foreach ( $links as $link )
		xt_links_div( $link );
?>
	</div>
	<div class="multi-control-new">
<?php
	xt_links_div();
?>
	</div>
	<p>
		<button type="button" class="button button-primary"><?= esc_html__( 'save', 'xt' ) ?></button>
		<span class="spinner" style="float: none;"></span>
		<button type="button" class="button multi-control-add" style="float: right;"><?= esc_html__( 'add', 'xt' ) ?></button>
	</p>
</div>
<?php
}

function xt_links_div( $link = NULL ): void {
	if ( is_null( $link ) )
		$link = array_fill_keys( [ 'url', 'caption', 'description', ], '' );
?>
		<p class="multi-control-item">
			<input type="url" data-multi-control-name="url" placeholder="<?= esc_attr__( 'URL', 'xt' ) ?>" autocomplete="off" value="<?= esc_attr( $link['url'] ) ?>" style="width: 100%;">
			<br>
			<input type="text" data-multi-control-name="caption" placeholder="<?= esc_attr__( 'caption', 'xt' ) ?>" autocomplete="off" value="<?= esc_attr( $link['caption'] ) ?>">
			<input type="text" data-multi-control-name="description" placeholder="<?= esc_attr__( 'description', 'xt' ) ?>" autocomplete="off" value="<?= esc_attr( $link['description'] ) ?>">
			<span style="float: right;">
				<button type="button" class="button multi-control-delete"><?= esc_html__( 'delete', 'xt' ) ?></button>
				<button type="button" class="button multi-control-up"><?= esc_html__( 'up', 'xt' ) ?></button>
				<button type="button" class="button multi-control-down"><?= esc_html__( 'down', 'xt' ) ?></button>
			</span>
		</p>
<?php
}

function xt_links_nonce( int $post ): string {
	return sprintf( 'xt-links-%d', $post );
}

add_action( 'wp_ajax_xt_links', function(): void {
	if ( !array_key_exists( 'id', $_POST ) )
		exit( 'post' );
	$post = filter_var( $_POST['id'], FILTER_VALIDATE_INT );
	if ( $post === FALSE )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post ) )
		exit( 'role' );
	if ( !array_key_exists( 'nonce', $_POST ) )
		exit( 'nonce' );
	if ( !wp_verify_nonce( $_POST['nonce'], xt_links_nonce( $post ) ) )
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
	if ( !in_array( $hook, [ 'post.php', 'post-new.php', ], TRUE ) )
		return;
	wp_enqueue_script( 'xt-control', XT_URL . 'multi-control/script.js', [ 'jquery', ], xt_version() );
	wp_enqueue_script( 'xt-control-save', XT_URL . 'control-save.js', [ 'jquery', ], xt_version() );
} );
