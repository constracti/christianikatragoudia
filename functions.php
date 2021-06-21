<?php

if ( !defined( 'ABSPATH' ) )
	exit;

define( 'KGR_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'KGR_URL', trailingslashit( get_stylesheet_directory_uri() ) );

// return currrent theme version
function kgr_version( bool $parent = FALSE ): string {
	$theme = wp_get_theme();
	if ( $parent )
		$theme = $theme->parent();
	return $theme->get( 'Version' );
}

// enqueue parent theme and child theme stylesheet
add_action( 'wp_enqueue_scripts', function(): void {
	# normally, the following should work:
	# wp_enqueue_style( 'total-child', KGR_URL . 'style.css', [ 'total-style' ] );
	wp_enqueue_style( 'total', get_template_directory_uri() . '/style.css', [], kgr_version( TRUE ) );
	wp_enqueue_style( 'total-child', KGR_URL . 'style.css', [ 'total' ], kgr_version() );
} );

// load translations for child theme
add_action( 'after_setup_theme', function(): void {
	load_child_theme_textdomain( 'kgr', KGR_DIR . 'languages' );
} );

require_once( KGR_DIR . 'tracks.php' );
require_once( KGR_DIR . 'links.php' );
require_once( KGR_DIR . 'home.php' );

require_once( KGR_DIR . 'widgets.php' );

// allow xml file uploading
add_filter( 'upload_mimes', function( array $mimes ): array {
	$mimes['xml'] = 'text/xml';
	return $mimes;
} );

// set browser tab color
add_action( 'wp_head', function(): void {
	$color = get_theme_mod( 'total_template_color', '#FFC107' );
	echo sprintf( '<meta name="theme-color" content="%s" />', $color ) . "\n";
} );

// remove page_for_posts from archive breadcrumbs
add_filter( 'breadcrumb_trail_items', function( array $items, array $args ): array {
	if ( !is_archive() )
		return $items;
	if ( !is_category() && !is_tag() && !is_tax() )
		return $items;
	array_splice( $items, 1, 1 );
	return $items;
}, 10, 2 );

// return gtag data attribute list
function kgr_gtag_data( string $category, string $action, string $label ): string {
	return sprintf( ' data-kgr-gtag-category="%s" data-kgr-gtag-action="%s" data-kgr-gtag-label="%s"', esc_attr( $category ), esc_attr( $action ), esc_attr( $label ) );
}

// return gtag data attribute list for an attachment
function kgr_gtag_attachment_data( WP_Post $attachment, string $action, string $suffix = ''): string {
	$dir = get_attached_file( $attachment->ID );
	$name = array_pop( explode( '/', $dir ) ); # TODO names only in ascii
	return kgr_gtag_data( $attachment->post_mime_type . $suffix, $action, $name );
}

// enqueue gtag event handlers
add_action( 'wp_enqueue_scripts', function(): void {
	wp_enqueue_script( 'kgr-gtag', KGR_URL . 'gtag.js', [ 'jquery' ], kgr_version() );
} );

// print links related to a post of any type
function kgr_links(): void {
	$links = get_post_meta( get_the_ID(), 'kgr-links', TRUE );
	if ( $links === '' )
		return;
	echo sprintf( '<h2>%s</h2>', __( 'Links', 'kgr' ) ) . "\n";
	foreach ( $links as $link ) {
		$url = $link['url'];
		$host = parse_url( $url, PHP_URL_HOST );
		echo '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
		echo sprintf( '<span class="%s"></span>', esc_attr( kgr_link_icon( $host ) ) ) . "\n";
		echo sprintf( '<a href="%s" target="_blank" class="kgr-gtag"%s>%s</a>', esc_url_raw( $url ), kgr_gtag_data( 'link', 'click', $url ), esc_html( $link['caption'] ) ) . "\n";
		echo '<span>' . esc_html( '[' . $host . ']' ) . '</span>' . "\n";
		echo '<br />' . "\n";
		echo sprintf( '<i>%s</i>', esc_html( $link['description'] ) ) . "\n";
		echo '</div><!-- .ht-clearfix -->' . "\n";
	}
}

// return the icon class for a specific link host
function kgr_link_icon( string $host ): string {
	switch ( $host ) {
		case 'www.youtube.com':
			return 'fa fa-fw fa-youtube';
		case 'vimeo.com':
			return 'fa fa-fw fa-vimeo';
		default:
			return 'fa fa-fw fa-external-link';
	}
}

// echo featured audio shortcode
function kgr_song_featured_audio( bool $full = FALSE ): void {
	if ( !has_category( 'songs' ) )
		return;
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
		'post_mime_type' => 'audio/mpeg',
	] );
	foreach( $attachments as $attachment ) {
		if ( mb_strpos( $attachment->post_content, 'featured' ) !== 0 )
			continue;
		$url = wp_get_attachment_url( $attachment->ID );
		$dir = get_attached_file( $attachment->ID );
		$ext = pathinfo( $dir, PATHINFO_EXTENSION );
		echo sprintf( '<div style="margin: 15px 0;" class="kgr-gtag-audio"%s>', kgr_gtag_attachment_data( $attachment, 'play', ' featured' ) ) . "\n";
		if ( $full ) {
			echo sprintf( '<a href="%s" target="_blank">', esc_url_raw( $url ) ) . "\n";
			echo '<span>' . esc_html( sprintf( '[%s, %s]', $ext, size_format( filesize( $dir ), 2 ) ) ) . '</span>' . "\n";
			echo '</a>' . "\n";
			echo '<br />' . "\n";
			$html = mb_ereg_replace( 'featured\,?\s*', '', $attachment->post_content );
			if ( $html === FALSE )
				$html = $attachment->post_content;
			echo sprintf( '<i>%s</i>', esc_html( $html ) ) . "\n";
			echo '<br />' . "\n";
		}
		echo wp_audio_shortcode( [
			'src' => esc_url_raw( $url ),
		] );
		echo '</div>' . "\n";
	}
}

