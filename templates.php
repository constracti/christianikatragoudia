<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * display post tags using the cool tag cloud shortcode
 */
function xt_tag_cloud(): void {
	$tags = get_the_tags();
	if ( empty( $tags ) )
		return;
	$tags = array_column( $tags, 'term_id' );
	$tags = implode( ',', $tags );
	echo do_shortcode( '[cool_tag_cloud smallest="12" largest="12" number="0" include="' . $tags . '"]' );
}

/**
 * return the icon class for a specific link host
 */
function xt_link_icon( string $host ): string {
	switch ( $host ) {
		case 'www.youtube.com':
			return 'fab fa-fw fa-youtube';
		case 'vimeo.com':
			return 'fab fa-fw fa-vimeo';
		default:
			return 'fas fa-fw fa-external-link-alt';
	}
}

function xt_link_thumbnail( string $href ): void {
	$host = parse_url( $href, PHP_URL_HOST );
	switch ( $host ) {
		case 'www.youtube.com':
			$id = parse_url( $href, PHP_URL_QUERY );
			if ( !is_string( $id ) )
				return;
			parse_str( $id, $id );
			if ( !is_array( $id ) )
				return;
			if ( !array_key_exists( 'v', $id ) )
				return;
			$id = filter_var( $id['v'], FILTER_VALIDATE_REGEXP, [
				'options' => [
					'regexp' => '/^[a-zA-Z0-9_\-]*$/',
				],
			] );
			if ( $id === FALSE )
				return;
?>
<img src="https://img.youtube.com/vi/<?= $id ?>/default.jpg" style="float: left; width: 100px; margin-right: 15px;">
<?php
			return;
		case 'vimeo.com':
			$id = parse_url( $href, PHP_URL_PATH );
			if ( !is_string( $id ) )
				return;
			$id = filter_var( $id, FILTER_VALIDATE_REGEXP, [
				'options' => [
					'regexp' => '/^\/\d*$/',
				],
			] );
			if ( $id === FALSE )
				return;
?>
<img src="https://vumbnail.com/<?= $id ?>_small.jpg" style="float: left; width: 100px; margin-right: 15px;">
<?php
			return;
	}
}

/**
 * print links related to a post of any type
 */
function xt_link_list(): void {
	$links = get_post_meta( get_the_ID(), 'kgr-links', TRUE );
	if ( $links === '' || !is_array( $links ) || empty( $links ) )
		return;
?>
<h2><?= esc_html__( 'Links', 'xt' ) ?></h2>
<?php
	foreach ( $links as $link ) {
		$href = $link['url'];
		$host = parse_url( $href, PHP_URL_HOST );
		$gtag = xt_gtag_data( 'link', 'click', $href );
?>
<div class="clearfix" style="margin-bottom: 15px;">
<?php
	xt_link_thumbnail( $href );
?>
	<span class="<?= esc_attr( xt_link_icon( $host ) ) ?>"></span>
	<a href="<?= esc_url_raw( $href ) ?>" target="_blank" class="xt-gtag"<?= $gtag ?>><?= esc_html( $link['caption'] ) ?></a>
	<span><?= esc_html( '[' . $host . ']' ) ?></span>
	<br>
	<i><?= esc_html( $link['description'] ) ?></i>
</div>
<?php
	}
}

/**
 * display a numbered list of the tracks for the current album
 */
function xt_track_list(): void {
	if ( !has_category( 'albums' ) )
		return;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' || !is_array( $tracks ) || empty( $tracks ) )
		return;
?>
<h2><?= esc_html__( 'Songs', 'xt' ) ?></h2>
<?php
	$ids = [];
	$oldest = NULL;
?>
<ol>
<?php
	foreach ( $tracks as $track_id ) {
		if ( $track_id === 0 ) {
?>
	<li></li>
<?php
			continue;
		}
		$track = get_post( $track_id );
		$href = get_permalink( $track->ID );
		$title = $track->post_title;
?>
	<li><a href="<?= $href ?>"><?= esc_html( $track->post_title ) ?></a></li>
<?php
		if ( is_null( $oldest ) || $oldest > $track->post_date )
			$oldest = $track->post_date;
	}
?>
</ol>
<?php
	if ( current_user_can( 'administrator' ) ) {
?>
<p><?= esc_html( $oldest ) ?></p>
<?php
	}
}

/**
 * display the number of tracks for the current album
 */
