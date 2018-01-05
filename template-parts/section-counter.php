<?php
/**
 *
 * @package Total
 */

// returns number of published albums
function total_counter_callback_1( $count ) {
	$posts = get_posts( [
		'post_type' => 'kgr-album',
		'nopaging' => TRUE,
		'fields' => 'ids',
	] );
	return count( $posts );
}

// returns number of published songs
function total_counter_callback_2( $count ) {
	$posts = get_posts( [
		'post_type' => 'kgr-song',
		'nopaging' => TRUE,
		'fields' => 'ids',
	] );
	return count( $posts );
}

// returns number of links
function total_counter_callback_3( $count ) {
	$posts = get_posts( [
		'post_type' => 'any',
		'nopaging' => TRUE,
		'meta_key' => 'kgr-links',
		'fields' => 'ids',
	] );
	$count = 0;
	foreach ( $posts as $post ) {
		$links = get_post_meta( $post, 'kgr-links', TRUE );
		if ( !is_array( $links ) )
			continue;
		$count += count( $links );
	}
	return $count;
}

// returns number of uploaded scores
function total_counter_callback_4( $count ) {
	$posts = get_posts( [
		'post_type' => 'attachment',
		'nopaging' => TRUE,
		'post_mime_type' => 'application/xml',
		'fields' => 'ids',
	] );
	return count( $posts );
}

if(get_theme_mod('total_counter_section_disable') != 'on' ){ ?>
<section id="ht-counter-section" data-stellar-background-ratio="0.5">
    <div class="ht-counter-section ht-section">
    <div class="ht-counter-overlay"></div>
    	<div class="ht-container">
    		<?php
    		$total_counter_title = get_theme_mod('total_counter_title');
    		$total_counter_sub_title = get_theme_mod('total_counter_sub_title');
    		?>
    		<?php 
    		if($total_counter_title || $total_counter_sub_title){
    		?>
    			<div class="ht-section-title-tagline">
    				<?php if($total_counter_title){ ?>
    				<h2 class="ht-section-title"><?php echo esc_html($total_counter_title); ?></h2>
    				<?php } ?>
    
    				<?php if($total_counter_sub_title){ ?>
    				<div class="ht-section-tagline"><?php echo esc_html($total_counter_sub_title); ?></div>
    				<?php } ?>
    			</div>
    		<?php } ?>
    
    		<div class="ht-team-counter-wrap ht-clearfix">
    			<?php 
    			for( $i = 1; $i < 5; $i++ ){
    				$total_counter_title = get_theme_mod('total_counter_title'.$i); 
    				$total_counter_count = get_theme_mod('total_counter_count'.$i);
    				$total_counter_icon = get_theme_mod('total_counter_icon'.$i);
					if ( is_callable( 'total_counter_callback_' . $i ) )
						$total_counter_count = call_user_func( 'total_counter_callback_' . $i, $total_counter_count );
    				if($total_counter_count){
    					?>
    					<div class="ht-counter">
    						<div class="ht-counter-icon">
    							<i class="<?php echo esc_attr($total_counter_icon); ?>"></i>
    						</div>
    
    						<div class="ht-counter-count odometer odometer<?php echo $i; ?>" data-count="<?php echo absint($total_counter_count); ?>">
    							<?php
							echo pow( 10, floor( log( $total_counter_count, 10 ) ) );
							?>
    						</div>
    
    						<h6 class="ht-counter-title">
    							<?php echo esc_html($total_counter_title); ?>
    						</h6>
    					</div>
    					<?php
    				}
    			}
    			?>
    		</div>
    	</div>
    </div>
</section>
<?php }
