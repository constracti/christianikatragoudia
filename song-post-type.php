<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'init', function() {
	register_post_type( 'kgr-song', [
		'label' => __( 'Songs', 'kgr' ),
		'public' => TRUE,
		'menu_icon' => 'dashicons-format-audio',
		'supports' => [
			'title',
			'editor',
			'author',
			'thumbnail',
			'excerpt',
			'custom-fields',
			'comments',
			'revisions',
		],
		'taxonomies' => [
			'kgr-subject',
			'kgr-signature',
		],
		'has_archive' => TRUE,
		'rewrite' => [
			'slug' => 'songs',
		],
	] );
	register_taxonomy( 'kgr-subject', 'kgr-song', [
		'label' => __( 'Subjects', 'kgr' ),
		'public' => TRUE,
		'show_admin_column' => TRUE,
		'rewrite' => [
			'slug' => 'subjects',
		],
		'sort' => FALSE,
	] );
	register_taxonomy( 'kgr-signature', 'kgr-song', [
		'label' => __( 'Signatures', 'kgr' ),
		'public' => TRUE,
		'show_admin_column' => TRUE,
		'rewrite' => [
			'slug' => 'signatures',
		],
		'sort' => FALSE,
	] );
} );

add_action( 'init', function() {
	add_rewrite_rule( 'songs/([0-9]{4})/?$', 'index.php?post_type=kgr-song&year=$matches[1]', 'top' );
	add_rewrite_rule( 'songs/([0-9]{4})/page/([0-9]{1,})/?$', 'index.php?post_type=kgr-song&year=$matches[1]&paged=$matches[2]', 'top' );
	add_rewrite_rule( 'songs/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?post_type=kgr-song&year=$matches[1]&monthnum=$matches[2]', 'top' );
	add_rewrite_rule( 'songs/([0-9]{4})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?post_type=kgr-song&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]', 'top' );
	add_rewrite_rule( 'songs/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?post_type=kgr-song&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]', 'top' );
	add_rewrite_rule( 'songs/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/([0-9]{1,})/?$', 'index.php?post_type=kgr-song&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]', 'top' );
} );

add_filter( 'get_archives_link', 'kgr_filter_get_archives_link', 10, 6 );
function kgr_filter_get_archives_link( $link_html, $url, $text, $format, $before, $after ) {
	if ( preg_match( '#(.*)([0-9]{4}/(?:[0-9]{2}/(?:[0-9]{2}/)?)?)(\?.*)#', $url, $matches ) !== 1 )
		return $link_html;
	$matches[3] = substr( $matches[3], 1 );
	$matches[3] = explode( '&', $matches[3] );
	$pos = array_search( 'post_type=kgr-song', $matches[3], TRUE );
	if ( $pos === FALSE )
		return $link_html;
	array_splice( $matches[3], $pos );
	$matches[2] = 'songs/' . $matches[2];
	$matches[3] = !empty( $matches[3] ) ? ( '?' . implode( '&', $matches[3] ) ) : '';
	$url = $matches[1] . $matches[2] . $matches[3];
	remove_filter( 'get_archives_link', 'kgr_filter_get_archives_link', 10 );
	$link_html = get_archives_link( $url, $text, $format, $before, $after );
	add_filter( 'get_archives_link', 'kgr_filter_get_archives_link', 10, 6 );
	return $link_html;
}
