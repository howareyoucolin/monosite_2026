<?php
/** Lightweight video preview. The shared script creates a player on click. */
$video_id = $args['id'];
$video_title = $args['title'];
$watch_url = 'https://www.youtube.com/watch?v=' . $video_id;
?>
<div class="dy-video-feature">
	<div class="dy-video-media">
		<a class="dy-video-preview" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"
			data-embed="<?php echo esc_url( 'https://www.youtube-nocookie.com/embed/' . $video_id ); ?>"
			data-title="<?php echo esc_attr( $video_title ); ?>" aria-label="<?php echo esc_attr( '播放：' . $video_title ); ?>">
			<img src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg' ); ?>" alt="" width="480" height="360" loading="lazy" decoding="async">
			<span class="dy-video-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 5 11 7-11 7z"/></svg></span>
		</a>
	</div>
	<a class="dy-video-external" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer">在 YouTube 观看 ↗<span class="screen-reader-text">（新标签页）</span></a>
</div>
