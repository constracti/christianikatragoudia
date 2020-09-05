<?php
/**
 * Template part for displaying single posts.
 *
 * @package Total
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div>
		<div class="entry-meta single-entry-meta">
			<?php total_posted_on(); ?>
		</div><!-- .entry-meta -->
		<?php if(has_post_thumbnail() ): ?>
		<figure class="entry-figure">
			<?php the_post_thumbnail( 'total-blog-header' ); ?>
		</figure>
		<?php endif; ?>
		<div class="entry-content" style="overflow: hidden;">
			<?php the_content(); ?>
		</div><!-- .entry-content -->

		<?php kgr_song_featured_audio( TRUE ); ?>

		<div class="entry-categories">
			<?php echo total_entry_category(); // WPCS: XSS OK. ?>
		</div>
		<?php kgr_tags(); ?>

		<?php kgr_albums( __( 'Albums', 'kgr' ) ); ?>

		<?php kgr_links(); ?>

		<?php kgr_song_attachments( [
			'title' => __( 'Scores', 'kgr' ),
		] ); ?>

		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'total' ),
				'after'  => '</div>',
			) );
		?>
	</div>

</article><!-- #post-## -->

