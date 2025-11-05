<?php

if ( !defined( 'ABSPATH' ) )
	exit;

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_songs_1
add_action( 'wp_ajax_nopriv_xt_app_songs_1', function(): void {
	$posts = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	$posts = array_map( function( WP_Post $post ): array {
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'content' => $post->post_content,
			'title' => $post->post_title,
			'excerpt' => $post->post_excerpt,
			'modified' => $post->post_modified_gmt,
			'permalink' => get_permalink( $post ),
		];
	}, $posts );
	header( 'content-type: application/json' );
	exit( json_encode( $posts ) );
} );

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_chords_1
add_action( 'wp_ajax_nopriv_xt_app_chords_1', function(): void {
	$posts = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'text/plain',
	] );
	$posts = array_map( function( WP_Post $post ): array {
		$path = get_attached_file( $post->ID );
		if ( $path === FALSE )
			return [];
		$text = file_get_contents( $path );
		if ( $text === FALSE )
			return [];
		$tonality = mb_split( '\s', $post->post_content );
		$tonality = array_pop( $tonality );
		if ( is_null( $tonality ) )
			return [];
		if ( !mb_ereg( '^([A-G])(bb?|#|x)?', $tonality, $m ) )
			return [];
		$tonality = $m[1] . $m[2];
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'modified' => $post->post_modified_gmt,
			'parent' => $post->post_parent,
			'content' => $text,
			'tonality' => $tonality,
		];
	}, $posts );
	$posts = array_filter( $posts, function( array $post ): bool {
		return !empty( $post );
	} );
	header( 'content-type: application/json' );
	exit( json_encode( $posts ) );
} );

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_patch_2
add_action( 'wp_ajax_nopriv_xt_app_patch_2', function(): void {
	$after = isset( $_GET['after'] ) ? intval( $_GET['after'] ) : 0;
	$after = wp_date( 'Y-m-d H:i:s', $after, new DateTimeZone( 'UTC' ) );
	$full = isset( $_GET['full'] ) ? $_GET['full'] === "true" : FALSE;
	$now = current_time( 'timestamp', TRUE );
	$date_query = [
		[
			'column' => 'post_modified_gmt',
			'after' => $after,
			'inclusive' => TRUE,
		],
	];
	$song_id_list = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
		'fields' => 'ids',
	] );
	$chord_id_list = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'text/plain',
		'fields' => 'ids',
	] );
	$song_list = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'date_query' => $date_query,
	] );
	$song_list = array_map( function( WP_Post $post ) use ( $full ): array {
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'content' => $full ? $post->post_content : '',
			'title' => $post->post_title,
			'excerpt' => $post->post_excerpt,
			'modified' => $post->post_modified_gmt,
			'permalink' => get_permalink( $post ),
		];
	}, $song_list );
	$chord_list = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'text/plain',
		'date_query' => $date_query,
	] );
	$chord_list = array_map( function( WP_Post $post ) use ( $full ): array|null {
		$path = get_attached_file( $post->ID );
		if ( $path === FALSE )
			return NULL;
		$text = file_get_contents( $path );
		if ( $text === FALSE )
			return NULL;
		$tonality = mb_split( '\s', $post->post_content );
		$tonality = array_pop( $tonality );
		if ( is_null( $tonality ) )
			return NULL;
		if ( !mb_ereg( '^([A-G])(bb?|#|x)?', $tonality, $m ) )
			return NULL;
		$tonality = $m[1] . $m[2];
		$speed = filter_var( $post->post_excerpt, FILTER_VALIDATE_FLOAT, [
			'options' => [
				'default' => NULL,
				'min_range' => 0,
			],
		] );
		return [
			'id' => $post->ID,
			'date' => $post->post_date_gmt,
			'modified' => $post->post_modified_gmt,
			'parent' => $post->post_parent,
			'content' => $full ? $text : '',
			'tonality' => $tonality,
			'speed' => $speed,
		];
	}, $chord_list );
	$chord_list = array_filter( $chord_list, 'is_array' );
	header( 'content-type: application/json' );
	exit( json_encode( [
		'timestamp' => $now,
		'song_id_list' => $song_id_list,
		'chord_id_list' => $chord_id_list,
		'song_list' => $song_list,
		'chord_list' => $chord_list,
	] ) );
} );

// https://christianikatragoudia.gr/wp-admin/admin-ajax.php?action=xt_app_notification_1
add_action( 'wp_ajax_nopriv_xt_app_notification_1', function(): void {
	$ts = get_option( 'xt_app_notification_timestamp' );
	if ( $ts !== FALSE )
		$ts = intval( $ts );
	else
		$ts = NULL;
	header( 'content-type: application/json' );
	exit( json_encode( $ts ) );
} );

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['app_notification'] = __( 'App Notification', 'xt' );
	return $tab_list;
} );

add_action( 'xt_tab_html_app_notification', function(): void {
	$dt = get_option( 'xt_app_notification_timestamp' );
	if ( $dt !== FALSE )
		$dt = ( new DateTime( timezone: wp_timezone() ) )->setTimestamp( $dt )->format( 'Y-m-d\\TH:i' );
	else
		$dt = NULL;
	$href = add_query_arg( 'action', 'xt_app_notification', admin_url( 'admin-post.php' ) );
?>
<h2><?= esc_html__( 'Timestamp', 'xt' ) ?></h2>
<form method="post" action="<?= $href ?>">
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="xt_app_notification_timestamp"><?= esc_html( 'Active timestamp', 'xt' ) ?></label>
				</th>
				<td>
					<input type="datetime-local" name="timestamp" id="xt_app_notification_timestamp" value="<?= $dt ?? '' ?>" class="regular-text">
					<p class="description"><?= esc_html__( 'Devices that haven\'t applied updates after this date will receive a notification.', 'xt' ) ?></p>
				</td>
			</tr>
		</tbody>
	</table>
<?php
	wp_nonce_field( action: 'xt_app_notification', name: 'nonce', referer: FALSE );
	submit_button( text: esc_html( 'Save', 'xt' ) );
?>
</form>
<?php
} );

add_action( 'admin_post_xt_app_notification', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'role', 402 );
	if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], $_GET['action'] ) )
		wp_die( 'nonce', 402 );
	if ( !isset( $_POST['timestamp'] ) || !is_string( $_POST['timestamp'] ) )
		wp_die( 'timestamp', 402 );
	if ( $_POST['timestamp'] !== '' ) {
		$dt = DateTime::createFromFormat( 'Y-m-d\\TH:i', $_POST['timestamp'], wp_timezone() );
		if ( $dt === FALSE )
			wp_die( 'timestamp', 402 );
		update_option( 'xt_app_notification_timestamp', $dt->getTimestamp() );
	} else {
		delete_option( 'xt_app_notification_timestamp' );
	}
	wp_redirect( add_query_arg( [
		'page' => 'xt',
		'tab' => 'app_notification',
	], admin_url( 'options-general.php' ) ) );
} );
