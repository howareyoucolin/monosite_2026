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
	<!--Load bootstrap library and JQuery-->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">

	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/style.css?v=202406">
	<?php if (isMobile()) { ?>
		<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/mobile.css?v=202406">
	<?php } ?>
	<!-- Loaded last so it can restyle the header without touching the rules above. -->
	<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/header.css?v=202608">
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

			<div class="dy-masthead__address">
				<svg class="dy-masthead__icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
					<path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
				</svg>
				<span>136-68 Roosevelt Ave. #4D, Flushing NY 11354</span>
			</div>

			<h1 class="dy-brand">
				<a class="dy-brand__link" href="<?php echo get_site_url(); ?>">
					<img class="dy-brand__mark" src="<?php echo get_template_directory_uri(); ?>/images/top.png" alt="" />
					<span class="dy-brand__text">
						<span class="dy-brand__name">德誉堂</span>
						<span class="dy-brand__rule"></span>
						<span class="dy-brand__tagline">纽约法拉盛中医诊所</span>
					</span>
				</a>
			</h1>

			<div class="dy-masthead__phone">
				<a class="dy-masthead__phone-link" href="tel:7188880255">
					<svg class="dy-masthead__icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
						<path d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z" />
					</svg>
					<span>718-888-0255</span>
				</a>
			</div>

		</div>
	</header>
	<?php $FullUrl = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
	<div id="nav-wrap">
		<nav class="container" aria-label="主导航">
			<ul class="row">
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
				<li class="col-md-1 col-sm-1 col-xs-12"><a target="_blank" href="http://www.flushing-acupuncture.com/">English</a></li>
				<li class="col-md-1 col-sm-1 col-xs-12"><a target="_blank" href="https://blog.deyutcm.com/">博客</a></li>
			</ul>
		</nav>
	</div>
	<div class="clear"></div>

	<div id="banner" class="dy-banner" style="background-image: url(<?php echo get_template_directory_uri(); ?>/images/bann.jpg);">

		<div class="dy-banner__scrim">

			<?php if (is_page('about')): ?>
				<p class="slogan">古今中医 博大精深</p>
			<?php else: ?>
				<p class="slogan">医德仁心 治人为本</p>
			<?php endif; ?>

			<span class="dy-banner__divider"></span>

			<a class="tell" href="tel:7188880255"><button class="btn btn-primary" type="button">
				<svg class="dy-banner__cta-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
					<path d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z" />
				</svg>
				718 - 888 - 0255
			</button></a>

		</div>

	</div>

	<main id="main-content">
