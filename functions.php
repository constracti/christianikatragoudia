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
	$mimes['xml'] = 'text/xml';
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
					echo do_shortcode( sprintf( '[audio mp3="%s"][/audio]', $url ) );
				if ( in_array( $attachment->post_mime_type, [ 'application/xml', 'text/xml' ], TRUE ) ) {
					echo sprintf( '<div id="kgr-mxml-%d" data-mxml-url="%s">', $attachment->ID, $url ) . "\n";
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
	if ( get_post_type() !== 'kgr-song' )
		return;
	wp_enqueue_script( 'mxml', site_url( 'mxml.js' ), [], '20180506' );
	wp_enqueue_script( 'vexflow', 'https://unpkg.com/vexflow/releases/vexflow-min.js' );
} );

add_action( 'wp_footer', function() {
	if ( get_post_type() !== 'kgr-song' )
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
	if ( !is_array( $metadata ) )
		return '';
	if ( !array_key_exists( 'sizes', $metadata ) )
		return '';
	$url = trailingslashit( dirname( wp_get_attachment_url( $attachment->ID ) ) );
	$sizes = $metadata['sizes'];
	$srcset = [];
	foreach ( $sizes as $key => $size )
		$srcset[$key] = sprintf( '%s %dw', $url . $size['file'], $size['width'] );
	if ( empty( $srcset ) )
		return '';
	$full = $sizes['full']['file'];
	return sprintf( '<img src="%s" alt="%s" srcset="%s" sizes="%dpx" style="float: left; margin-right: 15px;" />',
		esc_url( $url . $full ),
		esc_html( $full ),
		implode( ', ', $srcset ),
		$sizes['thumbnail']['width']
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
