<?php

/*
 * Plugin Name: Christianika Tragoudia
 * Plugin URI: https://github.com/constracti/christianikatragoudia
 * Description: Customization plugin of Christianika Tragoudia website.
 * Version: 1.12
 * Requires PHP: 8.0
 * Author: constracti
 * Author URI: https://github.com/constracti
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: xt
 * Domain Path: /languages
 */

if ( !defined( 'ABSPATH' ) )
	exit;

final class XT {

	// constants

	public static function dir( string $dir ): string {
		return plugin_dir_path( __FILE__ ) . $dir;
	}

	public static function url( string $url ): string {
		return plugin_dir_url( __FILE__ ) . $url;
	}

	// plugin version

	public static function version(): string {
		$plugin_data = get_plugin_data( __FILE__ );
		return $plugin_data['Version'];
	}

	// return json string

	public static function success( string $html ): void {
		header( 'content-type: application/json' );
		exit( json_encode( [
			'html' => $html,
		] ) );
	}

	// build attribute list

	public static function atts( array $atts ): string {
		$return = '';
		foreach ( $atts as $prop => $val ) {
			$return .= sprintf( ' %s="%s"', $prop, $val );
		}
		return $return;
	}

	// nonce

	private static function nonce_action( string $action, string ...$args ): string {
		foreach ( $args as $arg )
			$action .= '_' . $arg;
		return $action;
	}

	public static function nonce_create( string $action, string ...$args ): string {
		return wp_create_nonce( self::nonce_action( $action, ...$args ) );
	}

	public static function nonce_verify( string $action, string ...$args ): void {
		$nonce = XT_Request::get_str( 'nonce' );
		if ( !wp_verify_nonce( $nonce, self::nonce_action( $action, ...$args ) ) )
			exit( 'nonce' );
	}
}

function xt_first_line( string $str ): string {
	return explode( "\r\n", $str, 2 )[0];
}

/**
 * Require php files.
 */
$files = glob( XT::dir( '*.php' ) );
foreach ( $files as $file ) {
	if ( $file !== __FILE__ )
		require_once( $file );
}

/**
 * Load plugin translations.
 */
add_action( 'init', function(): void {
	load_plugin_textdomain( 'xt' );
} );

/**
 * Add options page.
 */
add_action( 'admin_menu', function(): void {
	$page_title = esc_html__( 'Christianika Tragoudia', 'xt' );
	$menu_title = esc_html__( 'Christianika Tragoudia', 'xt' );
	$capability = 'manage_options';
	$menu_slug = 'xt';
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, function(): void {
		$tab_list = apply_filters( 'xt_tab_list', [] );
		if ( array_key_exists( 'tab', $_GET ) && array_key_exists( $_GET['tab'], $tab_list ) )
			$tab_curr = $_GET['tab'];
		elseif ( !empty( $tab_list ) )
			$tab_curr = array_key_first( $tab_list );
		else
			$tab_curr = NULL;
?>
<div class="wrap">
	<h1><?= esc_html_e( 'Christianika Tragoudia', 'xt' ) ?></h1>
	<h2 class="nav-tab-wrapper">
<?php
		foreach ( $tab_list as $tab_slug => $tab_name ) {
			$class = [];
			$class[] = 'nav-tab';
			if ( $tab_slug === $tab_curr )
				$class[] = 'nav-tab-active';
				$class = implode( ' ', $class );
				$href = menu_page_url( 'xt', FALSE ) . '&tab=' . $tab_slug;
?>
		<a class="<?= $class ?>" href="<?= $href ?>"><?= esc_html( $tab_name ) ?></a>
<?php
		}
?>
	</h2>
<?php
	do_action( 'xt_tab_html_' . $tab_curr );
?>
</div>
<?php
	} );
} );

/**
 * Display a link to plugin settings.
 */
