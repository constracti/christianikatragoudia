<?php

/*
 * Plugin Name: Christianika Tragoudia
 * Plugin URI: https://github.com/constracti/christianikatragoudia
 * Description: Customization plugin of Christianika Tragoudia website.
 * Author: constracti
 * Version: 1.0
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xt
 */

if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * define plugin constants
 */
define( 'XT_DIR', plugin_dir_path( __FILE__ ) );
define( 'XT_URL', plugin_dir_url( __FILE__ ) );

/**
 * require php files
 */
$files = glob( XT_DIR . '*.php' );
foreach ( $files as $file ) {
	if ( $file !== __FILE__ )
		require_once( $file );
}

/**
 * return plugin version
 */
function xt_version(): string {
	$plugin_data = get_plugin_data( __FILE__ );
	return $plugin_data['Version'];
}

/**
 * load plugin translations
 */
add_action( 'init', function(): void {
	load_plugin_textdomain( 'xt', FALSE, basename( __DIR__ ) . DIRECTORY_SEPARATOR . 'languages' );
} );

/**
 * add options page
 */
add_action( 'admin_menu', function() {
	$page_title = esc_html__( 'Christianika Tragoudia', 'xt' );
	$menu_title = esc_html__( 'Christianika Tragoudia', 'xt' );
	$capability = 'manage_options';
	$menu_slug = 'xt';
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, function() {
		$tab_curr = array_key_exists( 'tab', $_GET ) ? $_GET['tab'] : 'settings';
?>
<div class="wrap">
	<h1><?= esc_html_e( 'Christianika Tragoudia', 'xt' ) ?></h1>
	<h2 class="nav-tab-wrapper">
<?php
		foreach ( apply_filters( 'xt_tab_list', [] ) as $tab_slug => $tab_name ) {
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
 * display a link to plugin settings
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
 * allow xml file uploading
 */
add_filter( 'upload_mimes', function( array $mimes ): array {
	$mimes['xml'] = 'text/xml';
	return $mimes;
} );

/**
 * include the chords script for show, hide and transpose functionality
 */
add_action( 'wp_enqueue_scripts', function(): void {
	if ( !is_singular() || !has_category( 'songs' ) )
		return;
	wp_enqueue_script( 'xt-chords', XT_URL . 'chords/chords.js', [ 'jquery', ], xt_version() );
} );

/**
 * restore open graph title meta
 */
add_filter( 'open_graph_protocol_meta', function( string $content, string $property ): string {
	if ( $property !== 'og:title' )
		return $content;
	return wp_get_document_title();
}, 10, 2 );

/**
 * set nopaging in selected queries
 */
add_action( 'pre_get_posts', function( WP_Query $query ): void {
	if ( is_admin() )
		return;
	if ( $query->get( 'posts_per_page' ) === 17 )
		$query->set( 'nopaging', TRUE );
} );
