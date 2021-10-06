<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['settings'] = __( 'Settings', 'xt' );
	return $tab_list;
} );
