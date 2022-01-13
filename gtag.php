<?php

if ( !defined( 'ABSPATH' ) )
	exit;

// return gtag data attribute list
function xt_gtag_data( string $category, string $action, string $label ): string {
	return sprintf( ' data-xt-gtag-category="%s" data-xt-gtag-action="%s" data-xt-gtag-label="%s"',
		esc_attr( $category ),
		esc_attr( $action ),
		esc_attr( $label ),
	);
}

// return gtag data attribute list for an attachment
function xt_gtag_attachment_data( WP_Post $attachment, string $action, string $suffix = '' ): string {
	$dir = get_attached_file( $attachment->ID );
	$name = array_pop( explode( '/', $dir ) ); # TODO names only in ascii
	return xt_gtag_data( $attachment->post_mime_type . $suffix, $action, $name );
}

// enqueue gtag event handlers
add_action( 'wp_enqueue_scripts', function(): void {
	wp_enqueue_script( 'xt-gtag', XT::url( 'gtag.js' ), [ 'jquery' ], XT::version() );
} );
