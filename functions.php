<?php

if ( !defined( 'ABSPATH' ) )
	exit;

define( 'KGR_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'KGR_URL', trailingslashit( get_stylesheet_directory_uri() ) );

/* enqueue parent theme and child theme stylesheet */
add_action( 'wp_enqueue_scripts', function() {
	# normally, the following should work:
	# wp_enqueue_style( 'total-child', KGR_URL . 'style.css', [ 'total-style' ] );
	wp_enqueue_style( 'total', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'total-child', KGR_URL . 'style.css', [ 'total' ] );
} );

/* remove smoothscroll script */
add_action( 'wp_enqueue_scripts', function() {
	wp_dequeue_script( 'smoothscroll' );
}, 20 );

/* load translations for child theme */
add_action( 'after_setup_theme', function() {
	load_child_theme_textdomain( 'kgr', KGR_DIR . 'languages' );
} );

require_once( KGR_DIR . 'album-post-type.php' );
require_once( KGR_DIR . 'album-content.php' );

require_once( KGR_DIR . 'song-post-type.php' );

require_once( KGR_DIR . 'tracks-metabox.php' );
require_once( KGR_DIR . 'links-metabox.php' );

require_once( KGR_DIR . 'widgets.php' );

/*
 * return the size of a file in a human friendly format
 *
 * @param  $filename string  the filename
 * @return           string  the filesize in a human friendly format
 */
function kgr_filesize( $filename ) {
	$size = filesize( $filename );
	if ( $size < 1024 )
		return sprintf( '%dB', $size );
	$size = $size / 1024;
	if ( $size < 1024 )
		return sprintf( '%0.2fKB', $size );
	$size = $size / 1024;
	if ( $size < 1024 )
		return sprintf( '%0.2fMB', $size );
	$size = $size / 1024;
	return sprintf( '%0.2fGB', $size );
}

/*
 * allow xml file uploading
 *
 * @param  $mimes array  the set of allowed mime types
 * @return        array  the new set of allowed mime types
 */
add_filter( 'upload_mimes', function( $mimes ) {
	$mimes['xml'] = 'application/xml';
	return $mimes;
} );

/* set browser tab color */
add_action( 'wp_head', function() {
	$color = get_theme_mod( 'total_template_color', '#FFC107' );
	echo sprintf( '<meta name="theme-color" content="%s" />', $color ) . "\n";
} );

/* include dashicons in frontend */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'dashicons' );
} );

/* print links related to a post of any type */
function kgr_links() {
	$links = get_post_meta( get_the_ID(), 'kgr-links', TRUE );
	if ( $links === '' )
		return;
	echo sprintf( '<h2>%s</h2>', __( 'Links', 'kgr' ) ) . "\n";
	foreach ( $links as $link ) {
		$url = $link['url'];
		$type = kgr_link_type( $url );
		echo '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
		echo sprintf( '<span class="%s"></span>', esc_attr( 'dashicons ' . kgr_link_type_dashicon( $type ) ) ) . "\n";
		echo sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_html( $link['caption'] ) ) . "\n";
		if ( $type !== '' )
			echo '<span>' . esc_html( '[' . $type . ']' ) . '</span>' . "\n";
		echo '<br />' . "\n";
		echo sprintf( '<i>%s</i>', esc_html( $link['description'] ) ) . "\n";
		echo '</div><!-- .ht-clearfix -->' . "\n";
	}
}

/*
 * return the type of a link
 * currently only simple youtube and vimeo links are handled
 *
 * @param  $url string       the link
 * @return      string|NULL  the type
 */
function kgr_link_type( $url ) {
	$link_types = [ 'youtube', 'vimeo' ];
	foreach ( $link_types as $link_type )
		if ( mb_strpos( $url, $link_type ) !== FALSE )
			return $link_type;
	return NULL;
}

/*
 * return the dashicon class for a specific link type
 *
 * @param  $link_type string|NULL  the link type
 * @return            string       the dashicon class
 */
function kgr_link_type_dashicon( $link_type ) {
	switch ( $link_type ) {
		case 'youtube':
		case 'vimeo':
			return 'dashicons-media-video';
		default:
			return 'dashicons-media-default';
	}
}

