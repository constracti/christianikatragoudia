<?php

if ( !defined( 'ABSPATH' ) )
	exit;

function xt_statistics_guest(): void {
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
	] );
	$songs_without_image = 0;
?>
<h3>Songs without Image</h3>
<table>
	<tbody>
<?php
	foreach ( $songs as $song ) {
		if ( has_post_thumbnail( $song ) )
			continue;
		$songs_without_image++;
		$href = add_query_arg( 'p', $song->ID, site_url() );
?>
		<tr>
			<td><a href="<?= $href ?>"><?= esc_html( $song->post_title ) ?></a></td>
			<td><?= esc_html( $song->post_excerpt ) ?></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<p><?= $songs_without_image ?> / <?= count( $songs ) ?></p>
<hr>
<?php
	$songs = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
	] );
	$songs_with_image = 0;
	$songs_without_chords = 0;
?>
<h3>Songs with Image but without Chords</h3>
<table>
	<tbody>
<?php
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
		if ( !empty( $atts ) )
			continue;
		$songs_without_chords++;
		$href = add_query_arg( 'p', $song->ID, site_url() );
?>
		<tr>
			<td><a href="<?= $href ?>"><?= esc_html( $song->post_title ) ?></a></td>
			<td><?= esc_html( $song->post_excerpt ) ?></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<p><?= $songs_without_chords ?> / <?= $songs_with_image ?></p>
<hr>
<?php
}

add_action( 'wp_ajax_nopriv_kgr_stats', function(): void {
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
tr:nth-child( odd ) {
	background-color: lightgray;
}
td {
	padding: 4px 8px;
}
</style>
<?php
	xt_statistics_guest();
	exit;
} );

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['statistics'] = __( 'Statistics', 'xt' );
	return $tab_list;
} );

add_action( 'xt_tab_html_statistics', 'xt_statistics_guest' );

add_action( 'xt_tab_html_statistics', function(): void {
	$mp3s_with_meta = 0;
	$mp3s = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'audio/mpeg',
		'nopaging' => TRUE,
	] );
?>
<h3>MP3s with Tags</h3>
<table>
	<tbody>
<?php
	foreach ( $mp3s as $mp3 ) {
		$meta = wp_get_attachment_metadata( $mp3->ID );
		if ( is_array( $meta ) && array_key_exists( 'title', $meta ) ) {
			$href = add_query_arg( 'item', $mp3->ID, admin_url( 'upload.php' ) );
?>
		<tr>
			<td><a href="<?= $href ?>"><?= esc_html( $mp3->post_title ) ?></a></td>
			<td><?= esc_html( $mp3->post_content ) ?></td>
		</tr>
<?php
			$mp3s_with_meta++;
		}
	}
?>
	</tbody>
</table>
<p><?= $mp3s_with_meta ?> / <?= count( $mp3s ) ?></p>
<hr>
<?php
} );

add_action( 'xt_tab_html_statistics', function(): void {
	$midis = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'audio/midi',
		'nopaging' => TRUE,
	] );
?>
<h3>Midi Files</h3>
<table>
	<tbody>
<?php
	foreach ( $midis as $midi ) {
		$href = add_query_arg( 'item', $midi->ID, admin_url( 'upload.php' ) );
?>
		<tr>
			<td><a href="<?= $href ?>"><?= esc_html( $midi->post_title ) ?></a></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<p><?= count( $midis ) ?></p>
<hr>
<?php
} );
