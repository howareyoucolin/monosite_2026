<?php
/** About Lumi page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" class="inner-page about-page section-inner">
    <section class="about-intro" aria-labelledby="about-title">
        <div class="about-copy">
            <p class="eyebrow">About Lumi Massage</p>
            <h1 id="about-title">Warm smiles.<br><em>Fresh energy.</em></h1>
            <p class="page-lead">Welcoming Asian massage therapists, a personal touch, and a little escape from the everyday.</p>
            <div class="about-story">
                <p>Our team brings a youthful spirit, easy charm, and attentive care to your massage. A friendly smile, a little conversation, and a genuine interest in your comfort make every visit feel more personal.</p>
                <p>Step into a softly lit room, settle onto the table, and let the pace slow down. With warm pink lighting and a relaxed atmosphere, Lumi is a place to leave the day outside and enjoy the moment.</p>
            </div>
            <p class="about-signoff">Charming. Attentive. Professional.</p>
        </div>
        <figure class="about-room">
            <img src="<?php echo esc_url( get_theme_file_uri( '/assets/about-pink-room.jpg' ) ); ?>" width="1122" height="1402" alt="Illustrative massage room with dim pink lighting, a simple massage table, and neatly folded towels" fetchpriority="high">
            <figcaption>A softer light. A slower pace.</figcaption>
        </figure>
    </section>
    <section class="reasons-grid about-reasons" aria-label="Why choose Lumi Massage">
        <article><span class="reason-number">01 · The welcome</span><h2>A little charm</h2><p>Warm personalities and fresh energy create an easygoing atmosphere from the moment you arrive.</p></article>
        <article><span class="reason-number">02 · The attention</span><h2>Your comfort first</h2><p>Share your preferences, find your comfortable pressure, and enjoy attentive service throughout your session.</p></article>
        <article><span class="reason-number">03 · The feeling</span><h2>Room to unwind</h2><p>A massage gives you time to relax tired muscles, ease everyday tension, and enjoy a welcome break from the rush.</p></article>
    </section>
    <?php while ( have_posts() ) : the_post(); if ( get_the_content() ) : ?><div class="entry-content page-extra"><?php the_content(); ?></div><?php endif; endwhile; ?>
</main>
<?php get_footer(); ?>
