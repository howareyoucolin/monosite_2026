<?php
/**
 * Page head, masthead and opening markup.
 *
 * The menu button opens a left drawer above the site name. Only the heading
 * level changes: h1 on the front page, a paragraph elsewhere.
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
	<div class="masthead__inner">
		<?php // Language switch hidden for now; keep the implementation for later.
		// akw_the_language_switcher(); ?>
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<div class="masthead__tools">
				<button type="button" class="menu-toggle" aria-label="<?php esc_attr_e( 'Open menu', 'kungfu_2026' ); ?>" aria-controls="primary-menu-drawer" aria-expanded="false" aria-haspopup="dialog" hidden>
					<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
						<path d="M4 6h16M4 12h16M4 18h16" />
					</svg>
				</button>
			</div>
		<?php endif; ?>

		<div class="masthead__identity">
			<?php if ( is_front_page() ) : ?>
				<h1 class="site-title">
					<a href="<?php echo esc_url( akw_localize_url( home_url( '/' ) ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</h1>
			<?php else : ?>
				<p class="site-title">
					<a href="<?php echo esc_url( akw_localize_url( home_url( '/' ) ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</p>
			<?php endif; ?>

			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="site-tagline"><?php bloginfo( 'description' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

</header>

<?php if ( has_nav_menu( 'primary' ) ) : ?>
	<dialog id="primary-menu-drawer" class="menu-drawer" aria-labelledby="menu-drawer-title">
		<div class="menu-drawer__head">
			<h2 id="menu-drawer-title"><?php esc_html_e( 'Menu', 'kungfu_2026' ); ?></h2>
			<button type="button" class="menu-drawer__close" aria-label="<?php esc_attr_e( 'Close menu', 'kungfu_2026' ); ?>" autofocus>
				<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18" /></svg>
			</button>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Primary menu', 'kungfu_2026' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'menu-drawer__links', 'menu_id' => 'primary-drawer-links', 'depth' => 1, 'fallback_cb' => false ) ); ?>
		</nav>
	</dialog>
<?php endif; ?>

<div class="site">
