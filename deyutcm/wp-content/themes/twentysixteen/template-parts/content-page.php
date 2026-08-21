<?php
/**
 * The template used for displaying page content
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	
	<?php if(is_front_page()):?>
			<p>德誉堂首页</p>
	<?php else:?>
			<p><a href="<?php echo get_site_url();?>">德誉堂首页</a> &gt; <?php the_title(); ?></p>
	<?php endif;?>
	
	<div class="entry-content">
		
		<div class="content-meat">
		<?php
		the_content();
		?>
		</div>
		
	</div><!-- .entry-content -->


</article><!-- #post-## -->


