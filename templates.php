<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Display post tags using the cool tag cloud shortcode.
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
 * Return the icon class for a specific link host.
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

/**
 * Display the thumbnail of an external video link.
 */
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
 * Print links related to a post of any type.
 */
function xt_link_list(): void {
	$links = XT_Links::load( get_post() );
	if ( empty( $links ) )
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
	<div>
		<span class="<?= esc_attr( xt_link_icon( $host ) ) ?>"></span>
		<a href="<?= esc_url_raw( $href ) ?>" target="_blank" class="xt-gtag"<?= $gtag ?>><?= esc_html__( 'open', 'xt' ) ?></a>
		<span><?= esc_html( '[' . $host . ']' ) ?></span>
		<br>
		<i><?= esc_html( $link['description'] ) ?></i>
	</div>
</div>
<?php
	}
}

/**
 * Display a numbered list of the tracks for the current album.
 */
function xt_track_list(): void {
	if ( !has_category( 'albums' ) )
		return;
	$tracks = XT_Tracks::load( get_post() );
	if ( empty( $tracks ) )
		return;
?>
<h2><?= esc_html__( 'Songs', 'xt' ) ?></h2>
<ol>
<?php
	foreach ( $tracks as $t => $track ) {
		$song = NULL;
		$text = NULL;
		if ( is_int( $track ) && $track > 0 )
			$song = get_post( $track );
		if ( is_string( $track ) )
			$text = $track;
		if ( !is_null( $song ) ) {
			echo '<div class="clearfix" style="margin-bottom: 15px;">' . "\n";
			if ( has_post_thumbnail( $song ) )
				xt_thumbnail( get_post_thumbnail_id( $song ) );
			echo sprintf( '<span>%d.</span>', $t + 1 ) . "\n";
			echo sprintf( '<a href="%s">%s</a>', esc_url_raw( get_the_permalink( $song ) ), esc_html( get_the_title( $song ) ) ) . "\n";
			echo '<br />' . "\n";
			echo sprintf( '<i>%s</i>', esc_html( xt_first_line( get_the_excerpt( $song ) ) ) ) . "\n";
			echo '</div>' . "\n";
		} elseif ( !is_null( $text ) ) {
			echo '<div style="margin-bottom: 15px;">' . "\n";
			echo sprintf( '<span>%d.</span>', $t + 1 ) . "\n";
			echo sprintf( '<span>%s</span>', esc_html( $text ) ) . "\n";
			echo '</div>' . "\n";
		}
	}
?>
</ol>
<?php
}

/**
 * Display the number of tracks for the current album.
 */
