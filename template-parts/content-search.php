<?php
/**
 * The template part for displaying results in search pages.
 *
 * @package Total
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('total-hentry'); ?>>
	<header class="entry-header">
		<?php the_title( sprintf( '<h1 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h1>' ); ?>
	</header><!-- .entry-header -->

	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div>

	<?php kgr_album_tracks_count(); ?>

	<?php kgr_song_attachments( [
		'mode' => 'icons',
	] ); ?>

	<div class="entry-readmore">
		<a href="<?php the_permalink(); ?>"><?php _e( 'Read More', 'total' ); ?></a>
	</div>

	<?php kgr_song_featured_audio(); ?>

</article><!-- #post-## -->

