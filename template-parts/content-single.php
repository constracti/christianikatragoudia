<?php
/**
 * Template part for displaying single posts.
 *
 * @package Total
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="entry-content">
		<div class="entry-meta ht-post-info">
			<?php total_posted_on(); ?>
		</div><!-- .entry-meta -->
		<div style="overflow: hidden;">
			<?php the_content(); ?>
		</div>

		<?php kgr_song_featured_audio(); ?>

		<?php kgr_links(); ?>

		<?php kgr_song_attachments(); ?>

		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'total' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

</article><!-- #post-## -->

