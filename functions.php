<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'parent', get_template_directory_uri() . '/style.css' );
} );

define( 'KGR_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'KGR_URL', trailingslashit( get_stylesheet_directory_uri() ) );

require_once( KGR_DIR . 'album-post-type.php' );
require_once( KGR_DIR . 'album-content.php' );
require_once( KGR_DIR . 'album-excerpt.php' );

require_once( KGR_DIR . 'song-post-type.php' );
require_once( KGR_DIR . 'song-content.php' );
require_once( KGR_DIR . 'song-excerpt.php' );

require_once( KGR_DIR . 'tracks-metabox.php' );
require_once( KGR_DIR . 'links-metabox.php' );

require_once( KGR_DIR . 'widgets.php' );

function kgr_filesize( string $filename ): string {
	$size = filesize( $filename );
	if ( $size < 1024 )
		return sprintf( '%dB', $size );
	$size = $size / 1024;
	if ( $size < 1024 )
		return sprintf( '%0.2fKB', $size );
	$size = $size / 1024;
	if ( $size < 1024 )
		return sprintf( '%0.2fMB', $size );
	$size = $size / 1024;
	return sprintf( '%0.2fGB', $size );
}

add_filter( 'upload_mimes', function( array $mimes ): array {
	$mimes['xml'] = 'application/xml';
	return $mimes;
} );

add_filter( 'total_home_sections', function( array $sections ): array {
	$key = array_search( 'cta', $sections, TRUE );
	$value = $sections[ $key ];
	unset( $sections[ $key ] );
	$key = array_search( 'portfolio', $sections, TRUE );
	array_splice( $sections, $key, 0, [ $value ] );
	return $sections;
} );

add_action( 'after_setup_theme', function() {
	load_child_theme_textdomain( 'kgr', KGR_DIR . 'languages' );
} );

add_action( 'wp_head', function() {
	$color = get_theme_mod( 'total_template_color', '#FFC107' );
	echo sprintf( '<meta name="theme-color" content="%s" />', $color ) . "\n";
} );