function xt_track_count(): void {
	if ( !has_category( 'albums' ) )
		return;
	$tracks = get_post_meta( get_the_ID(), 'kgr-tracks', TRUE );
	if ( $tracks === '' )
		return;
	$count = 0;
	foreach ( $tracks as $track_id ) {
		if ( $track_id !== 0 )
			$count++;
	}
?>
<p>
	<span><?= $count ?></span>
	<span><?= esc_html( _n( 'song', 'songs', $count, 'xt' ) ) ?></span>
</p>
<?php
}

/**
 * output a list of albums related to the current song
 */
function xt_album_list( string $title = '' ): void {
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
		ob_start();
		$prev = $key;
		while ( TRUE ) {
			$prev--;
			if ( $prev < 0 ) {
				$prev = NULL;
				break;
			}
			if ( $tracks[$prev] !== 0 ) {
				$prev = get_post( $tracks[$prev] );
				break;
			}
		};
		$next = $key;
		while ( TRUE ) {
			$next++;
			if ( $next >= count( $tracks ) ) {
				$next = NULL;
				break;
			}
			if ( $tracks[$next] !== 0 ) {
				$next = get_post( $tracks[$next] );
				break;
			}
		};
?>
<p>
	<span class="fas fa-fw fa-compact-disc"></span>
	<a href="<?= get_permalink( $album ) ?>"><?= esc_html( get_the_title( $album ) ) ?></a>
	<span><?= esc_html( '(' . ( $key + 1 ) . ')' ) ?></span>
</p>
<?php
		if ( !is_null( $prev ) ) {
?>
<p style="margin-left: 20px;">
	<span class="fas fa-fw fa-backward"></span>
	<a href="<?= get_permalink( $prev ) ?>"><?= esc_html( get_the_title( $prev ) ) ?></a>
</p>
<?php
		}
		if ( !is_null( $next ) ) {
?>
<p style="margin-left: 20px;">
	<span class="fas fa-fw fa-forward"></span>
	<a href="<?= get_permalink( $next ) ?>"><?= esc_html( get_the_title( $next ) ) ?></a>
</p>
<?php
		}
		$self[] = ob_get_clean();
	}
	if ( empty( $self ) )
		return;
?>
<h2><?= esc_html__( 'Albums', 'xt' ) ?></h2>
<?php
	echo implode( $self );
}

/**
 * echo featured audio shortcode
 */
function xt_song_featured_audio( bool $full = FALSE ): void {
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
?>
<div style="margin: 15px 0;" class="xt-gtag-audio"<?= xt_gtag_attachment_data( $attachment, 'play', ' featured' ) ?>>
<?php
		if ( $full ) {
			xt_attachment_download( $attachment );
			$html = mb_ereg_replace( 'featured\,?\s*', '', $attachment->post_content );
			if ( $html === FALSE )
				$html = $attachment->post_content;
?>
	<i><?= esc_html( $html ) ?></i>
	<br>
<?php
		}
		echo wp_audio_shortcode( [
			'src' => $url,
		] );
?>
</div>
<?php
	}
}

/**
 * echo the attachment section
 */
function xt_song_attachment_list(): void {
	if ( !has_category( 'songs' ) )
		return;
?>
<h2><?= esc_html__( 'Scores', 'xt' ) ?></h2>
<?php
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
	] );
	foreach( $attachments as $attachment ) {
		if ( $attachment->post_mime_type === 'audio/mpeg' && mb_strpos( $attachment->post_content, 'featured' ) === 0 )
			continue;
		if ( $attachment->post_mime_type === 'image/jpeg' )
			continue;
		$url = wp_get_attachment_url( $attachment->ID );
		echo '<div class="clearfix" style="margin-bottom: 15px;">' . "\n";
		xt_thumbnail( $attachment );
		xt_attachment_download( $attachment );
		echo sprintf( '<i>%s</i>', esc_html( $attachment->post_content ) ) . "\n";
		xt_player( $attachment );
		if ( $attachment->post_mime_type === 'text/plain' && mb_ereg_match( '^.*\.chords$', $attachment->post_title ) )
			xt_attachment_chords( $attachment );
		echo '</div>' . "\n";
	}
}

/**
 * echo the thumbnail image tag for the attachment
 */
