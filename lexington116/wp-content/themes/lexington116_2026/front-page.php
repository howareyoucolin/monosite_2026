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
$phone = lexington116_contact_detail( 'phone' );
get_header();
?>
<main id="main">
    <section class="hero" id="home" aria-labelledby="hero-title">
        <div class="hero-inner">
            <div class="hero-copy">
                <p class="eyebrow hero-eyebrow"><span aria-hidden="true"></span> Asian massage · Unwind your senses</p>
                <h1 id="hero-title">Book your next<br><em>massage.</em></h1>
                <p class="hero-description">Relax and unwind with a massage at Lumi Massage. Call to check availability or book an appointment for your next session.</p>
                <div class="hero-actions">
                    <?php lexington116_booking_link( 'Book an appointment', 'button' ); ?>
                    <a class="button button-outline" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">Call <?php echo esc_html( $phone ); ?></a>
                </div>
                <div class="hero-note"><span class="note-line" aria-hidden="true"></span><p>Leave the world outside.<br><span>A softer touch. A deeper escape.</span></p></div>
            </div>
            <div class="hero-art shape-<?php echo esc_attr( $hero_shape ); ?>">
                <div class="arch-outline" aria-hidden="true"></div>
                <figure class="hero-photo">
                        <video class="hero-video" width="592" height="512" muted loop playsinline preload="metadata" poster="<?php echo esc_url( get_theme_file_uri( '/assets/massage-poster.jpg' ) ); ?>" aria-label="Spa massage video">
                            <source src="<?php echo esc_url( add_query_arg( 'v', filemtime( get_theme_file_path( '/assets/massage.mp4' ) ), get_theme_file_uri( '/assets/massage.mp4' ) ) ); ?>" type="video/mp4">
                        </video>
                        <button type="button" class="video-toggle" hidden>Play video</button>
                    <figcaption>Charming. Attentive. Professional.</figcaption>
                </figure>
                <div class="floating-note"><span class="flower" aria-hidden="true">✳</span><div><span class="floating-label">The art of Asian massage</span><span class="floating-title">A touch of escape.</span></div></div>
            </div>
        </div>
    </section>
    <div class="calm-strip" aria-label="Massage, relaxation, appointments">
        <span>Massage</span><i aria-hidden="true">✧</i><span>Relaxation</span><i aria-hidden="true">✧</i><span>Appointments</span><i aria-hidden="true">✧</i><span>Lumi Massage</span>
    </div>
    <section class="approach section-inner" id="approach" aria-labelledby="approach-title">
        <div><p class="eyebrow">Why choose Lumi Massage</p><h2 id="approach-title">Attentive care.<br><em>A welcoming escape.</em></h2></div>
        <div class="approach-copy"><p>Enjoy a warm welcome, a softly lit spa atmosphere, and attentive service that puts your comfort first. At Lumi Massage, you can settle in, unwind, and enjoy your massage at your own pace.</p><p>Massage offers a welcome break from a busy day: a chance to ease everyday tension, relax tired muscles, and enjoy a little time for yourself. Leave the rush outside and settle into a slower rhythm.</p></div>
    </section>
</main>
<?php get_footer(); ?>
