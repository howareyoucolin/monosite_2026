<?php
//SEO META:
	$TITLE = '';
	$DESCRIPTIONS = '';
	$FullUrl = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
	if(substr($FullUrl, -11)=='contact.php'):
		$TITLE = '法拉盛中医 - 法拉盛诊所联系方式';
		$DESCRIPTIONS = '联系电话: 718-961-0528,诊所微信号:vervedent,诊所地址: 13987 35th AVE #Room L2, Flushing NY 11354. 我们位于离法拉盛中心附近,从缅街步行10分钟即到, 公共交通十分方便, 而且我们也有独立的停车位提供我们客人免费使用.';
	elseif(substr($FullUrl, -9)=='about.php'):
		$TITLE = '法拉盛牙医 - 罗立玮个人简价';
		$DESCRIPTIONS = '罗立玮牙医博士现任纽约大学口腔黏膜科临床副教授，亚洲再教育学术事务主管。哥伦比亚大学牙医学院毕业，并获哥伦比亚大学长老会医院牙科住院医总医师荣誉。临床执业经验超过十五年，专精美容牙科，数位化修复，水激光无痛牙科手术，面部微创整形美容技术，如肉毒杆菌，玻尿酸注射美容等。精通广东话、普通话，客家话与英文。';
	elseif(substr($FullUrl, -12)=='services.php'):
		$TITLE = '法拉盛牙医 - 服务与保险';
		$DESCRIPTIONS = '提供各大方面的牙科服务和牙齿美容, 罗立玮牙医诊所接受大部分商业牙科保险，并接收各类政府牙科保险，无保险者自费可优惠。';
	else:
		$TITLE = '纽约中医 - 纽约著名老中医张德超';
		$DESCRIPTIONS = '联系电话: 347-368-6435. 纽约著名老中医张德超有着多年的中医知识与经验, 诊所位于法拉盛图书馆附近, 交通方便.';
	endif;
get_header(); ?>



<div id="primary" class="content-area">

		

		<?php
		// Start the loop.
		while ( have_posts() ) : the_post();

			get_template_part( 'template-parts/content', 'page' );

		endwhile;
		?>
		
		<?php if(is_page('videos')):?>
			<article id="video_div">
			
				<header class="entry-header">
					<p class="entry-title">德誉堂视频列表</p>
				</header><!-- .entry-header -->
				<div class="video_lists row">
					<?php
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
					  while ($my_query->have_posts()) : $my_query->the_post(); ?>
						<div class="col-md-4">
						<?php the_content();?>
						<?php the_title(); ?>
						</div>
						<?php
					  endwhile;
					}
					wp_reset_query();  // Restore global post data stomped by the_post().
					?>
				</div>
			
			</article><!-- #video list-## -->
		<?php endif;?>
		
		<?php if(is_page('news')):?>
			<article id="video_div">
			
				<header class="entry-header">
					<p class="entry-title">德誉堂新闻文章列表</p>
				</header><!-- .entry-header -->
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
					wp_reset_query();  // Restore global post data stomped by the_post().
					?>
				</div>
			
			</article><!-- #video list-## -->
		<?php endif;?>
			
</div><!-- .content-area -->

<?php get_footer(); ?>
