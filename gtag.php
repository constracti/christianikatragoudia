<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Return gtag data attribute list.
 */
function xt_gtag_data( string $category, string $action, string $label ): string {
	return sprintf( ' data-xt-gtag-category="%s" data-xt-gtag-action="%s" data-xt-gtag-label="%s"',
		esc_attr( $category ),
		esc_attr( $action ),
		esc_attr( $label ),
	);
}

/**
 * Return gtag data attribute list for an attachment.
 */
function xt_gtag_attachment_data( WP_Post $attachment, string $action, string $suffix = '' ): string {
	$dir = get_attached_file( $attachment->ID );
	$name = array_pop( explode( '/', $dir ) );
	return xt_gtag_data( $attachment->post_mime_type . $suffix, $action, $name );
}

/**
 * Enqueue gtag event handlers.
 */
add_action( 'wp_enqueue_scripts', function(): void {
	wp_enqueue_script( 'xt-gtag', XT::url( 'gtag.js' ), [ 'jquery' ], XT::version() );
	// https://developer.wordpress.org/reference/functions/wp_get_document_title/
	$title = wp_get_document_title();
	if ( !is_front_page() ) {
		$sep = apply_filters( 'document_title_separator', '-' );
		$sep = apply_filters( 'document_title', $sep );
		$title = explode( ' ' . $sep . ' ', $title );
		$title = $title[0];
	}
	wp_localize_script( 'xt-gtag', 'xt_gtag', [
		'title' => $title,
	] );
} );
