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

require_once( KGR_DIR . 'tracks.php' );
require_once( KGR_DIR . 'links.php' );

require_once( KGR_DIR . 'widgets.php' );

/*
 * allow xml file uploading
 *
 * @param  $mimes array  the set of allowed mime types
 * @return        array  the new set of allowed mime types
 */
add_filter( 'upload_mimes', function( $mimes ) {
	$mimes['xml'] = 'text/xml';
	return $mimes;
} );

/* set browser tab color */
add_action( 'wp_head', function() {
	$color = get_theme_mod( 'total_template_color', '#FFC107' );
	echo sprintf( '<meta name="theme-color" content="%s" />', $color ) . "\n";
} );

/* remove page_for_posts from archive breadcrumbs */
add_filter( 'breadcrumb_trail_items', function( array $items, array $args ) {
	if ( !is_archive() )
		return $items;
	if ( !is_category() && !is_tag() && !is_tax() )
		return $items;
	array_splice( $items, 1, 1 );
	return $items;
}, 10, 2 );

/* print links related to a post of any type */
function kgr_links() {
	$links = get_post_meta( get_the_ID(), 'kgr-links', TRUE );
	if ( $links === '' )
		return;
	echo sprintf( '<h2>%s</h2>', __( 'Links', 'kgr' ) ) . "\n";
	foreach ( $links as $link ) {
		$url = $link['url'];
		$host = parse_url( $url, PHP_URL_HOST );
		echo '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
		echo sprintf( '<span class="%s"></span>', esc_attr( 'fa ' . kgr_link_icon( $host ) ) ) . "\n";
		echo sprintf( '<a href="%s" target="_blank">%s</a>', esc_url_raw( $url ), esc_html( $link['caption'] ) ) . "\n";
		echo '<span>' . esc_html( '[' . $host . ']' ) . '</span>' . "\n";
		echo '<br />' . "\n";
		echo sprintf( '<i>%s</i>', esc_html( $link['description'] ) ) . "\n";
		echo '</div><!-- .ht-clearfix -->' . "\n";
	}
}

/*
 * return the icon class for a specific link host
 *
 * @param  $host string  the link host
 * @return       string  the icon class
 */
function kgr_link_icon( $host ) {
	switch ( $host ) {
		case 'www.youtube.com':
			return 'fa-youtube';
		case 'vimeo.com':
			return 'fa-vimeo';
		default:
			return 'fa-external-link';
	}
}

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
		echo '<div style="margin: 15px 0;">' . "\n";
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

