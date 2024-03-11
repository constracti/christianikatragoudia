<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'xt_tab_list', function( array $tab_list ): array {
	$tab_list['characters'] = __( 'Characters', 'xt' );
	return $tab_list;
}, 20 );

function xt_character_row( string $pattern, string $text, string $title, string $href ): void {
	mb_ereg_search_init( $text, $pattern );
	if ( !mb_ereg_search() )
		return;
	$chars = [];
	$names = [
		"\t" => 'TAB',
		"\r" => 'CR',
	];
	$c = mb_ereg_search_getregs();
	do {
		$c = $c[0];
		if ( !array_key_exists( $c, $chars ) )
			$chars[$c] = 0;
		$chars[$c]++;
	} while ( $c = mb_ereg_search_regs() );
	foreach ( $chars as $c => $count ) {
?>
		<tr>
			<td><a href="<?= $href ?>"><?= esc_html( $title ) ?></a></td>
			<td><?= esc_html( array_key_exists( $c, $names ) ? $names[$c] : $c ) ?></td>
			<td><?= esc_html( sprintf( '0x%04x', mb_ord( $c ) ) ) ?></td>
			<td><?= esc_html( IntlChar::charName( $c ) ) ?></td>
			<td><?= esc_html( $count ) ?></td>
		</tr>
<?php
	}
}

add_action( 'xt_tab_html_characters', function(): void {
	$posts = get_posts( [
		'category_name' => 'songs',
		'nopaging' => TRUE,
		'orderby' => 'post_title',
		'order' => 'ASC',
	] );
?>
<h3>Songs with Special Characters</h3>
<table>
	<tbody>
<?php
	foreach ( $posts as $post ) {
		$text = strip_tags( $post->post_content );
		xt_character_row(
			'[^ ,.…\'\-\n\r\p{Greek}]',
			$text,
			$post->post_title,
			add_query_arg( [
				'post' => $post->ID,
				'action' => 'edit',
			], admin_url( 'post.php') ),
		);
	}
?>
	</tbody>
</table>
<?php
} );

add_action( 'xt_tab_html_characters', function(): void {
	$posts = get_posts( [
		'post_type' => 'attachment',
		'post_mime_type' => 'text/plain',
		'nopaging' => TRUE,
		'orderby' => 'post_id',
		'order' => 'ASC',
	] );
?>
<h3>Chords with Special Characters</h3>
<table>
	<tbody>
<?php
	foreach ( $posts as $post ) {
		$path = get_attached_file( $post->ID );
		if ( $path === FALSE )
			continue;
		$text = file_get_contents( $path );
		if ( $text === FALSE )
			continue;
		xt_character_row(
			'[^ ABCDEFG#bdimsux()/,.…\'*\-\n\d\p{Greek}]',
			$text,
			$post->post_title,
			add_query_arg( [
				'post' => $post->post_parent,
				'action' => 'edit',
			], admin_url( 'post.php') ),
		);
	}
?>
	</tbody>
</table>
<?php
} );
