<?php
require_once get_template_directory() . '/inc/video-previews.php';
get_header();
?>
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
				
                <p>点击视频即可播放，或在 YouTube 中观看。</p>
                <div class="dy-video-grid">
                    <?php
                    $videos_query = new WP_Query( array(
                        'post_type' => 'videos',
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'ignore_sticky_posts' => true,
                        'no_found_rows' => true,
                    ) );
                    while ( $videos_query->have_posts() ) :
                        $videos_query->the_post();
                        $previews = dy_video_previews( get_post_field( 'post_content', get_the_ID() ) );
                        foreach ( $previews as $index => $video ) :
                            $title = get_the_title();
                            if ( count( $previews ) > 1 ) {
                                $title .= '（' . ( $index + 1 ) . '）';
                            }
                            $watch_url = 'https://www.youtube.com/watch?v=' . $video['id'];
                            $embed_url = 'https://www.youtube-nocookie.com/embed/' . $video['id'];
                            if ( $video['start'] ) {
                                $watch_url = add_query_arg( 't', $video['start'], $watch_url );
                                $embed_url = add_query_arg( 'start', $video['start'], $embed_url );
                            }
                            ?>
                            <article class="dy-video-card">
                                <div class="dy-video-media">
                                    <a class="dy-video-preview" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"
                                       data-embed="<?php echo esc_url( $embed_url ); ?>" data-title="<?php echo esc_attr( $title ); ?>"
                                       aria-label="<?php echo esc_attr( '播放：' . $title ); ?>">
                                        <img src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . $video['id'] . '/hqdefault.jpg' ); ?>" alt="" width="480" height="360" loading="lazy" decoding="async">
                                        <span class="dy-video-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 5 11 7-11 7z"/></svg></span>
                                    </a>
                                </div>
                                <h3><?php echo esc_html( $title ); ?></h3>
                                <a class="dy-video-external" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer">在 YouTube 观看 ↗<span class="screen-reader-text">（新标签页）</span></a>
                            </article>
                        <?php endforeach;
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
	
	</div>
</div>


<script src="<?php echo esc_url( get_template_directory_uri() . '/js/video-previews.js?v=20261024-1' ); ?>" defer></script>
<?php get_footer();?>