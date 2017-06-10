<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'init', function() {
	register_post_type( 'kgr-album', [
		'label' => __( 'Albums', 'kgr' ),
		'public' => TRUE,
		'menu_icon' => 'dashicons-video-alt3',
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
		# TODO add categories and tags to support artists
		'has_archive' => TRUE,
		'rewrite' => [
			'slug' => 'albums',
		],
	] );
} );