function kgr_albums( $title = '' ) {
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

function kgr_tags(): void {
	$tags = get_the_tags();
	if ( empty( $tags ) )
		return;
	$tags = array_column( $tags, 'term_id' );
	$tags = implode( ',', $tags );
	echo do_shortcode( '[cool_tag_cloud smallest="12" largest="12" number="0" include="' . $tags . '"]' );
}

function kgr_song_attachments( $args = [] ) {
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
				echo sprintf( '<span class="%s"></span>', esc_attr( 'fa ' . kgr_mime_type_icon( $attachment->post_mime_type ) ) ) . "\n";
				echo sprintf( '<a href="%s" target="_blank">[%s]</a>', esc_url_raw( $url ), esc_html( $ext ) ) . "\n";
				echo '</span>' . "\n";
				break;
			default:
				echo '<div class="ht-clearfix" style="margin-bottom: 15px;">' . "\n";
				echo kgr_thumbnail( $attachment );
				echo sprintf( '<span class="%s"></span>', esc_attr( 'fa ' . kgr_mime_type_icon( $attachment->post_mime_type ) ) ) . "\n";
				echo sprintf( '<a href="%s" target="_blank">', esc_url_raw( $url ) ) . "\n";
				if ( !empty( $attachment->post_excerpt ) )
					echo sprintf( '<span>%s</span>', esc_html( $attachment->post_excerpt ) ) . "\n";
				echo '<span>' . esc_html( sprintf( '[%s, %s]', $ext, size_format( filesize( $dir ), 2 ) ) ) . '</span>' . "\n";
				echo '</a>' . "\n";
				echo '<br />' . "\n";
				echo sprintf( '<i>%s</i>', esc_html( $attachment->post_content ) ) . "\n";
				if ( $attachment->post_mime_type === 'audio/mpeg' )
					echo wp_audio_shortcode( [
						'src' => esc_url_raw( $url ),
					] );
				if ( in_array( $attachment->post_mime_type, [ 'application/xml', 'text/xml' ], TRUE ) ) {
					echo sprintf( '<div id="kgr-mxml-%d" data-mxml-url="%s">', $attachment->ID, esc_url_raw( $url ) ) . "\n";
					echo '<button onclick="kgrMxmlLoad(this);">' . "\n";
					echo sprintf( '<span>%s</span>', esc_html__( 'load', 'kgr' ) ) . "\n";
					echo '<i class="fa fa-spinner fa-pulse" style="display: none;"></i>' . "\n";
					echo '</button>' . "\n";
					echo '<form class="kgr-mxml-form" onsubmit="return kgrMxmlRender(this);" style="display: none;">' . "\n";
					// parts
					echo '<div class="kgr-mxml-form-parts"></div>' . "\n";
					// names
					echo '<label>' . "\n";
					echo '<input type="checkbox" class="kgr-mxml-form-names" name="names" value="1" checked="checked" />' . "\n";
					echo sprintf( '<span>%s</span>', esc_html__( 'display part names', 'kgr' ) ) . "\n";
					echo '</label>' . "\n";
					// transpose
					echo '<div>' . "\n";
					echo '<label>' . "\n";
					echo '<input type="checkbox" class="kgr-mxml-form-transpose" name="transpose" value="on" />' . "\n";
					echo sprintf( '<span>%s</span>', esc_html__( 'transpose', 'kgr' ) ) . "\n";
					echo '</label>' . "\n";
					echo '<div class="kgr-mxml-form-transpose-on">' . "\n";
					echo '<select class="kgr-mxml-form-transpose-direction">' . "\n";
					echo sprintf( '<option value="up" selected="selected">%s</option>', esc_html__( 'up', 'kgr' ) ) . "\n";
					echo sprintf( '<option value="down">%s</option>', esc_html__( 'down', 'kgr' ) ) . "\n";
					echo '</select>' . "\n";
					$intervals = [
						'0' => __( '1st', 'kgr' ),
						'1' => __( '2nd', 'kgr' ),
						'2' => __( '3rd', 'kgr' ),
						'3' => __( '4th', 'kgr' ),
						'4' => __( '5th', 'kgr' ),
						'5' => __( '6th', 'kgr' ),
						'6' => __( '7th', 'kgr' ),
					];
					echo '<select class="kgr-mxml-form-transpose-interval" name="transpose-interval">' . "\n";
					foreach ( $intervals as $key => $value )
						echo sprintf( '<option value="%d">%s</option>', esc_attr( $key ), esc_html( $value ) ) . "\n";
					echo '</select>' . "\n";
					$primary = [
						'-1' => __( 'diminished', 'kgr' ),
						'0' => __( 'perfect', 'kgr' ),
						'1' => __( 'augmented', 'kgr' ),
					];
					echo '<select class="kgr-mxml-form-transpose-primary" name="transpose-primary">' . "\n";
					foreach ( $primary as $key => $value )
						echo sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) ) . "\n";
					echo '</select>' . "\n";
					$secondary = [
						'-2' => __( 'diminished', 'kgr' ),
						'-1' => __( 'minor', 'kgr' ),
						'0' => __( 'major', 'kgr' ),
						'1' => __( 'augmented', 'kgr' ),
					];
					echo '<select class="kgr-mxml-form-transpose-secondary" name="transpose-secondary">' . "\n";
					foreach ( $secondary as $key => $value )
						echo sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) ) . "\n";
					echo '</select>' . "\n";
					echo '</div>' . "\n";
					echo '</div>' . "\n";
					// submit
					echo '<div>' . "\n";
					echo sprintf( '<button type="submit">%s</button>', esc_html__( 'render', 'kgr' ) ) . "\n";
					echo '</div>' . "\n";
					echo '</form>' . "\n";
					echo '</div>' . "\n";
				}
				echo '</div><!-- .ht-clearfix -->' . "\n";
				break;
		}
	}
}

add_action( 'wp_enqueue_scripts', function() {
	if ( !has_category( 'songs' ) )
		return;
	wp_enqueue_script( 'mxml', site_url( 'mxml.js' ), [], '20180506' );
	wp_enqueue_script( 'vexflow', 'https://unpkg.com/vexflow/releases/vexflow-min.js' );
} );

add_action( 'wp_footer', function() {
	if ( !has_category( 'songs' ) )
		return;
?>
<style>
#kgr-mxml-popup {
	display: none;
	position: fixed;
	top: 0px;
	left: 0px;
	width: 100%;
	height: 100vh;
	background-color: rgba(0, 0, 0, .5);
	z-index: 100000;
	padding: 10px;
}
#kgr-mxml-popup-window {
	display: flex;
	flex-direction: column;
	width: 100%;
	height: calc(100vh - 20px);
	background-color: white;
}
#kgr-mxml-popup-close {
	float: right;
}
#kgr-mxml-popup-main {
	flex-grow: 1;
	padding: 10px;
	overflow: auto;
}
</style>
<div id="kgr-mxml-popup">
	<div id="kgr-mxml-popup-window">
		<div id="kgr-mxml-popup-header">
			<button id="kgr-mxml-popup-close">
				<i class="fa fa-times"></i>
			</button>
		</div>
		<div id="kgr-mxml-popup-main">
			<div id="kgr-mxml-popup-renderer"></div>
		</div>
	</div>
</div>
<script>
function kgrToggle(element, show) {
	if (show && element.style.display === 'none') {
		element.style.display = element.dataset.kgrToggle;
		element.dataset.kgrToggle = null;
	}
	if (!show && element.style.display !== 'none') {
		element.dataset.kgrToggle = element.style.display;
		element.style.display = 'none';
	}
}