function kgr_song_featured_audio() {
	if ( get_post_type() !== 'kgr-song' )
		return;
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
		'post_mime_type' => 'audio/mpeg',
	] );
	foreach( $attachments as $attachment ) {
		if ( $attachment->post_excerpt !== '' )
			continue;
		$url = wp_get_attachment_url( $attachment->ID );
		echo '<div style="margin: 15px 0;">' . "\n";
		echo do_shortcode( sprintf( '[audio mp3="%s"][/audio]', esc_url( $url ) ) );
		echo '</div>' . "\n";
	}
}

function kgr_song_albums( $title = '' ) {
	if ( get_post_type() !== 'kgr-song' )
		return;
	$song = get_the_ID();
	$albums = get_posts( [
		'post_type' => 'kgr-album',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	$self = [];
	foreach ( $albums as $album ) {
		$tracks = get_post_meta( $album->ID, 'kgr-tracks', TRUE );
		if ( $tracks === '' )
			continue;
		$key = array_search( $song, $tracks, TRUE );
		if ( $key === FALSE )
			continue;
		$self[] = sprintf( '<p><a href="%s">%s</a> (%d)</p>',
			esc_url( get_permalink( $album ) ),
			esc_html( get_the_title( $album ) ),
			$key + 1
		) . "\n";
	}
	if ( empty( $self ) )
		return;
	if ( $title !== '' )
		echo sprintf( '<h2>%s</h2>', esc_html( $title ) ) . "\n";
	echo implode( $self );
}

function kgr_song_subjects( $title = '' ) {
	if ( get_post_type() !== 'kgr-song' )
		return;
	$id = get_the_ID();
	$terms = wp_get_post_terms( $id, 'kgr-subject' );
	if ( $title !== '' )
		echo sprintf( '<h2>%s</h2>', esc_html( $title ) ) . "\n";
	echo '<div class="tagcloud">' . "\n";
	foreach ( $terms as $term )
		echo sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) ) . "\n";
	echo '</div>' . "\n";
}

function kgr_song_signatures( $title = '' ) {
	if ( get_post_type() !== 'kgr-song' )
		return;
	$id = get_the_ID();
	$terms = wp_get_post_terms( $id, 'kgr-signature' );
	if ( $title !== '' )
		echo sprintf( '<h2>%s</h2>', esc_html( $title ) ) . "\n";
	echo '<div class="tagcloud">' . "\n";
	foreach ( $terms as $term )
		echo sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) ) . "\n";
	echo '</div>' . "\n";
}

function kgr_song_attachments( $args = [] ) {
	if ( get_post_type() !== 'kgr-song' )
		return;
	if ( array_key_exists( 'title', $args ) )
		echo sprintf( '<h2>%s</h2>', esc_html( $args['title'] ) ) . "\n";
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
	] );
	foreach( $attachments as $attachment ) {
		if ( $attachment->post_excerpt === '' )
			continue;
		$url = wp_get_attachment_url( $attachment->ID );
		$dir = get_attached_file( $attachment->ID );
		$ext = pathinfo( $dir, PATHINFO_EXTENSION );
		switch ( $args['mode'] ) {
			case 'icons':
				echo '<span style="white-space: nowrap; margin-right: 1em;">' . "\n";
				echo sprintf( '<span class="%s"></span>', esc_attr( 'dashicons ' . kgr_mime_type_dashicon( $attachment->post_mime_type ) ) ) . "\n";
				echo sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_html( $attachment->post_excerpt ) ) . "\n";
				echo '</span>' . "\n";
				break;
			default:
				echo '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
				echo kgr_thumbnail( $attachment );
				echo sprintf( '<span class="%s"></span>', esc_attr( 'dashicons ' . kgr_mime_type_dashicon( $attachment->post_mime_type ) ) ) . "\n";
				echo sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_html( $attachment->post_excerpt ) ) . "\n";
				echo '<span style="white-space: nowrap;">' . esc_html( sprintf( '[%s, %s]', $ext, kgr_filesize( $dir ) ) ) . '</span>' . "\n";
				echo '<br />' . "\n";
				echo sprintf( '<i>%s</i>', esc_html( $attachment->post_content ) ) . "\n";
				if ( $attachment->post_mime_type === 'audio/mpeg' )
					echo do_shortcode( sprintf( '[audio mp3="%s"][/audio]', esc_url( $url ) ) );
				echo '</div><!-- .ht-clearfix -->' . "\n";
				break;
		}
	}
}

