<?php

if ( !defined( 'ABSPATH' ) )
	exit;

return;

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['replace_chords'] = __( 'Replace Chords', 'xt' );
	return $tab_list;
} );

add_action( 'xt_tab_html_replace_chords', function(): void {
	echo sprintf( '<h3>%s</h3>', esc_html__( 'Replace Chords', 'xt' ) ) . "\n";
	$dir = XT::dir( 'dir-replace-chords' );
	$replace = isset( $_GET['action'] ) && $_GET['action'] === 'replace';
	$name_list = array_values( array_filter( scandir( $dir ), function( string $file_name ): bool {
		return str_ends_with( $file_name, '.chords.txt' );
	} ) );
	foreach ( $name_list as $name ) {
		echo '<div>' . "\n";
		$src = $dir . '/' . $name;
		$dst = wp_get_upload_dir()['basedir'] . '/' . $name;
		echo '<label>' . "\n";
		echo '<span>src</span>' . "\n";
		echo sprintf( '<input class="large-text code" value="%s">', esc_attr( $src ) ) . "\n";
		echo '</label>' . "\n";
		echo '<label>' . "\n";
		echo '<span>dst</span>' . "\n";
		echo sprintf( '<input class="large-text code" value="%s">', esc_attr( $dst ) ) . "\n";
		echo '</label>' . "\n";
		$atts = get_posts( [
			'post_type' => 'attachment',
			'nopaging' => TRUE,
			'meta_key' => '_wp_attached_file',
			'meta_value' => $name,
		] );
		if ( count( $atts ) !== 1 )
			exit( 'count atts' );
		$att = $atts[0];
		echo '<pre>' . "\n";
		echo esc_html( print_r( $att, TRUE ) );
		echo '</pre>' . "\n";
		if ( $replace )
			wp_delete_attachment( $att->ID, TRUE );
		if ( $replace )
			rename( $src, $dst );
		$parent = $att->post_parent;
		$new = [
			'post_title' => $att->post_title,
			'post_content' => $att->post_content,
			'post_mime_type' => $att->post_mime_type,
		];
		if ( $replace )
			wp_insert_attachment( $new, $dst, $parent );
		echo '</div>' . "\n";
	}
	if ( !$replace )
		echo sprintf( '<a href="?page=xt&tab=replace_chords&action=replace" class="button button-primary">%s</a>', esc_html__( 'Replace Chords', 'xt' ) ) . "\n";
} );
