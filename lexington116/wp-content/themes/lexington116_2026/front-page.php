<?php
/** Lexington116 home page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Shape A is the original arch; Shape B is the rounded portrait frame.
$hero_shape = get_theme_mod( 'lexington116_hero_shape', 'b' );
// A preview URL can compare the shapes without changing the saved selection.
if ( isset( $_GET['hero_shape'] ) && is_string( $_GET['hero_shape'] ) ) {
    $preview_shape = sanitize_key( wp_unslash( $_GET['hero_shape'] ) );
    if ( in_array( $preview_shape, array( 'a', 'b' ), true ) ) {
        $hero_shape = $preview_shape;
    }
}
$hero_shape = in_array( $hero_shape, array( 'a', 'b' ), true ) ? $hero_shape : 'b';
get_header();
?>
<main id="main">
    <section class="hero" id="home" aria-labelledby="hero-title">
        <div class="hero-inner">
            <div class="hero-copy">
                <p class="eyebrow hero-eyebrow"><span aria-hidden="true"></span> A little time for yourself</p>
                <h1 id="hero-title">Let the day go.<br>Come back to <em>you.</em></h1>
                <p class="hero-description">Step away from the rush. Discover a moment of calm at 116 Lexington Massage, where your time is yours to slow down, unwind, and simply be.</p>
                <div class="hero-actions">
                    <?php lexington116_booking_link( 'Book your escape', 'button' ); ?>
                    <a class="button button-outline" href="#approach">Discover our approach <span aria-hidden="true">↗</span></a>
                </div>
                <div class="hero-note"><span class="note-line" aria-hidden="true"></span><p>A slower pace.<br><span>A little more room to breathe.</span></p></div>
            </div>
            <div class="hero-art shape-<?php echo esc_attr( $hero_shape ); ?>">
                <div class="arch-outline" aria-hidden="true"></div>
                <figure class="hero-photo">
                        <video class="hero-video" width="800" height="1000" muted loop playsinline preload="metadata" poster="<?php echo esc_url( get_theme_file_uri( '/assets/spa-pink-poster.jpg' ) ); ?>" aria-label="Illustrative spa portrait of an adult massage therapist in a pink uniform, with gentle camera motion">
                            <source src="<?php echo esc_url( get_theme_file_uri( '/assets/spa-pink-loop.mp4' ) ); ?>" type="video/mp4">
                        </video>
                        <button type="button" class="video-toggle" hidden>Play video</button>
                    <figcaption>Pause. Breathe. Begin again.</figcaption>
                </figure>
                <div class="floating-note"><span class="flower" aria-hidden="true">✳</span><div><span class="floating-label">A moment of calm</span><span class="floating-title">Made for you.</span></div></div>
            </div>
        </div>
    </section>
    <div class="calm-strip" aria-label="Pause, unwind, reconnect">
        <span>Pause</span><i aria-hidden="true">✧</i><span>Unwind</span><i aria-hidden="true">✧</i><span>Reconnect</span><i aria-hidden="true">✧</i><span>Feel like yourself</span>
    </div>
    <section class="approach section-inner" id="approach" aria-labelledby="approach-title">
        <div><p class="eyebrow">The Lexington approach</p><h2 id="approach-title">Less rush.<br><em>More presence.</em></h2></div>
        <div class="approach-copy"><p>We believe taking time for yourself should feel natural. A quiet pause, a welcoming atmosphere, and space to leave the day behind.</p><p>Whether you’re making room for a little rest or building a new ritual, start with a moment that belongs to you.</p></div>
    </section>
</main>
<?php get_footer(); ?>
