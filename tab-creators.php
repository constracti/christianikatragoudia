<?php

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['creators'] = __( 'Creators', 'xt' );
	return $tab_list;
} );

add_action( 'xt_tab_html_creators', function(): void {
?>
<h3>Creators</h3>
<table class="fixed widefat striped">
	<thead>
		<tr>
			<th><?= esc_html__( 'Title', 'xt' ) ?></th>
			<th><?= esc_html__( 'Excerpt', 'xt' ) ?></th>
			<th><?= esc_html__( 'Verse', 'xt' ) ?></th>
			<th><?= esc_html__( 'Creators', 'xt' ) ?></th>
		</tr>
	</thead>
	<tbody>
<?php
	$posts = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
	foreach ( $posts as $post ) {
		echo '<tr>';
		$href = add_query_arg( [
			'post' => $post->ID,
			'action' => 'edit',
		], admin_url( 'post.php') );
		printf( '<td><a href="%s">%s</a></td>', $href, esc_html( $post->post_title ) );
		$excerpt = $post->post_excerpt;
		printf( '<td>%s</td>', nl2br( esc_html( $excerpt ) ) );
		$verse = xt_first_line( $excerpt );
		printf( '<td>%s</td>', esc_html( $verse ) );
		printf( '<td>%s</td>', nl2br( esc_html( ( function( WP_Post $song, string $excerpt, string $verse ): string {
			if ( empty( trim( $excerpt ) ) )
				return 'empty';
			if ( $verse !== $excerpt )
				return 'ready';
			$atts = get_posts( [
				'post_parent' => $song->ID,
				'post_type' => 'attachment',
				'post_mime_type' => 'text/xml',
				'nopaging' => TRUE,
				'fields' => 'ids',
			] );
			if ( empty( $atts ) )
				return 'xml not found';
			$path = get_attached_file( $atts[0] );
			if ( $path === FALSE )
				return 'xml not found';
			$data = file_get_contents( $path );
			if ( $data === FALSE )
				return 'xml not readable';
			$composer = NULL;
			$lyricist = NULL;
			$xml = new SimpleXMLElement( $data );
			foreach ( $xml->identification->creator as $creator ) {
				switch ( $creator->attributes()->type ) {
					case 'composer':
						$composer = strval( $creator );
						break;
					case 'lyricist':
						$lyricist = strval( $creator );
						break;
				}
			}
			$ret = [];
			if ( !is_null( $lyricist ) )
				$ret[] = 'στίχοι: ' . $lyricist;
			if ( !is_null( $composer ) )
				$ret[] = 'μουσική: ' . $composer;
			if ( empty( $ret ) )
				return 'unknown';
			$song->post_excerpt = $verse . "\r\n\r\n" . implode( "\r\n", $ret );
			if ( wp_update_post( $song ) !== $song->ID )
				return 'error';
			return implode( ' - ', $ret );
		} )( $post, $excerpt, $verse ) ) ) );
		echo '</tr>';
	}
?>
	</tbody>
</table>
<?php
} );