function xt_thumbnail( WP_Post $attachment ): void {
	$img = wp_get_attachment_image( $attachment->ID );
	if ( empty( $img ) )
		return;
?>
<div style="float: left; width: 100px; margin-right: 15px;"><?= $img ?></div>
<?php
}

/**
 * echo the audio shortcode for the attachment
 */
function xt_player( WP_Post $attachment, string $suffix = '' ): void {
	if ( $attachment->post_mime_type !== 'audio/mpeg' )
		return;
	$url = wp_get_attachment_url( $attachment->ID );
	$gtag = xt_gtag_attachment_data( $attachment, 'play', $suffix );
	echo sprintf( '<div class=""%s>', $gtag ) . "\n";
	echo '</div>';
?>
<div class="xt-gtag-audio"<?= $gtag ?>>
<?php
	echo wp_audio_shortcode( [
		'src' => $url,
	] );
?>
</div>
<?php
}

/**
 * echo the download section for the attachment
 */
function xt_attachment_download( WP_Post $attachment ): void {
	$url = wp_get_attachment_url( $attachment->ID );
	$dir = get_attached_file( $attachment->ID );
	$ext = pathinfo( $dir, PATHINFO_EXTENSION );
	$mime_type = $attachment->post_mime_type;
	$icon = xt_mime_type_icon( $mime_type );
	$gtag = xt_gtag_attachment_data( $attachment, 'download' );
?>
<div>
	<span class="<?= esc_attr( $icon ) ?>"></span>
	<a href="<?= $url ?>" target="_blank" class="xt-gtag"<?= $gtag ?>>
<?php
	if ( !empty( $attachment->post_excerpt ) ) {
?>
		<span><?= esc_html( $attachment->post_excerpt ) ?></span>
<?php
	}
?>
		<span><?= esc_html( sprintf( '[%s, %s]', $ext, size_format( filesize( $dir ), 2 ) ) ) ?></span>
	</a>
</div>
<?php
}

/**
 * return the icon class for a specific mime type
 */
function xt_mime_type_icon( string $mime_type ): string {
	switch ( $mime_type ) {
		case 'application/pdf':
			return 'fas fa-fw fa-file-pdf';
		case 'audio/midi':
		case 'audio/mpeg':
			return 'fas fa-fw fa-file-audio';
		case 'application/xml':
		case 'text/xml':
			return 'fas fa-fw fa-file-code';
		case 'text/plain':
			return 'fas fa-fw fa-file-alt';
		default:
			return 'fas fa-fw fa-file';
	}
}

/**
 * echo controls for the chords attachments
 */
function xt_attachment_chords( WP_Post $attachment ): void {
	$url = wp_get_attachment_url( $attachment->ID );
	$tonality = mb_split( '\s', $attachment->post_content );
	$tonality = array_pop( $tonality );
	if ( !is_null( $tonality ) ) {
		$tonality = mb_ereg_replace( '♭', 'b', $tonality );
		$tonality = mb_ereg_replace( '♯', '#', $tonality );
	}
?>
<form class="chords"
		data-chords-url="<?= $url ?>"
		data-chords-lang="el"
		data-chords-tonality="<?= esc_attr( $tonality ) ?>"
		autocomplete="off">
	<table>
		<thead>
			<tr>
				<th colspan="2"><?= esc_html__( 'transpose', 'xt' ) ?>:</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th><?= esc_html__( 'tonality', 'xt' ) ?></th>
				<td>
					<select class="chords-dst"></select>
				</td>
			</tr>
			<tr>
				<th><?= esc_html__( 'interval', 'xt' ) ?></th>
				<td>
					<select class="chords-dir"></select>
					<div class="together">
						<select class="chords-diatonic"></select>
						<select class="chords-primary"></select>
						<select class="chords-secondary"></select>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<div>
		<div class="together">
			<button type="submit" class="xt-gtag"<?= xt_gtag_attachment_data( $attachment, 'show' ) ?>>
				<?= esc_html__( 'show', 'xt' ) ?>
			</button>
			<button type="button" class="chords-hide">
				<?= esc_html__( 'hide', 'xt' ) ?>
			</button>
		</div>
		<div class="together">
			<button type="button" class="chords-larger">
				<span class="fas fa-fw fa-search-plus"></span>
			</button>
			<button type="button" class="chords-smaller">
				<span class="fas fa-fw fa-search-minus"></span>
			</button>
		</div>
	</div>
	<div class="chords-text"></div>
</form>
<?php
}