add_filter( 'plugin_action_links', function( array $actions, string $plugin_file ): array {
	if ( $plugin_file !== basename( __DIR__ ) . DIRECTORY_SEPARATOR . basename( __FILE__ ) )
		return $actions;
	$href = esc_url_raw( menu_page_url( 'xt', FALSE ) );
	$html = esc_html__( 'Settings', 'xt' );
	$actions['settings'] = sprintf( '<a href="%s">%s</a>', $href, $html );
	return $actions;
}, 10, 2 );

/**
 * Allow xml file uploading.
 */
add_filter( 'upload_mimes', function( array $mimes ): array {
	$mimes['xml'] = 'text/xml';
	return $mimes;
} );

/**
 * Include the chords script for show, hide and transpose functionality.
 */
add_action( 'wp_enqueue_scripts', function(): void {
	if ( !is_singular() || !has_category( 'songs' ) )
		return;
	wp_enqueue_script( 'xt-chords', XT::url( 'chords/chords.js' ), [ 'jquery' ], XT::version() );
} );
add_action( 'wp_head', function(): void {
	if ( !is_singular() || !has_category( 'songs' ) )
		return;
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fira+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
.chords table th,
.chords table td {
	text-align: left;
	padding: 4px 4px 4px 0px;
}

.chords button {
	margin-bottom: 4px;
}

.chords .together {
	display: inline-block;
}

.chords .chords-copy {
	position: relative;
	display: inline-block;
}
.chords .chords-copied {
	width: 120px;
	background-color: rgba(0,0,0,0.75);
	color: White;
	text-align: center;
	border-radius: 5px;
	padding: 5px;
	position: absolute;
	bottom: calc(100% + 10px);
	left: 50%;
	margin-left: -60px;
}
.chords .chords-copied::after {
	content: "";
	position: absolute;
	top: 100%;
	left: 50%;
	margin-left: -5px;
	border-width: 5px;
	border-style: solid;
	border-color: rgba(0,0,0,0.75) transparent transparent transparent;
}

.chords .chords-text {
	font-family: 'Fira Mono', monospace;
	overflow-x: hidden;
}
</style>
<?php
} );

/**
 * Restore open graph title meta.
 */
add_filter( 'open_graph_protocol_meta', function( string $content, string $property ): string {
	if ( $property !== 'og:title' )
		return $content;
	return wp_get_document_title();
}, 10, 2 );

/**
 * Set nopaging in selected queries.
 */
add_action( 'pre_get_posts', function( WP_Query $query ): void {
	if ( is_admin() )
		return;
	if ( $query->get( 'posts_per_page' ) !== 17 )
		return;
	$query->set( 'nopaging', TRUE );
} );

/**
 * Simplify excerpt in main index query.
 */
add_filter( 'the_posts', function( array $posts, WP_Query $query ): array {
	if ( is_admin() )
		return $posts;
	global $wp_query;
	if ( !$wp_query->is_page( 'index' ) )
		return $posts;
	if ( !$query->is_category( 'songs' ) )
		return $posts;
	if ( $query->query_vars['nopaging'] !== TRUE )
		return $posts;
	foreach ( $posts as $post )
		$post->post_excerpt = xt_first_line( $post->post_excerpt );
	return $posts;
}, 10, 2 );

/**
 * Choose some posts at random in selected queries.
 */
add_action( 'pre_get_posts', function( WP_Query $query ): void {
	if ( is_admin() )
		return;
	if ( $query->get( 'posts_per_page' ) !== 13 )
		return;
	$query->set( 'posts_per_page', 3 );
	$query->set( 'orderby', 'rand' );
} );

/**
 * Simplify excerpt in random songs sidebar query.
 */
add_filter( 'the_posts', function( array $posts, WP_Query $query ): array {
	if ( is_admin() )
		return $posts;
	if ( !$query->is_category( 'songs' ) )
		return $posts;
	if ( $query->query_vars['posts_per_page'] !== 3 )
		return $posts;
	if ( $query->query_vars['orderby'] !== 'rand' )
		return $posts;
	foreach ( $posts as $post )
		$post->post_excerpt = xt_first_line( $post->post_excerpt );
	return $posts;
}, 10, 2 );
