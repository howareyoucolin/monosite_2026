<?php
/**
 * Chapters and arcs.
 *
 * A chapter is an ordinary post. There is no custom post type: the site has
 * nothing to write but chapters, so a second type would only have split the
 * editor in two.
 *
 * An arc is a tag. Tags are flat, which is the whole reason they fit — an arc
 * has no sub-arcs, and the editor already has a tag box on every post. The
 * constants stay because every query below reads better saying AKW_CHAPTER and
 * AKW_ARC than 'post' and 'post_tag'.
 *
 * Nothing here registers anything. Both the post type and the taxonomy are
 * core's, and the numbering that used to be stored in meta is now derived from
 * publication order — see inc/template-tags.php.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Chapters are plain posts. */
define( 'AKW_CHAPTER', 'post' );

/** Arcs are plain tags. */
define( 'AKW_ARC', 'post_tag' );

/**
 * Post statuses that occupy a chapter number.
 *
 * Drafts are excluded so the published sequence a reader sees has no holes in
 * it. Scheduled and private chapters are counted, because they already have a
 * place in the run and publishing one should not renumber everything after it.
 * Filter this to count drafts if you would rather numbers stay pinned while you
 * write ahead.
 *
 * @return string[]
 */
function akw_counted_statuses() {
	return apply_filters( 'akw_counted_statuses', array( 'publish', 'future', 'private' ) );
}
