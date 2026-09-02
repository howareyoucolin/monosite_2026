<?php
/**
 * Page head, masthead and opening markup.
 *
 * The masthead is the same on every page — centred blackletter title, then the
 * menu in its own ruled bar, the way fk.369usa.com does it. Only the heading
 * level changes: h1 on the front page, a paragraph elsewhere, where the h1
 * belongs to the chapter.
 *
 * The language flags sit at the top left, outside .masthead__inner, so that
 * they do not pull the centred title off centre.
 *
 * @package kungfu_2026
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="masthead">
	<?php akw_the_language_switcher(); ?>

	<div class="masthead__inner">
		<?php if ( is_front_page() ) : ?>
			<h1 class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</h1>
		<?php else : ?>
			<p class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</p>
		<?php endif; ?>

		<?php if ( get_bloginfo( 'description' ) ) : ?>
			<p class="site-tagline"><?php bloginfo( 'description' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'kungfu_2026' ); ?>">
			<div class="site-nav__inner">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'site-nav__list',
						'depth'          => 1,
					)
				);
				?>
			</div>
		</nav>
	<?php endif; ?>
</header>

<div class="site">
