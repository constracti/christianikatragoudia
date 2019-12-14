<?php
/**
 *
 * @package Total
 */

# constracti: edited .ht-portfolio-posts

if(get_theme_mod('total_portfolio_section_disable') != 'on' ){ ?>
<section id="ht-portfolio-section" class="ht-section">
	<div class="ht-container">
	<?php
	$total_portfolio_title = get_theme_mod('total_portfolio_title');
	$total_portfolio_sub_title = get_theme_mod('total_portfolio_sub_title');
	?>

	<?php 
	if( $total_portfolio_title || $total_portfolio_sub_title ){ ?>
	<div class="ht-section-title-tagline">
		<?php 
		if($total_portfolio_title){ ?>
		<h2 class="ht-section-title"><?php echo esc_html($total_portfolio_title); ?></h2>
		<?php } ?>

		<?php if($total_portfolio_sub_title){ ?>
		<div class="ht-section-tagline"><?php echo esc_html($total_portfolio_sub_title); ?></div>
		<?php } ?>
	</div>
	<?php } ?>

	<div class="ht-portfolio-post-wrap">
		<div class="ht-portfolio-posts-kgr ht-clearfix">
			<?php 
			$count = 1;
			$args = [
				'category_name' => 'albums',
				'nopaging' => TRUE,
				'orderby' => 'rand',
			];
			$query = new WP_Query($args);
			if($query->have_posts()):
				while($query->have_posts()) : $query->the_post();	
				$categories = get_the_category();
		 		$category_slug = "";
		 		$cat_slug = array();

		 		foreach ($categories as $category) {
		 			$cat_slug[] = 'total-portfolio-'.$category->term_id;
		 		}

		 		$category_slug = implode(" ", $cat_slug);

		 		if(has_post_thumbnail()){
                	$image_url = get_template_directory_uri().'/images/portfolio-small-blank.png';
                	$total_image = wp_get_attachment_image_src(get_post_thumbnail_id(),'total-portfolio-thumb');	
					$total_image_large = wp_get_attachment_image_src(get_post_thumbnail_id(),'large');
            	}else{
            		$image_url = get_template_directory_uri().'/images/portfolio-small.png';
            		$total_image = "";
            	}
			?>
				<div class="ht-portfolio <?php echo esc_attr($category_slug); ?>">
					<div class="ht-portfolio-outer-wrap">
					<div class="ht-portfolio-wrap" style="background-image: url(<?php echo esc_url($total_image[0]) ?>);">
					
					<img src="<?php echo esc_url($image_url); ?>" alt="<?php esc_attr(get_the_title()); ?>">

					<div class="ht-portfolio-caption">
						<h5><?php the_title(); ?></h5>
						<a class="ht-portfolio-link" href="<?php echo esc_url(get_permalink()); ?>"><i class="fa fa-link"></i></a>
						
						<?php if(has_post_thumbnail()){ ?>
							<a class="ht-portfolio-image" data-lightbox-gallery="gallery1" href="<?php echo esc_url($total_image_large[0]) ?>"><i class="fa fa-search"></i></a>
						<?php } ?>
					</div>
					</div>
					</div>
				</div>
			<?php
			endwhile;
			endif;	
			wp_reset_postdata();
			?>
		</div>
		<?php
		?>
	</div>
	</div>
</section>
<?php }
