<?php

if ( !defined( 'ABSPATH' ) )
	exit;

function kgr_stats(): void {
?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
body {
	max-width: 1080px;
	margin: auto;
}
table {
	border-collapse: collapse;
	width: 100%;
}
tr:nth-child(odd) {
	background-color: lightgray;
}
td {
	padding: 4px 8px;
}
</style>
<?php
	echo '<h3>Songs without Image</h3>' . "\n";
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
	] );
	$songs_without_image = 0;
	echo '<table>' . "\n";
	foreach ( $songs as $song ) {
		if ( !has_post_thumbnail( $song ) ) {
			echo sprintf( '<tr><td><a href="%s">%s</a></td><td>%s</td></tr>', add_query_arg( 'p', $song->ID, site_url() ), $song->post_title, $song->post_excerpt ) . "\n";
			$songs_without_image++;
		}
	}
	echo '</table>' . "\n";
	echo sprintf( '<p>%d / %d</p>', $songs_without_image, count( $songs ) ) . "\n";
	echo '<hr>' . "\n";

	echo '<h3>Songs with Image but without Chords</h3>' . "\n";
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
	] );
	$songs_with_image = 0;
	$songs_without_chords = 0;
	echo '<table>' . "\n";
	foreach ( $songs as $song ) {
		if ( !has_post_thumbnail( $song ) )
			continue;
		$songs_with_image++;
		$atts = get_posts( [
			'post_parent' => $song->ID,
			's' => 'chords',
			'post_type' => 'attachment',
			'post_mime_type' => 'text/plain',
			'nopaging' => TRUE,
		] );
		if ( empty( $atts ) ) {
			echo sprintf( '<tr><td><a href="%s">%s</a></td><td>%s</td></tr>',
				add_query_arg( 'p', $song->ID, site_url() ),
				$song->post_title,
				$song->post_excerpt,
			) . "\n";
			$songs_without_chords++;
		}
	}
	echo '</table>' . "\n";
	echo sprintf( '<p>%d / %d</p>', $songs_without_chords, $songs_with_image ) . "\n";
	echo '<hr>' . "\n";

	if ( !current_user_can( 'administrator' ) )
		exit;

	echo '<h3>MP3s with Tags</h3>' . "\n";
	$mp3s_with_meta = 0;
	$mp3s = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'audio/mpeg',
		'nopaging' => TRUE,
	] );
	echo '<table>' . "\n";
	foreach ( $mp3s as $mp3 ) {
		$meta = wp_get_attachment_metadata( $mp3->ID );
		if ( is_array( $meta ) && array_key_exists( 'title', $meta ) ) {
			echo sprintf( '<tr><td><a href="%s">%s</a></td><td>%s</td></tr>',
				add_query_arg( 'item', $mp3->ID, admin_url( 'upload.php' ) ),
				esc_html( $mp3->post_title ),
				esc_html( $mp3->post_content ),
			) . "\n";
			$mp3s_with_meta++;
		}
	}
	echo '</table>' . "\n";
	printf( '<p>%d / %d</p>', $mp3s_with_meta, count( $mp3s ) );
	echo '<hr>' . "\n";

	echo '<h3>MP3s with wrong BitRate</h3>' . "\n";
	$mp3s_with_bitrate = 0;
	$mp3s = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'audio/mpeg',
		'nopaging' => TRUE,
	] );
	echo '<table>' . "\n";
	foreach ( $mp3s as $mp3 ) {
		$mp3_path = get_attached_file( $mp3->ID );
		$mp3_info = wp_read_audio_metadata( $mp3_path );
		$bitrate = intval( round( $mp3_info['bitrate'] / 1000 ) );
		if ( $bitrate !== 128 ) {
			echo sprintf( '<tr><td><a href="%s">%s</a></td><td>%s</td><td>%d</td></tr>',
				add_query_arg( 'item', $mp3->ID, admin_url( 'upload.php' ) ),
				esc_html( $mp3->post_title ), esc_html( $mp3->post_content ),
				$bitrate,
			);
			$mp3s_with_bitrate++;
		}
	}
	echo '</table>' . "\n";
	echo sprintf( '<p>%d / %d</p>', $mp3s_with_bitrate, count( $mp3s ) ) . "\n";
	echo '<hr>' . "\n";

	echo '<h3>Midi Files</h3>' . "\n";
	$midis = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'audio/midi',
		'nopaging' => TRUE,
	] );
	echo '<table>' . "\n";
	foreach ( $midis as $midi ) {
		echo sprintf( '<tr><td><a href="%s">%s</a></td></tr>',
			add_query_arg( 'item', $midi->ID, admin_url( 'upload.php' ) ),
			esc_html( $midi->post_title ),
		) . "\n";
	}
	echo '</table>' . "\n";
	echo sprintf( '<p>%d</p>', count( $midis ) ) . "\n";
	echo '<hr>' . "\n";

	exit;
}

add_action( 'wp_ajax_kgr_stats', 'kgr_stats' );
add_action( 'wp_ajax_nopriv_kgr_stats', 'kgr_stats' );
