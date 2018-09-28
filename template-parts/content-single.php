<?php
/**
 * Template part for displaying single posts.
 *
 * @package Total
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<a class="entry-title" href="<?php the_permalink(); ?>" rel="bookmark" style="display: none;"><?php the_title(); ?></a>
	<span class="entry-summary" style="display: none;"><?php the_excerpt(); ?></span>

	<div>
		<div class="entry-meta ht-post-info">
			<?php total_posted_on(); ?>
		</div><!-- .entry-meta -->
		<div class="entry-content" style="overflow: hidden;">
			<?php the_content(); ?>
		</div>

		<?php kgr_song_featured_audio(); ?>

		<?php kgr_song_albums( __( 'Albums', 'kgr' ) ); ?>
		<?php kgr_song_subjects( __( 'Subjects', 'kgr' ) ); ?>
		<?php kgr_song_signatures( __( 'Signatures', 'kgr' ) ); ?>

		<?php kgr_links(); ?>

		<?php kgr_song_attachments( [
			'title' => __( 'Files', 'kgr' ),
		] ); ?>

		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'total' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

</article><!-- #post-## -->

