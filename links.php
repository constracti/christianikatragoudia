<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes', function( string $post_type, WP_Post $post ): void {
	if ( $post_type !== 'post' )
		return;
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	add_meta_box( 'xt_links', __( 'Links', 'xt' ), [ 'XT_Links', 'home_echo' ], NULL, 'normal' );
}, 10, 2 );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( !current_user_can( 'edit_posts' ) )
		return;
	if ( !in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], TRUE ) )
		return;
	wp_enqueue_style( 'xt-flex', XT::url( 'flex.css' ), [], XT::version() );
	wp_enqueue_script( 'xt-table', XT::url( 'table.js' ), [ 'jquery' ], XT::version() );
} );

final class XT_Links {

	public static function load( WP_Post $post ): array {
		$links = get_post_meta( $post->ID, 'xt_links', TRUE );
		if ( $links === '' )
			return [];
		return $links;
	}

	public static function save( WP_Post $post, array $links ): void {
		if ( empty( $links ) )
			delete_post_meta( $post->ID, 'xt_links' );
		else
			update_post_meta( $post->ID, 'xt_links', $links );
	}

	public static function home( WP_Post $post ): string {
		$html = '<div class="xt-table-home xt-flex-col xt-root" style="margin: -6px -12px -12px -12px;">' . "\n";
		$html .= '<div class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= self::refresh_button( $post );
		$html .= '<span class="xt-table-spinner xt-leaf spinner" data-xt-table-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="xt-leaf" />' . "\n";
		$html .= '<div class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<div class="xt-leaf">%s</div>', esc_html__( 'Links', 'xt' ) ) . "\n";
		$html .= self::insert_button( $post );
		$html .= '</div>' . "\n";
		$html .= self::table( $post );
		$html .= self::form();
		$html .= '</div>' . "\n";
		return $html;
	}

	public static function home_echo( WP_Post $post ): void {
		echo self::home( $post );
	}

	private static function refresh_button( WP_Post $post ): string {
		return sprintf( '<a%s>%s</a>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_links_refresh',
				'post' => $post->ID,
				'nonce' => XT::nonce_create( 'xt_links_refresh', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link xt-leaf button',
		] ), esc_html__( 'Refresh', 'xt' ) ) . "\n";
	}

	private static function insert_button( WP_Post $post ): string {
		return sprintf( '<a%s>%s</a>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_links_insert',
				'post' => $post->ID,
				'nonce' => XT::nonce_create( 'xt_links_insert', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-insert xt-leaf button',
			'data-xt-table-form' => '.xt-table-form-link',
		] ), esc_html__( 'Add', 'xt' ) ) . "\n";
	}

	private static function table( WP_Post $post ): string {
		$links = self::load( $post );
		$html = '<div class="xt-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= self::table_head_row( $post );
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $links as $i => $link )
			$html .= self::table_body_row( $post, $i, $link );
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function table_head_row( WP_Post $post ): string {
		$html = '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary has-row-actions">%s</th>', esc_html__( 'URL', 'xt' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Description', 'xt' ) ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function table_body_row( WP_Post $post, int $i, array $link ): string {
		$actions = [];
		$actions[] = sprintf( '<span><a href="%s" target="_blank">%s</a></span>', esc_url_raw( $link['url'] ), esc_html__( 'Open', 'xt' ) );
		$actions[] = sprintf( '<span><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_links_update',
				'post' => $post->ID,
				'link' => $i,
				'nonce' => XT::nonce_create( 'xt_links_update', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-insert',
			'data-xt-table-form' => '.xt-table-form-link',
			'data-xt-table-field-url' => esc_url( $link['url'] ),
			'data-xt-table-field-description' => esc_attr( $link['description'] ),
		] ), esc_html__( 'Edit', 'xt' ) );
		$actions[] = sprintf( '<span class="delete"><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_links_delete',
				'post' => $post->ID,
				'link' => $i,
				'nonce' => XT::nonce_create( 'xt_links_delete', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link',
			'data-xt-table-confirm' => esc_attr( sprintf( __( 'Delete link %s?', 'xt' ), $link['description'] ) ),
		] ), esc_html__( 'Delete', 'xt' ) );
		$actions[] = sprintf( '<span><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_links_up',
				'post' => $post->ID,
				'link' => $i,
				'nonce' => XT::nonce_create( 'xt_links_up', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link',
		] ), esc_html__( 'Up', 'xt' ) );
		$actions[] = sprintf( '<span><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_links_down',
				'post' => $post->ID,
				'link' => $i,
				'nonce' => XT::nonce_create( 'xt_links_down', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link',
		] ), esc_html__( 'Down', 'xt' ) );
		$html = '<tr>' . "\n";
		$html .= '<td class="column-primary has-row-actions">' . "\n";
		$html .= sprintf( '<strong>%s</strong>', esc_url( $link['url'] ) ) . "\n";
		$html .= sprintf( '<div class="row-actions">%s</div>', implode( ' | ', $actions ) ) . "\n";
		$html .= '</td>' . "\n";
		$html .= sprintf( '<td>%s</td>', esc_html( $link['description'] ) ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function form(): string {
		$html = '<div class="xt-table-form xt-table-form-link xt-leaf xt-root xt-root-border xt-flex-col" style="display: none;">' . "\n";
		$html .= '<label class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="xt-leaf" style="width: 6em;">%s</span>', esc_html__( 'URL', 'xt' ) ) . "\n";
		$html .= '<input type="text" class="xt-table-field xt-leaf xt-flex-grow" data-xt-table-name="url" />' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<label class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="xt-leaf" style="width: 6em;">%s</span>', esc_html__( 'Description', 'xt' ) ) . "\n";
		$html .= '<input type="text" class="xt-table-field xt-leaf xt-flex-grow" data-xt-table-name="description" />' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<div class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="xt-table-link xt-table-submit xt-leaf button button-primary">%s</a>', esc_html__( 'Submit', 'xt' ) ) . "\n";
		$html .= sprintf( '<a href="" class="xt-table-cancel xt-leaf button">%s</a>', esc_html__( 'Cancel', 'xt' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}
}

add_action( 'wp_ajax_' . 'xt_links_refresh', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	XT::nonce_verify( 'xt_links_refresh', $post->ID );
	XT::success( XT_Links::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_links_insert', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	XT::nonce_verify( 'xt_links_insert', $post->ID );
	$link = [
		'url' => XT_Request::post_text( 'url' ),
		'description' => XT_Request::post_text( 'description' ),
	];
	$links = XT_Links::load( $post );
	$links[] = $link;
	XT_Links::save( $post, $links );
	XT::success( XT_Links::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_links_update', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'link' );
	$links = XT_Links::load( $post );
	if ( !array_key_exists( $i, $links ) )
		exit( 'link' );
	XT::nonce_verify( 'xt_links_update', $post->ID, $i );
	$link = [
		'url' => XT_Request::post_text( 'url' ),
		'description' => XT_Request::post_text( 'description' ),
	];
	$links[$i] = $link;
	XT_Links::save( $post, $links );
	XT::success( XT_Links::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_links_delete', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'link' );
	$links = XT_Links::load( $post );
	if ( !array_key_exists( $i, $links ) )
		exit( 'link' );
	XT::nonce_verify( 'xt_links_delete', $post->ID, $i );
	unset( $links[$i] );
	$links = array_values( $links );
	XT_Links::save( $post, $links );
	XT::success( XT_Links::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_links_up', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'link' );
	$links = XT_Links::load( $post );
	if ( !array_key_exists( $i, $links ) )
		exit( 'link' );
	XT::nonce_verify( 'xt_links_up', $post->ID, $i );
	if ( $i > 0 ) {
		$repl = array_splice( $links, $i, 1 );
		array_splice( $links, $i - 1, 0, $repl );
	}
	XT_Links::save( $post, $links );
	XT::success( XT_Links::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_links_down', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'link' );
	$links = XT_Links::load( $post );
	if ( !array_key_exists( $i, $links ) )
		exit( 'link' );
	XT::nonce_verify( 'xt_links_down', $post->ID, $i );
	if ( $i < count( $links ) - 1 ) {
		$repl = array_splice( $links, $i, 1 );
		array_splice( $links, $i + 1, 0, $repl );
	}
	XT_Links::save( $post, $links );
	XT::success( XT_Links::home( $post ) );
} );
