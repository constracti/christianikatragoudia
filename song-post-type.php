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
