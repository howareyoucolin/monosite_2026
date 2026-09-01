<?php
/**
 * Front page: every chapter in one table, in reading order.
 *
 * @package kungfu_2026
 */

get_header();

akw_the_chapter_table( akw_get_chapter_index() );

get_footer();
