<?php
/**
 * The template used for displaying page content in page.php
 *
 * @package Total
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<a class="entry-title" href="<?php the_permalink(); ?>" rel="bookmark" style="display: none;"><?php the_title(); ?></a>
	<span class="entry-summary" style="display: none;"><?php the_excerpt(); ?></span>

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'total' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php edit_post_link( esc_html__( 'Edit', 'total' ), '<span class="edit-link">', '</span>' ); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->