function kgr_album_tracks_count() {
	if ( get_post_type() !== 'kgr-album' )
		return;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		return;
	$count = 0;
	foreach ( $tracks as $track_id )
		if ( $track_id !== 0 )
			$count++;
	echo '<p>' . esc_html( sprintf( '%d %s', $count, __( 'songs', 'kgr' ) ) ) . '</p>' . "\n";
}

function kgr_thumbnail( WP_Post $attachment ): string {
	$metadata = wp_get_attachment_metadata( $attachment->ID );
	if ( $metadata === FALSE )
		return '';
	if ( !array_key_exists( 'sizes', $metadata ) )
		return '';
	$sizes = $metadata['sizes'];
	if ( !array_key_exists( 'thumbnail', $sizes ) )
		return '';
	$thumbnail = $sizes['thumbnail'];
	$url = trailingslashit( dirname( wp_get_attachment_url( $attachment->ID ) ) );
	return sprintf( '<img src="%s" alt="%s" width="%d" height="%d" style="float: left; margin-right: 15px;" />',
		esc_url( $url . $thumbnail['file'] ),
		esc_html( $thumbnail['file'] ),
		esc_html( $thumbnail['width'] ),
		esc_html( $thumbnail['height'] )
	) . "\n";
}

function kgr_mime_type_dashicon( string $mime_type ): string {
	switch ( $mime_type ) {
		case 'application/pdf':
			return 'dashicons-media-document';
		case 'audio/midi':
		case 'audio/mpeg':
			return 'dashicons-media-audio';
		case 'application/xml':
			return 'dashicons-media-code';
		case 'text/plain':
			return 'dashicons-media-text';
		default:
			return 'dashicons-media-default';
	}
}

add_shortcode( 'kgr-list', function( array $atts ) {
	$html = '';
	switch ( $atts['post_type'] ) {
		case 'attachment':
			# parameter $atts normally defines post_type="attachment", post_mime_type and posts_per_page
			$attachments = get_posts( $atts );
			foreach ( $attachments as $attachment ) {
				$post = get_post( $attachment->post_parent );
				if ( is_null( $post ) )
					continue;
				if ( $attachment->post_excerpt === '' )
					continue;
				$html .= '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
				$html .= kgr_thumbnail( $attachment );
				$html .= sprintf( '<a href="%s">%s</a>', esc_url( get_post_permalink( $post ) ), esc_html( $post->post_title ) ) . "\n";
				$url = wp_get_attachment_url( $attachment->ID );
				$dir = get_attached_file( $attachment->ID );
				$ext = pathinfo( $dir, PATHINFO_EXTENSION );
				$html .= '<br />' . "\n";
				$html .= sprintf( '<span class="%s"></span>', esc_attr( 'dashicons ' . kgr_mime_type_dashicon( $attachment->post_mime_type ) ) ) . "\n";
				$html .= sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $url ), esc_html( $attachment->post_excerpt ) ) . "\n";
				$html .= '<span style="white-space: nowrap;">' . esc_html( sprintf( '[%s, %s]', $ext, kgr_filesize( $dir ) ) ) . '</span>' . "\n";
				$html .= '<br />' . "\n";
				$html .= sprintf( '<i>%s</i>', esc_html( $attachment->post_content ) ) . "\n";
				if ( $attachment->post_mime_type === 'audio/mpeg' )
					$html .= do_shortcode( sprintf( '[audio mp3="%s"][/audio]', esc_url( $url ) ) );
				$html .= '</div><!-- .ht-clearfix -->' . "\n";
			}
			break;
		case 'kgr-song':
			# parameter $atts normally defines post_type="kgr-song" and nopaging
			$posts = get_posts( $atts );
			foreach ( $posts as $post ) {
				$html .= sprintf( '<h3><a href="%s">%s</a></h3>', esc_url( get_permalink( $post ) ), esc_html( $post->post_title ) ) . "\n";
				$html .= sprintf( '<p>%s</p>', esc_html( $post->post_excerpt ) ) . "\n";
			}
			break;
	}
	return $html;
} );
