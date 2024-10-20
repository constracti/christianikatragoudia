<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_shortcode( 'xt_album_null', function(): string {
	$albums = get_posts( [
		'category_name' => 'albums',
		'nopaging' => TRUE,
	] );
	$songs = [];
	foreach ( $albums as $album ) {
		$tracks = XT_Tracks::load( $album );
		foreach ( $tracks as $track ) {
			if ( is_int( $track ) && $track > 0 )
				$songs[] = $track;
		}
	}
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'post__not_in' => $songs,
		'orderby' => 'title',
		'order' => 'ASC',
	] );
	ob_start();
	foreach ( $songs as $song ) {
		echo '<div class="clearfix" style="margin-bottom: 15px;">' . "\n";
		if ( has_post_thumbnail( $song ) )
			xt_thumbnail( get_post_thumbnail_id( $song ) );
		echo sprintf( '<a href="%s">%s</a>', esc_url_raw( get_the_permalink( $song ) ), esc_html( get_the_title( $song ) ) ) . "\n";
		echo '<br />' . "\n";
		echo sprintf( '<i>%s</i>', esc_html( xt_first_line( get_the_excerpt( $song ) ) ) ) . "\n";
		echo '</div>' . "\n";
	}
	return ob_get_clean();
} );