document.getElementById('kgr-mxml-popup-close').addEventListener('click', function(e) {
	document.getElementById('kgr-mxml-popup').style.display = 'none';
	e.stopPropagation();
});
document.getElementById('kgr-mxml-popup-window').addEventListener('click', function(e) {
	e.stopPropagation();
});
document.getElementById('kgr-mxml-popup').addEventListener('click', function(e) {
	document.getElementById('kgr-mxml-popup').style.display = 'none';
	e.stopPropagation();
});

function kgrMxmlLoad(button) {
	var container = button.parentElement;
	button.getElementsByClassName('fa-spinner')[0].style.display = 'inline-block';
	mxmlLoad(container, function(container) {
		container.getElementsByTagName('button')[0].style.display = 'none';
		var form = container.getElementsByClassName('kgr-mxml-form')[0];
		form.style.display = 'block';
		var form_parts = form.getElementsByClassName('kgr-mxml-form-parts')[0];
		form_parts.innerHTML = '';
		var xml = mxmlResponses[container.id];
		var score_partwise = xml.getElementsByTagName('score-partwise')[0];
		var part_list = score_partwise.getElementsByTagName('part-list')[0];
		for (var score_part of part_list.getElementsByTagName('score-part'))
			form_parts.innerHTML += '<label>' + '\n' +
				'<input type="checkbox" class="kgr-mxml-form-part" name="parts[]" value="' + score_part.id + '" />' + '\n' +
				'<span>' + score_part.getElementsByTagName('part-name')[0].innerHTML + '</span>' +
				'</label>' + '\n';
	});
}

for (let checkbox of document.getElementsByClassName('kgr-mxml-form-transpose')) {
	checkbox.addEventListener('change', function(e) {
		for (let element of e.target.closest('.kgr-mxml-form').getElementsByClassName('kgr-mxml-form-transpose-on'))
			kgrToggle(element, e.target.checked);
	});
	checkbox.dispatchEvent(new Event('change'));
}
for (let select of document.getElementsByClassName('kgr-mxml-form-transpose-interval')) {
	select.addEventListener('change', function(e) {
		let is_primary = [0, 3, 4].includes(parseInt(e.target.value));
		let form = e.target.closest('.kgr-mxml-form');
		let primary = form.getElementsByClassName('kgr-mxml-form-transpose-primary')[0];
		kgrToggle(primary, is_primary);
		primary.value = '0';
		let secondary = form.getElementsByClassName('kgr-mxml-form-transpose-secondary')[0];
		kgrToggle(secondary, !is_primary);
		secondary.value = '0';
	});
	select.dispatchEvent(new Event('change'));
}

function kgrMxmlRender(form) {
	var container = form.parentElement;
	var xml = mxmlResponses[container.id];
	var renderer = document.getElementById('kgr-mxml-popup-renderer');
	var options = {};
	options.visibleParts = [];
	for (let part of form.getElementsByClassName('kgr-mxml-form-part'))
		if (part.checked)
			options.visibleParts.push(part.value);
	options.displayPartNames = form.getElementsByClassName('kgr-mxml-form-names')[0].checked;
	if (form.getElementsByClassName('kgr-mxml-form-transpose')[0].checked) {
		options.transpose = {};
		options.transpose.diatonic = parseInt(form.getElementsByClassName('kgr-mxml-form-transpose-interval')[0].value);
		options.transpose.chromatic = 2 * options.transpose.diatonic;
		if (options.transpose.diatonic >= 3)
			options.transpose.chromatic -= 1;
		if ([0, 3, 4].includes(options.transpose.diatonic))
			options.transpose.chromatic += parseInt(form.getElementsByClassName('kgr-mxml-form-transpose-primary')[0].value);
		else
			options.transpose.chromatic += parseInt(form.getElementsByClassName('kgr-mxml-form-transpose-secondary')[0].value);
		if (form.getElementsByClassName('kgr-mxml-form-transpose-direction')[0].value !== 'up') {
			options.transpose.diatonic = -options.transpose.diatonic;
			options.transpose.chromatic = -options.transpose.chromatic;
		}
	}
	document.getElementById('kgr-mxml-popup').style.display = 'block';
	try {
		mxmlRender(xml, renderer, options);
	} catch (err) {
		console.log(err);
	} finally {
		return false;
	}
}
</script>
<?php
} );

function kgr_album_tracks_count() {
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

function kgr_mime_type_icon( string $mime_type ): string {
	switch ( $mime_type ) {
		case 'application/pdf':
			return 'fa-file-pdf-o';
		case 'audio/midi':
		case 'audio/mpeg':
			return 'fa-file-audio-o';
		case 'application/xml':
		case 'text/xml':
			return 'fa-file-code-o';
		case 'text/plain':
			return 'fa-file-text-o';
		default:
			return 'fa-file-o';
	}
}

add_shortcode( 'kgr-list', function( array $atts ) {
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

add_shortcode( 'kgr-count', function( array $atts ): int {
	if ( !array_key_exists( 'nopaging', $atts ) )
		$atts['nopaging'] = TRUE;
	if ( !array_key_exists( 'fields', $atts ) )
		$atts['fields'] = 'ids';
	return count( get_posts( $atts ) );
} );
