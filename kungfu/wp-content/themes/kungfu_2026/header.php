<?php
/**
 * Page head, masthead and opening markup.
 *
 * On the front page the masthead doubles as the hero, so the site name is not
 * printed twice.
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

<header class="masthead<?php echo is_front_page() ? ' masthead--hero' : ''; ?>">
	<div class="masthead__inner">
		<?php if ( is_front_page() ) : ?>
			<h1 class="site-title"><?php bloginfo( 'name' ); ?></h1>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="site-tagline"><?php bloginfo( 'description' ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<p class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</p>
		<?php endif; ?>

		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => 'nav',
					'container_class' => 'site-nav',
					'menu_class'     => 'site-nav__list',
					'depth'          => 1,
				)
			);
		}
		?>
	</div>
</header>

<div class="site">
