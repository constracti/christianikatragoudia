<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'pre_get_posts', function( WP_Query $query ): void {
	global $wp_the_query;
	if ( !$wp_the_query->is_page( 9190 ) )
		return;
	if ( $query->get( 'post_type' ) !== 'post' )
		return;
	if ( $query->get( 'orderby' ) !== 'title' )
		return;
	if ( $query->get( 'order' ) !== 'ASC' )
		return;
	if ( $query->get( 'posts_per_page' ) !== 6 )
		return;
	$query->set( 'nopaging', TRUE );
} );

add_action( 'pre_get_posts', function( WP_Query $query ): void {
	global $wp_the_query;
	if ( !$wp_the_query->is_page( 9190 ) )
		return;
	if ( $query->get( 'post_type' ) !== 'post' )
		return;
	if ( $query->get( 'orderby' ) !== 'rand' && $query->get( 'orderby' ) !== 'title' )
		return;
	if ( $query->get( 'order' ) !== 'ASC' )
		return;
	if ( $query->get( 'posts_per_page' ) !== 4 )
		return;
	$query->set( 'orderby', 'rand' );
	$query->set( 'posts_per_page', 12 );
} );

add_shortcode( 'xt_count', function( array $atts ): string {
	if ( !isset( $atts['category_name'] ) )
		return '';
	if ( !is_string( $atts['category_name'] ) )
		return '';
	$count = count( get_posts( [
		'category_name' => $atts['category_name'],
		'nopaging' => TRUE,
		'fields' => 'ids',
	] ) );
	return strval( $count );
} );
