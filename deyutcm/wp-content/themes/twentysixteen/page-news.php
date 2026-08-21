<?php get_header();?>
<div class="crumbbread">
	<div class="container">
	德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a> >> 新闻文章
	</div>
</div>

<div class="h30"></div>

<div class="mid">
	<div class="container">
	
		<div class="row">
		
			<div class="col-md-12">
				<div class="unit">
				<h2>德誉堂新闻文章列表</h2>
					<div>以下是关于德誉堂的一些媒体报道的新闻和德誉堂自家发表的文章.</div>
					<div class="h15"></div>
					<div class="video_lists">
					<?php
					$type = 'news';
					$args=array(
					  'post_type' => $type,
					  'post_status' => 'publish',
					  'posts_per_page' => -1,
					  'caller_get_posts'=> 1
					);
					$my_query = null;
					$my_query = new WP_Query($args);
					if( $my_query->have_posts() ) {
					  echo '<ul id="news_list">';
					  while ($my_query->have_posts()) : $my_query->the_post(); ?>
						<li>
							<span class="glyphicon glyphicon-book" aria-hidden="true"></span> <a href="<?php the_permalink();?>"><?php the_title(); ?></a>
						</li>
						<?php
					  endwhile;
					  echo '</ul>';
					}
					wp_reset_query(); 
					?>
					<div class="h30"></div>
					</div>
				</div>
			</div>
			
		</div>
	
	</div>
</div>


<?php get_footer();?>