function xt_track_count(): void {
	if ( !has_category( 'albums' ) )
		return;
	$tracks = XT_Tracks::load( get_post() );
	if ( empty( $tracks ) )
		return;
	$count = 0;
	foreach ( $tracks as $track ) {
		if ( is_int( $track ) && $track > 0 )
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
 * Output the creators section.
 */
function xt_song_creators(): void {
	if ( !has_category( 'songs' ) )
		return;
	$excerpt = get_the_excerpt();
	$verse = xt_first_line( $excerpt );
	if ( $verse === $excerpt )
		return;
?>
<h2><?= esc_html__( 'Creators', 'xt' ) ?></h2>
<?php
	$creators = mb_substr( $excerpt, mb_strlen( $verse ) );
	foreach ( explode( "\r\n", $creators ) as $line ) {
		if ( empty( $line ) )
			continue;
		echo sprintf( '<p>%s</p>', esc_html( $line ) ) . "\n";
	}
}

/**
 * Output a list of albums related to the current song.
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
		$tracks = XT_Tracks::load( $album );
		if ( empty( $tracks ) )
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
			$track = $tracks[$prev];
			if ( !( is_int( $track ) && $track > 0 ) )
				continue;
			$track = get_post( $track );
			if ( is_null( $track ) )
				continue;
			$prev = $track;
			break;
		};
		$next = $key;
		while ( TRUE ) {
			$next++;
			if ( $next >= count( $tracks ) ) {
				$next = NULL;
				break;
			}
			$track = $tracks[$next];
			if ( !( is_int( $track ) && $track > 0 ) )
				continue;
			$track = get_post( $track );
			if ( is_null( $track ) )
				continue;
			$next = get_post( $track );
			break;
		};
		echo '<div class="clearfix" style="margin-bottom: 15px;">' . "\n";
		if ( has_post_thumbnail( $album ) )
			xt_thumbnail( get_post_thumbnail_id( $album ) );
		echo '<div>' . "\n";
?>
<p>
	<span class="fas fa-fw fa-compact-disc"></span>
	<a href="<?= get_permalink( $album ) ?>"><?= esc_html( get_the_title( $album ) ) ?></a>
	<span><?= esc_html( '(' . ( $key + 1 ) . ')' ) ?></span>
</p>
<?php
		if ( !is_null( $prev ) ) {
?>
<p>
	<div style="width: 1.25em;display: inline-block;"></div>
	<span class="fas fa-fw fa-backward"></span>
	<a href="<?= get_permalink( $prev ) ?>"><?= esc_html( get_the_title( $prev ) ) ?></a>
</p>
<?php
		}
		if ( !is_null( $next ) ) {
?>
<p>
	<div style="width: 1.25em;display: inline-block;"></div>
	<span class="fas fa-fw fa-forward"></span>
	<a href="<?= get_permalink( $next ) ?>"><?= esc_html( get_the_title( $next ) ) ?></a>
</p>
<?php
		}
		echo '</div>' . "\n";
		echo '</div>' . "\n";
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
 * Echo featured audio shortcode.
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
		if ( !mb_ereg_match( '^.*\.featured$', $attachment->post_title ) )
			continue;
		$url = wp_get_attachment_url( $attachment->ID );
?>
<div style="margin: 15px 0;" class="xt-gtag-audio"<?= xt_gtag_attachment_data( $attachment, 'play', ' featured' ) ?>>
<?php
		if ( $full ) {
			xt_attachment_download( $attachment );
?>
	<i><?= esc_html( $attachment->post_content ) ?></i>
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
 * Echo the attachment section.
 */
function xt_song_attachment_list(): void {
	if ( !has_category( 'songs' ) )
		return;
	$gs = [
		'txt' => [],
		'pdf' => [],
		'full' => [],
		'mp3' => [],
		'*' => []
	];
	$attachments = get_children( [
		'post_parent' => get_the_ID(),
		'post_type' => 'attachment',
		'order' => 'ASC',
	] );
	foreach ( $attachments as $attachment ) {
		if ( $attachment->post_mime_type === 'audio/mpeg' && mb_ereg_match( '^.*\.featured$', $attachment->post_title ) )
			continue;
		if ( $attachment->post_mime_type === 'image/jpeg' )
			continue;
		if ( $attachment->post_mime_type === 'text/plain' && mb_ereg_match( '^.*\.chords$', $attachment->post_title ) ) {
			$gn = 'txt';
		} elseif ( $attachment->post_mime_type === 'application/pdf' ) {
			$gn = mb_ereg_match( '^.*\.full$', $attachment->post_title ) ? 'full' : 'pdf';
		} elseif ( $attachment->post_mime_type === 'audio/mpeg' ) {
			$gn = 'mp3';
		} else {
			$gn = '*';
		}
		$gs[$gn][] = $attachment;
	}
	$gs = [
		'txt' => $gs['txt'],
		'*' => array_merge( $gs['pdf'], $gs['full'], $gs['mp3'], $gs['*'] ),
	];
	foreach ( $gs as $gn => $g ) {
		if ( empty( $g ) )
			continue;
		if ( $gn === 'txt' ) {
			echo '<div style="display: flex;">' . "\n";
			echo sprintf( '<h2 style="margin-right: 10px;">%s</h2>', esc_html__( 'Chords', 'xt' ) ) . "\n";
			echo '<span class="tooltip">' . "\n";
			echo '<span class="fas fa-fw fa-question-circle"></span>' . "\n";
			echo sprintf( '<a href="%s" target="_blank"><span class="tooltiptext">%s</span></a>', site_url( 'chords/' ), esc_html__( 'help', 'xt' ) ) . "\n";
			echo '</span>' . "\n";
			echo '</div>' . "\n";
		} else {
			echo sprintf( '<h2>%s</h2>', esc_html__( 'Scores', 'xt' ) ) . "\n";
		}
		foreach ( $g as $attachment ) {
			$url = wp_get_attachment_url( $attachment->ID );
			echo '<div class="clearfix" style="margin-bottom: 15px;">' . "\n";
			xt_thumbnail( $attachment );
			echo '<div>' . "\n";
			xt_attachment_download( $attachment );
			echo sprintf( '<i>%s</i>', esc_html( $attachment->post_content ) ) . "\n";
			xt_player( $attachment );
			if ( $attachment->post_mime_type === 'text/plain' )
				xt_attachment_chords( $attachment );
			echo '</div>' . "\n";
			echo '</div>' . "\n";
		}
	}
}

/**
 * Echo the thumbnail image tag for the attachment.
 */
function xt_thumbnail( WP_Post|int $attachment ): void {
	if ( is_a( $attachment, 'WP_Post' ) )
		$attachment = $attachment->ID;
	$img = wp_get_attachment_image( $attachment );
	if ( empty( $img ) )
		return;
?>
<div style="float: left; width: 100px; margin-right: 15px;"><?= $img ?></div>
<?php
}

/**
 * Echo the audio shortcode for the attachment.
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
 * Echo the download section for the attachment.
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
	<a href="<?= $url ?>" target="_blank" class="xt-gtag"<?= $gtag ?>><?= esc_html__( 'download', 'xt' ) ?></a>
<?php
	if ( $mime_type === 'application/pdf' ) {
		$url = add_query_arg( 'url', urlencode( $url ), 'https://docs.google.com/gview' );
		$gtag = xt_gtag_attachment_data( $attachment, 'view' );
?>
	<span>|</span>
	<a href="<?= $url ?>" target="_blank" class="xt-gtag"<?= $gtag ?>><?= esc_html__( 'view', 'xt' ) ?></a>
<?php
	}
?>
	<span><?= esc_html( sprintf( '[%s, %s]', $ext, size_format( filesize( $dir ), 2 ) ) ) ?></span>
</div>
<?php
}

/**
 * Return the icon class for a specific mime type.
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
 * Echo controls for the chords attachments.
 */
function xt_attachment_chords( WP_Post $attachment ): void {
	$url = wp_get_attachment_url( $attachment->ID );
	$tonality = mb_split( '\s', $attachment->post_content );
	$tonality = array_pop( $tonality );
?>
<form class="chords"
		data-chords-url="<?= $url ?>"
		data-chords-lang="el"
		data-chords-tonality="<?= esc_attr( $tonality ) ?>"
		autocomplete="off">
	<h3 style="display: none;"><?= esc_html__( 'transpose', 'xt' ) ?>:</h3>
	<div style="display: none;">
		<label><?= esc_html__( 'interval', 'xt' ) ?></label>
		<select class="chords-dir"></select>
		<div class="together">
			<select class="chords-diatonic"></select>
			<select class="chords-primary"></select>
			<select class="chords-secondary"></select>
		</div>
	</div>
	<div class="together">
			<label><?= esc_html__( 'tonality', 'xt' ) ?></label>
			<select class="chords-dst"></select>
	</div>
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
	<button type="button" class="chords-copy">
		<span class="fas fa-fw fa-copy"></span>
		<span><?= esc_html__( 'copy', 'xt' ) ?></span>
		<span class="chords-copied"><?= esc_html__( 'copied!', 'xt' ) ?></span>
	</button>
	<div class="chords-text"></div>
</form>
<?php
}
