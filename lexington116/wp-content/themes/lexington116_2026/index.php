<?php
/** Standard content fallback; the landing page lives in front-page.php. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" class="content-page section-inner">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <article <?php post_class(); ?>>
        <h1><?php if ( is_singular() ) : the_title(); else : ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php endif; ?></h1>
        <div class="entry-content"><?php the_content(); ?></div>
    </article>
<?php endwhile; the_posts_navigation(); else : ?>
    <h1>Take a moment.</h1><p>There’s nothing here yet. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Visit our home page.</a></p>
<?php endif; ?>
</main>
<?php get_footer(); ?>
