<?php get_header();?>
<div class="crumbbread">
	<div class="container">
	德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a> >> 视频节目
	</div>
</div>

<div class="h30"></div>

<div class="mid">
	<div class="container">
	
		<div class="row">
		
			<div class="col-md-12">
				<div class="unit">
					<h2>中医视频节目</h2>
					<p>【<a href="http://www.uschinapress.com/" target="_blank">侨报</a>记者陈辰6月25日纽约报道】由《侨报》和“侨报之友会”主办、美东联成公所协办的“健康大讲堂”25日于华埠举行，特邀德誉堂中医名师张德超，针对肝炎的病症以及如何防治进行讲解。现场同时为民众带来如何煲汤食疗等中医学基础知识，深受现场听众欢迎。

随着社会老龄化现象出现，华裔移民社区的耆老们都对中医养身、针灸治疗等中国传统医疗方法产生愈加浓厚的兴趣，中医讲究的长期养身之道成为中老年华人朋友的热门话题。25日，《侨报》邀请著名中医师张德超从中医角度，为民众讲解肝病的预防与治疗等健康知识。</p>
			
				</div>
				
				<div class="h30"></div>
				
				<div class="video_lists row">
					<?php
					$c = 0;
					$type = 'videos';
					$args=array(
					  'post_type' => $type,
					  'post_status' => 'publish',
					  'posts_per_page' => -1,
					  'caller_get_posts'=> 1
					);
					$my_query = null;
					$my_query = new WP_Query($args);
					if( $my_query->have_posts() ) {
					  while ($my_query->have_posts()) : $my_query->the_post(); $c++;?>
						<div class="col-md-2 vunit">
						<?php the_content();?>
						<?php the_title(); ?>
						</div>
						<?php echo '<div class="';?>
						<?php if($c%6==0) echo 'h30 ';?>
						<?php if($c%2==0) echo 'm30';?>
						<?php echo '"></div>';?>	
						<?php
					  endwhile;
					}
					wp_reset_query();  
					?>
				</div>
	
	</div>
</div>


<?php get_footer();?>