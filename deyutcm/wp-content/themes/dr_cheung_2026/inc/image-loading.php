<?php
/** Use native image loading while preserving explicit above-the-fold priorities. */
function dy_image_loading_attributes( $attributes ) {
	if ( empty( $attributes['loading'] ) && ( ! isset( $attributes['fetchpriority'] ) || 'high' !== $attributes['fetchpriority'] ) ) {
		$attributes['loading'] = 'lazy';
	}
	if ( empty( $attributes['decoding'] ) ) {
		$attributes['decoding'] = 'async';
	}
	return $attributes;
}
add_filter( 'wp_get_attachment_image_attributes', 'dy_image_loading_attributes', 20 );

/** Cover editor images without hiding their source URLs behind JavaScript. */
function dy_content_image_loading( $image ) {
	$tag = new WP_HTML_Tag_Processor( $image );
	if ( $tag->next_tag( 'IMG' ) ) {
		if ( ! $tag->get_attribute( 'loading' ) && 'high' !== $tag->get_attribute( 'fetchpriority' ) ) {
			$tag->set_attribute( 'loading', 'lazy' );
		}
		if ( ! $tag->get_attribute( 'decoding' ) ) {
			$tag->set_attribute( 'decoding', 'async' );
		}
	}
	return $tag->get_updated_html();
}
add_filter( 'wp_content_img_tag', 'dy_content_image_loading', 20 );
