<?php
/** Massage menu: rates and hours, without phone or address. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" class="inner-page menu-page section-inner">
    <header class="page-intro">
        <p class="eyebrow">Lumi Massage · Our menu</p>
        <h1>Body <em>Work.</em></h1>
        <p class="page-lead">Choose your time. Settle in. Let the day go.</p>
        <p class="hours-pill">Business hours <span>9:30 am – 9:30 pm</span></p>
    </header>
    <section class="massage-menu" aria-labelledby="menu-rates-title">
        <div class="menu-section-heading"><h2 id="menu-rates-title">Massage sessions</h2><span>Duration / Price</span></div>
        <dl class="rate-list">
            <div><dt><strong>20</strong> minutes</dt><dd>$39</dd></div>
            <div><dt><strong>30</strong> minutes</dt><dd>$49</dd></div>
            <div><dt><strong>60</strong> minutes</dt><dd>$79</dd></div>
        </dl>
        <aside class="menu-special" aria-labelledby="special-title">
            <span class="special-symbol" aria-hidden="true">✧</span>
            <div><p class="eyebrow">A little extra welcome</p><h2 id="special-title">New customer special</h2><p>9:30 am – 2:30 pm</p></div>
        </aside>
    </section>
    <?php while ( have_posts() ) : the_post(); if ( get_the_content() ) : ?><div class="entry-content page-extra"><?php the_content(); ?></div><?php endif; endwhile; ?>
</main>
<?php get_footer(); ?>
