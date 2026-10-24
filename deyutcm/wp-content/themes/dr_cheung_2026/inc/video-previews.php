<?php
/** Extract saved YouTube URLs without rendering embeds or making oEmbed requests. */
function dy_video_previews( $content ) {
	$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
	preg_match_all( '~https?://(?:www\.|m\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be)/[^\s<>"\'\[\]]+~i', $content, $matches );
	$videos = array();
	foreach ( $matches[0] as $url ) {
		$parts = wp_parse_url( $url );
		$query = array();
		parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
		$id = '';
		if ( 'youtu.be' === strtolower( $parts['host'] ) ) {
			$id = trim( $parts['path'], '/' );
		} elseif ( preg_match( '~^/(?:embed|shorts|v)/([\w-]{11})(?:/|$)~', $parts['path'], $match ) ) {
			$id = $match[1];
		} elseif ( isset( $query['v'] ) && is_string( $query['v'] ) ) {
			$id = $query['v'];
		}
		if ( ! preg_match( '/^[\w-]{11}$/', $id ) ) {
			continue;
		}
		$start = 0;
		$time = isset( $query['start'] ) ? $query['start'] : ( isset( $query['t'] ) ? $query['t'] : '' );
		if ( is_scalar( $time ) && preg_match( '/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', (string) $time, $duration ) && $time !== '' ) {
			$start = ( isset( $duration[1] ) ? (int) $duration[1] * 3600 : 0 ) + ( isset( $duration[2] ) ? (int) $duration[2] * 60 : 0 ) + ( isset( $duration[3] ) ? (int) $duration[3] : 0 );
		} elseif ( is_scalar( $time ) && ctype_digit( (string) $time ) ) {
			$start = (int) $time;
		}
		$videos[ $id . ':' . $start ] = array( 'id' => $id, 'start' => $start );
	}
	return array_values( $videos );
}
