<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['tonalities'] = __( 'Tonalities', 'xt' );
	return $tab_list;
} );

add_action( 'xt_tab_html_tonalities', function(): void {
	$posts = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'text/plain',
		'nopaging' => TRUE,
		'orderby' => 'post_id',
		'order' => 'ASC',
	] );
?>
<h3>Chords with altered Tonality</h3>
<table>
	<tbody>
<?php
	foreach ( $posts as $post ) {
		$href = add_query_arg( [
			'post' => $post->post_parent,
			'action' => 'edit',
		], admin_url( 'post.php') );
		$tonality = mb_split( '\s', $post->post_content );
		$tonality = array_pop( $tonality );
		if ( is_null( $tonality ) )
			continue;
		if ( !mb_ereg( '^([A-G])(bb?|#|x)', $tonality, $m ) )
			continue;
?>
		<tr>
			<td><a href="<?= $href ?>"><?= esc_html( $post->post_title ) ?></a></td>
			<td><?= esc_html( $post->post_content ) ?></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
} );