// output a list of albums related to the current song
function kgr_albums( string $title = '' ): void {
	if ( !has_category( 'songs' ) )
		return;
	$song = get_the_ID();
	$albums = get_posts( [
		'category_name' => 'albums',
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
			esc_url_raw( get_permalink( $album ) ),
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

// display post tags using the cool tag cloud shortcode
function kgr_tags(): void {
	$tags = get_the_tags();
	if ( empty( $tags ) )
		return;
	$tags = array_column( $tags, 'term_id' );
	$tags = implode( ',', $tags );
	echo do_shortcode( '[cool_tag_cloud smallest="12" largest="12" number="0" include="' . $tags . '"]' );
}

// echo the attachment section
function kgr_song_attachments( array $args = [] ): void {
	if ( !has_category( 'songs' ) )
		return;
	if ( array_key_exists( 'title', $args ) )
		echo sprintf( '<h2>%s</h2>', esc_html( $args['title'] ) ) . "\n";
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
	] );
	foreach( $attachments as $attachment ) {
		if ( mb_strpos( $attachment->post_content, 'featured' ) === 0 )
			continue;
		if ( $attachment->post_mime_type === 'image/jpeg' )
			continue;
		$url = wp_get_attachment_url( $attachment->ID );
		$dir = get_attached_file( $attachment->ID );
		$ext = pathinfo( $dir, PATHINFO_EXTENSION );
		switch ( $args['mode'] ) {
			case 'icons':
				echo '<span style="white-space: nowrap; margin-right: 1em;">' . "\n";
				echo sprintf( '<span class="%s"></span>', esc_attr( kgr_mime_type_icon( $attachment->post_mime_type ) ) ) . "\n";
				echo sprintf( '<a href="%s" target="_blank" class="kgr-gtag"%s>[%s]</a>', esc_url_raw( $url ), kgr_gtag_attachment_data( $attachment, 'download' ), esc_html( $ext ) ) . "\n";
				echo '</span>' . "\n";
				break;
			default:
				echo '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
				echo kgr_thumbnail( $attachment );
				echo sprintf( '<span class="%s"></span>', esc_attr( kgr_mime_type_icon( $attachment->post_mime_type ) ) ) . "\n";
				echo sprintf( '<a href="%s" target="_blank" class="kgr-gtag"%s>', esc_url_raw( $url ), kgr_gtag_attachment_data( $attachment, 'download' ) ) . "\n";
				if ( !empty( $attachment->post_excerpt ) )
					echo sprintf( '<span>%s</span>', esc_html( $attachment->post_excerpt ) ) . "\n";
				echo '<span>' . esc_html( sprintf( '[%s, %s]', $ext, size_format( filesize( $dir ), 2 ) ) ) . '</span>' . "\n";
				echo '</a>' . "\n";
				echo '<br />' . "\n";
				echo sprintf( '<i>%s</i>', esc_html( $attachment->post_content ) ) . "\n";
				if ( $attachment->post_mime_type === 'audio/mpeg' ) {
					echo sprintf( '<div class="kgr-gtag-audio"%s>', kgr_gtag_attachment_data( $attachment, 'play' ) ) . "\n";
					echo wp_audio_shortcode( [
						'src' => esc_url_raw( $url ),
					] );
					echo '</div>';
				}
				if ( $attachment->post_mime_type === 'text/plain' && mb_ereg_match( '^.*\.chords$', $attachment->post_title ) )
					kgr_song_attachments_chords( $attachment );
				echo '</div><!-- .ht-clearfix -->' . "\n";
				break;
		}
	}
}

// echo controls for the chords attachments
function kgr_song_attachments_chords( WP_Post $attachment ): void {
	$url = wp_get_attachment_url( $attachment->ID );
	$tonality = mb_split( '\s', $attachment->post_content );
	$tonality = array_pop( $tonality );
	if ( !is_null( $tonality ) ) {
		$tonality = mb_ereg_replace( '♭', 'b', $tonality );
		$tonality = mb_ereg_replace( '♯', '#', $tonality );
	}
	echo sprintf( '<form class="chords" data-chords-url="%s" data-chords-lang="el" data-chords-tonality="%s" autocomplete="off">',
		esc_url_raw( $url ),
		esc_attr( $tonality ),
	) . "\n";
	echo '<div>' . "\n";
	echo sprintf( '<span>%s:</span>', esc_html__( 'transpose', 'kgr' ) ) . "\n";
	echo '<select class="chords-dir"></select>' . "\n";
	echo '<select class="chords-diatonic"></select>' . "\n";
	echo '<select class="chords-primary"></select>' . "\n";
	echo '<select class="chords-secondary"></select>' . "\n";
	echo '<select class="chords-dst"></select>' . "\n";
	echo '</div>' . "\n";
	echo '<div>' . "\n";
	echo sprintf( '<button type="submit" class="kgr-gtag"%s>%s</button>', kgr_gtag_attachment_data( $attachment, 'show' ), esc_html__( 'show', 'kgr' ) ) . "\n";
	echo sprintf( '<button type="button" class="chords-hide">%s</button>', esc_html__( 'hide', 'kgr' ) ) . "\n";
	echo '<button type="button" class="chords-larger"><span class="fa fa-fw fa-search-plus"></span></button>' . "\n";
	echo '<button type="button" class="chords-smaller"><span class="fa fa-fw fa-search-minus"></span></button>' . "\n";
	echo '</div>' . "\n";
	echo '<div class="chords-text" style="overflow-x: hidden;"></div>' . "\n";
	echo '</form>' . "\n";
}

// include the chords script for show, hide and transpose functionality
add_action( 'wp_enqueue_scripts', function(): void {
	if ( !is_singular() || !has_category( 'songs' ) )
		return;
	wp_enqueue_script( 'kgr-chords', KGR_URL . 'chords/chords.js', ['jquery'], kgr_version() );
} );

// display the number of tracks for current album
function kgr_album_tracks_count(): void {
	if ( !has_category( 'albums' ) )
		return;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		return;
	$count = 0;
	foreach ( $tracks as $track_id )
		if ( $track_id !== 0 )
			$count++;
	if ( $count === 1 )
		echo '<p>' . esc_html( sprintf( '%d %s', $count, __( 'song', 'kgr' ) ) ) . '</p>' . "\n";
	else
		echo '<p>' . esc_html( sprintf( '%d %s', $count, __( 'songs', 'kgr' ) ) ) . '</p>' . "\n";
}

// return the image tag for the attachment thumbnail
function kgr_thumbnail( WP_Post $attachment ): string {
	$metadata = wp_get_attachment_metadata( $attachment->ID );
	if ( !is_array( $metadata ) )
		return '';
	if ( !array_key_exists( 'sizes', $metadata ) )
		return '';
	$url = trailingslashit( dirname( wp_get_attachment_url( $attachment->ID ) ) );
	$sizes = $metadata['sizes'];
	$srcset = [];
	foreach ( $sizes as $key => $size )
		$srcset[$key] = sprintf( '%s %dw', esc_url_raw( $url ) . $size['file'], $size['width'] );
	if ( empty( $srcset ) )
		return '';
	$full = $sizes['full']['file'];
	return sprintf( '<img src="%s" alt="%s" srcset="%s" sizes="%dpx" style="float: left; margin-right: 15px;" />',
		esc_url_raw( $url . $full ),
		esc_html( $full ),
		implode( ', ', $srcset ),
		$sizes['thumbnail']['width']
	) . "\n";
}

// return the icon class for a specific mime type
function kgr_mime_type_icon( string $mime_type ): string {
	switch ( $mime_type ) {
		case 'application/pdf':
			return 'fa fa-fw fa-file-pdf-o';
		case 'audio/midi':
		case 'audio/mpeg':
			return 'fa fa-fw fa-file-audio-o';
		case 'application/xml':
		case 'text/xml':
			return 'fa fa-fw fa-file-code-o';
		case 'text/plain':
			return 'fa fa-fw fa-file-text-o';
		default:
			return 'fa fa-fw fa-file-o';
	}
}

// display a vertical list according to a post query
add_shortcode( 'kgr-list', function( array $atts ): string {
	$html = '';
	if ( array_key_exists( 'category_name', $atts ) && $atts['category_name'] === 'songs' ) {
		$posts = get_posts( $atts );
		foreach ( $posts as $post ) {
			$html .= sprintf( '<h3><a href="%s">%s</a></h3>', esc_url_raw( get_permalink( $post ) ), esc_html( $post->post_title ) ) . "\n";
			$html .= sprintf( '<p>%s</p>', esc_html( $post->post_excerpt ) ) . "\n";
		}
	}
	return $html;
} );

// restore open graph title meta
add_filter( 'open_graph_protocol_meta', function( string $content, string $property ): string {
	if ( $property !== 'og:title' )
		return $content;
	return wp_get_document_title();
}, 10, 2 );
