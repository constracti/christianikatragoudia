<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes_post', function( WP_Post $post ): void {
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	if ( !has_category( 'albums', $post ) )
		return;
	add_meta_box( 'xt_tracks', __( 'Tracks', 'xt' ), [ 'XT_Tracks', 'home_echo' ], NULL, 'normal' );
} );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( !current_user_can( 'edit_posts' ) )
		return;
	if ( !in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], TRUE ) )
		return;
	wp_enqueue_style( 'xt-flex', XT::url( 'flex.css' ), [], XT::version() );
	wp_enqueue_script( 'xt-table', XT::url( 'table.js' ), [ 'jquery' ], XT::version() );
} );

final class XT_Tracks {

	public static function load( WP_Post $post ): array {
		$tracks = get_post_meta( $post->ID, 'xt_tracks', TRUE );
		if ( $tracks === '' )
			return [];
		return $tracks;
	}

	public static function save( WP_Post $post, array $tracks ): void {
		if ( empty( $tracks ) )
			delete_post_meta( $post->ID, 'xt_tracks' );
		else
			update_post_meta( $post->ID, 'xt_tracks', $tracks );
	}

	public static function home( WP_Post $post ): string {
		$html = '<div class="xt-table-home xt-flex-col xt-root" style="margin: -6px -12px -12px -12px;">' . "\n";
		$html .= '<div class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= self::refresh_button( $post );
		$html .= '<span class="xt-table-spinner xt-leaf spinner" data-xt-table-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="xt-leaf" />' . "\n";
		$html .= '<div class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<div class="xt-leaf">%s</div>', esc_html__( 'Tracks', 'xt' ) ) . "\n";
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
				'action' => 'xt_tracks_refresh',
				'post' => $post->ID,
				'nonce' => XT::nonce_create( 'xt_tracks_refresh', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link xt-leaf button',
		] ), esc_html__( 'Refresh', 'xt' ) ) . "\n";
	}

	private static function insert_button( WP_Post $post ): string {
		return sprintf( '<a%s>%s</a>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_tracks_insert',
				'post' => $post->ID,
				'nonce' => XT::nonce_create( 'xt_tracks_insert', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-insert xt-leaf button',
			'data-xt-table-form' => '.xt-table-form-track',
		] ), esc_html__( 'Insert', 'xt' ) ) . "\n";
	}

	private static function table( WP_Post $post ): string {
		$tracks = self::load( $post );
		$html = '<div class="xt-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= self::table_head_row( $post );
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $tracks as $i => $track )
			$html .= self::table_body_row( $post, $i, $track );
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function table_head_row( WP_Post $post ): string {
		$html = '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary has-row-actions">%s</th>', esc_html__( 'Title', 'xt' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Excerpt', 'xt' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Track', 'xt' ) ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function table_body_row( WP_Post $post, int $i, int|string|null $track ): string {
		$song = NULL;
		$text = NULL;
		if ( is_int( $track ) && $track > 0 )
			$song = get_post( $track );
		if ( is_string( $track ) )
			$text = $track;
		$actions = [];
		$actions[] = sprintf( '<span><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_tracks_update',
				'post' => $post->ID,
				'track' => $i,
				'nonce' => XT::nonce_create( 'xt_tracks_update', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-insert',
			'data-xt-table-form' => '.xt-table-form-track',
			'data-xt-table-field-song' => esc_attr( $song?->ID ),
			'data-xt-table-field-text' => esc_attr( $text ),
		] ), esc_html__( 'Replace', 'xt' ) );
		$actions[] = sprintf( '<span class="delete"><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_tracks_delete',
				'post' => $post->ID,
				'track' => $i,
				'nonce' => XT::nonce_create( 'xt_tracks_delete', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link',
			'data-xt-table-confirm' => esc_attr( sprintf( __( 'Remove track %d?', 'xt' ), $i + 1 ) ),
		] ), esc_html__( 'Remove', 'xt' ) );
		$actions[] = sprintf( '<span><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_tracks_up',
				'post' => $post->ID,
				'track' => $i,
				'nonce' => XT::nonce_create( 'xt_tracks_up', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link',
		] ), esc_html__( 'Up', 'xt' ) );
		$actions[] = sprintf( '<span><a%s>%s</a></span>', XT::atts( [
			'href' => add_query_arg( [
				'action' => 'xt_tracks_down',
				'post' => $post->ID,
				'track' => $i,
				'nonce' => XT::nonce_create( 'xt_tracks_down', $post->ID, $i ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'xt-table-link',
		] ), esc_html__( 'Down', 'xt' ) );
		$html = '<tr>' . "\n";
		$html .= '<td class="column-primary has-row-actions">' . "\n";
		$title = !is_null( $song ) ? esc_html( $song->post_title ) : ( !is_null( $text ) ? esc_html( $text ) : '&mdash;' );
		$title = sprintf( '<strong>%s</strong>',  $title );
		if ( !is_null( $song ) )
			$title = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url_raw( get_permalink( $song ) ), $title );
		$html .= $title . "\n";
		$html .= sprintf( '<div class="row-actions">%s</div>', implode( ' | ', $actions ) ) . "\n";
		$html .= '</td>' . "\n";
		$html .= sprintf( '<td>%s</td>', !is_null( $song ) ? esc_html( xt_first_line( $song->post_excerpt ) ) : '&mdash;' ) . "\n";
		$html .= sprintf( '<td>%d</td>', $i + 1 ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function form(): string {
		$songs = get_posts( [
			'category_name' => 'songs',
			'nopaging' => TRUE,
			'orderby' => 'post_title',
			'order' => 'ASC',
		] );
		$html = '<div class="xt-table-form xt-table-form-track xt-leaf xt-root xt-root-border xt-flex-col" style="display: none;">' . "\n";
		$html .= '<label class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="xt-leaf" style="width: 6em;">%s</span>', esc_html__( 'Song', 'xt' ) ) . "\n";
		$html .= '<select class="xt-table-field xt-leaf xt-flex-grow" data-xt-table-name="song" />' . "\n";
		$html .= '<option value="">&mdash;</option>' . "\n";
		foreach ( $songs as $song )
			$html .= sprintf( '<option value="%d">%s</option>', $song->ID, esc_html( sprintf( '%s (%s)', $song->post_title, xt_first_line( $song->post_excerpt ) ) ) ) . "\n";
		$html .= '</select>' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<label class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="xt-leaf" style="width: 6em;">%s</span>', esc_html__( 'Text', 'xt' ) ) . "\n";
		$html .= '<input type="text" class="xt-table-field xt-leaf xt-flex-grow" data-xt-table-name="text" />' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<div class="xt-flex-row xt-flex-justify-between xt-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="xt-table-link xt-table-submit xt-leaf button button-primary">%s</a>', esc_html__( 'Submit', 'xt' ) ) . "\n";
		$html .= sprintf( '<a href="" class="xt-table-cancel xt-leaf button">%s</a>', esc_html__( 'Cancel', 'xt' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}
}

add_action( 'wp_ajax_' . 'xt_tracks_refresh', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !has_category( 'albums', $post ) )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	XT::nonce_verify( 'xt_tracks_refresh', $post->ID );
	XT::success( XT_Tracks::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_tracks_insert', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !has_category( 'albums', $post ) )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	XT::nonce_verify( 'xt_tracks_insert', $post->ID );
	$song = XT_Request::post_post( 'song', TRUE );
	if ( !is_null( $song ) && !has_category( 'songs', $song ) )
		exit( 'song' );
	$text = XT_Request::post_text( 'text', TRUE );
	if ( !is_null( $song ) && !is_null( $text ) )
		exit( 'text' );
	$track = $song?->ID ?? $text;
	$tracks = XT_Tracks::load( $post );
	$tracks[] = $track;
	XT_Tracks::save( $post, $tracks );
	XT::success( XT_Tracks::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_tracks_update', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !has_category( 'albums', $post ) )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'track' );
	$tracks = XT_Tracks::load( $post );
	if ( !array_key_exists( $i, $tracks ) )
		exit( 'track' );
	XT::nonce_verify( 'xt_tracks_update', $post->ID, $i );
	$song = XT_Request::post_post( 'song', TRUE );
	if ( !is_null( $song ) && !has_category( 'songs', $song ) )
		exit( 'song' );
	$text = XT_Request::post_text( 'text', TRUE );
	if ( !is_null( $song ) && !is_null( $text ) )
		exit( 'text' );
	$track = $song?->ID ?? $text;
	$tracks[$i] = $track;
	XT_Tracks::save( $post, $tracks );
	XT::success( XT_Tracks::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_tracks_delete', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !has_category( 'albums', $post ) )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'track' );
	$tracks = XT_Tracks::load( $post );
	if ( !array_key_exists( $i, $tracks ) )
		exit( 'track' );
	XT::nonce_verify( 'xt_tracks_delete', $post->ID, $i );
	unset( $tracks[$i] );
	$tracks = array_values( $tracks );
	XT_Tracks::save( $post, $tracks );
	XT::success( XT_Tracks::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_tracks_up', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !has_category( 'albums', $post ) )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'track' );
	$tracks = XT_Tracks::load( $post );
	if ( !array_key_exists( $i, $tracks ) )
		exit( 'track' );
	XT::nonce_verify( 'xt_tracks_up', $post->ID, $i );
	if ( $i > 0 ) {
		$repl = array_splice( $tracks, $i, 1 );
		array_splice( $tracks, $i - 1, 0, $repl );
	}
	XT_Tracks::save( $post, $tracks );
	XT::success( XT_Tracks::home( $post ) );
} );

add_action( 'wp_ajax_' . 'xt_tracks_down', function(): void {
	$post = XT_Request::get_post();
	if ( $post->post_type !== 'post' )
		exit( 'post' );
	if ( !has_category( 'albums', $post ) )
		exit( 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$i = XT_Request::get_int( 'track' );
	$tracks = XT_Tracks::load( $post );
	if ( !array_key_exists( $i, $tracks ) )
		exit( 'track' );
	XT::nonce_verify( 'xt_tracks_down', $post->ID, $i );
	if ( $i < count( $tracks ) - 1 ) {
		$repl = array_splice( $tracks, $i, 1 );
		array_splice( $tracks, $i + 1, 0, $repl );
	}
	XT_Tracks::save( $post, $tracks );
	XT::success( XT_Tracks::home( $post ) );
} );
