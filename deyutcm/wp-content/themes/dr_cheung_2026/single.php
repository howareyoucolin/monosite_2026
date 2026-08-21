<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

get_header(); ?>


	

		<?php
			while ( have_posts() ) : the_post(); endwhile;
		?>

		<div class="crumbbread">
			<div class="container">
			德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a> >> <a href="<?php echo get_site_url();?>/news">新闻文章</a> >> <?php the_title();?>
			</div>
		</div>

		<div class="h30"></div>

		<div class="mid">
			<div class="container">
			
				<div class="row">
				
					<div class="col-md-12">
						<div class="unit">
						<h2><?php the_title();?></h2>
							<div class="entry-content">
								<div class="content-meat">
								<?php
									the_content();
								?>
								</div>
							</div><!-- .entry-content -->
						<div class="h30"></div>
							
						</div>
					</div>
					
				</div>
			
			</div>
		</div>









<?php get_footer(); ?>
