<?php
function isMobile()
{
	return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="format-detection" content="telephone=no">
	<link rel="preload" as="image" href="<?php echo esc_url( get_template_directory_uri() . '/images/bann.jpg' ); ?>" fetchpriority="high">
	<!--Load bootstrap library and JQuery-->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">

	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/style.css?v=20261024-11">
	<?php if (isMobile()) { ?>
		<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/mobile.css?v=202406">
	<?php } ?>
	<!-- Loaded last so it can restyle the header without touching the rules above. -->
	<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/header.css?v=20261024-23">
	<script src="<?php echo esc_url( get_template_directory_uri() . '/js/mobile-header.js?v=20261024-1' ); ?>" defer></script>
	<style>
		body:not(.custom-background-image):before,
		body:not(.custom-background-image):after {
			height: 0;
		}
	</style>
</head>

<body style="background: #FFF;">
	<a class="skip-link screen-reader-text" href="#main-content">跳到主要内容</a>
	<header class="dy-masthead">
		<div class="container dy-masthead__inner">


			<h1 class="dy-brand">
				<a class="dy-brand__link" href="<?php echo get_site_url(); ?>">
					<img class="dy-brand__mark" src="<?php echo get_template_directory_uri(); ?>/images/top.png" alt="" width="60" height="60" loading="eager" decoding="async" />
					<span class="dy-brand__text">
						<span class="dy-brand__name">德誉堂</span>
						<span class="dy-brand__rule"></span>
						<span class="dy-brand__tagline">纽约法拉盛中医诊所</span>
					</span>
				</a>
			</h1>

			<div class="dy-masthead__phone">
				<a class="dy-masthead__phone-link" href="tel:7188880255">
					<svg class="dy-masthead__call-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.36 1.9.7 2.79a2 2 0 0 1-.45 2.11L8.09 9.89a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.83.58 2.79.7A2 2 0 0 1 22 16.92z" />
					</svg>
					<span class="dy-masthead__call-copy"><span class="dy-masthead__call-label">电话咨询</span><span>718-888-0255</span></span>
				</a>
				<address class="dy-masthead__address">
					<span>136-68 Roosevelt Ave. #4D,</span>
					<span>Flushing NY 11354</span>
				</address>
			</div>

		</div>
	</header>
	<?php $FullUrl = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
	<div id="nav-wrap">
		<nav class="container" aria-label="主导航">
			<div class="dy-mobile-toolbar">
				<div class="dy-mobile-toolbar__start">
					<button class="dy-mobile-menu" type="button" aria-expanded="false" aria-controls="dy-navigation-list">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
						<span>菜单</span>
					</button>
					<a class="dy-mobile-location" aria-label="查看法拉盛诊所地址" href="<?php echo esc_url( get_site_url() . '/contact/' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
						法拉盛
					</a>
				</div>
				<a class="dy-mobile-appointment" href="<?php echo esc_url( get_site_url() . '/appointment/' ); ?>">网上预约 <span aria-hidden="true">↗</span></a>
			</div>
			<ul class="row" id="dy-navigation-list">
				<li class="<?php if (is_front_page())
					echo 'active'; ?> col-md-1 col-sm-1 col-xs-12"><a
						<?php if (is_front_page())
							echo 'aria-current="page"'; ?>
						href="<?php echo get_site_url(); ?>">首页</a></li>
				<li class="<?php if (is_page('about'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						<?php if (is_page('about'))
							echo 'aria-current="page"'; ?>
						href="<?php echo get_site_url(); ?>/about">德誉堂简介</a></li>
				<li class="<?php if (is_page('videos'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						<?php if (is_page('videos'))
							echo 'aria-current="page"'; ?>
						href="<?php echo get_site_url(); ?>/videos">视频节目</a></li>
				<!-- <li class="<?php if (is_page('news') || is_single())
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						href="<?php echo get_site_url(); ?>/news">新闻文章</a></li> -->
				<li class="<?php if (is_page('contact'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						<?php if (is_page('contact'))
							echo 'aria-current="page"'; ?>
						href="<?php echo get_site_url(); ?>/contact">联系我们</a></li>
				<li class="<?php if (is_page('appointment'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12 dy-nav-cta"><a
						<?php if (is_page('appointment'))
							echo 'aria-current="page"'; ?>
						href="<?php echo get_site_url(); ?>/appointment">网上预约</a></li>
				<li class="col-md-1 col-sm-1 col-xs-12"><a target="_blank" href="https://blog.deyutcm.com/">博客</a></li>
			</ul>
		</nav>
	</div>
	<div class="clear"></div>

	<div id="banner" class="dy-banner">
        <img class="dy-banner__image" src="<?php echo esc_url( get_template_directory_uri() . '/images/bann.jpg' ); ?>" alt="" width="640" height="175" loading="eager" fetchpriority="high" decoding="async">

		<div class="dy-banner__scrim">

			<?php if (is_page('about')): ?>
				<p class="slogan">古今中医 博大精深</p>
			<?php else: ?>
				<p class="slogan">医德仁心 治人为本</p>
			<?php endif; ?>

			<span class="dy-banner__divider"></span>

			<a class="tell tell--style-i" href="tel:7188880255">
				<svg class="dy-banner__cta-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.36 1.9.7 2.79a2 2 0 0 1-.45 2.11L8.09 9.89a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.83.58 2.79.7A2 2 0 0 1 22 16.92z" />
				</svg>
				<span class="dy-banner__cta-copy">
					<span class="dy-banner__cta-label">电话咨询 · 预约</span>
					<span class="dy-banner__cta-number">718-888-0255</span>
				</span>
				<span class="dy-banner__cta-prompt">致电咨询</span>
				<span class="dy-banner__cta-brush-label">預約諮詢<span class="screen-reader-text">：718-888-0255</span></span>
				<svg class="dy-banner__cta-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M4 12h15m-6-6 6 6-6 6" />
				</svg>
			</a>

		</div>

	</div>

	<main id="main-content">
