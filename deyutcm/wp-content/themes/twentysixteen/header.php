<?php
function isMobile()
{
	return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="format-detection" content="telephone=no">
	<!--Load bootstrap library and JQuery-->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">

	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<!--[if lt IE 9]>

	<![endif]-->
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/style.css?v=202406">
	<?php if (isMobile()) { ?>
		<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/mobile.css?v=202406">
	<?php } ?>
	<style>
		body:not(.custom-background-image):before,
		body:not(.custom-background-image):after {
			height: 0;
		}
	</style>
</head>

<body style="background: #FFF url(<?php echo get_template_directory_uri(); ?>/images/med_top.png) repeat-x top;">
	<div class="site-top-contact">
		<div class="container site-top-contact__inner">
			<div class="site-top-contact__address">
				<span class="site-top-contact__address-line">136-68 Roosevelt Ave. #4D,</span>
				<span class="site-top-contact__address-line">Flushing NY 11354</span>
			</div>
			<a class="site-top-contact__phone" href="tel:7188880255">718-888-0255</a>
		</div>
	</div>
	<header class="container">

		<!--<a href="<?php echo get_site_url(); ?>"><h1><img src="<?php echo get_template_directory_uri(); ?>/images/logo2.png" alt="纽约中医"/></h1></a>-->

		<h1>
			<a href="<?php echo get_site_url(); ?>">
				<img src="<?php echo get_template_directory_uri(); ?>/images/top.png" />
				<div>德誉堂</div>
			</a>
		</h1>

		<!--<div id="ninja-scroll" style="background: url(<?php echo get_template_directory_uri(); ?>/images/scroll.png) no-repeat;" >
				<a href="+tel:347-368-6435">
					请直接拔打<br/>
					<span>347-368-6435</span>
				</a>
				</div>-->

	</header>
	<?php $FullUrl = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
	<div id="nav-wrap">
		<nav class="container">
			<ul class="row">
				<li class="<?php if (is_front_page())
					echo 'active'; ?> col-md-1 col-sm-1 col-xs-12"><a
						href="<?php echo get_site_url(); ?>">首页</a></li>
				<li class="<?php if (is_page('about'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						href="<?php echo get_site_url(); ?>/about">德誉堂简介</a></li>
				<li class="<?php if (is_page('videos'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						href="<?php echo get_site_url(); ?>/videos">视频节目</a></li>
				<!-- <li class="<?php if (is_page('news') || is_single())
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						href="<?php echo get_site_url(); ?>/news">新闻文章</a></li> -->
				<li class="<?php if (is_page('contact'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12"><a
						href="<?php echo get_site_url(); ?>/contact">联系我们</a></li>
				<li class="<?php if (is_page('appointment'))
					echo 'active'; ?> col-md-2 col-sm-2 col-xs-12 active"><a
						href="<?php echo get_site_url(); ?>/appointment">网上预约</a></li>
				<li class="col-md-1 col-sm-1 col-xs-12"><a target="_blank" href="http://www.flushing-acupuncture.com/">English</a></li>
				<li class="col-md-1 col-sm-1 col-xs-12"><a target="_blank" href="https://blog.deyutcm.com/">博客</a></li>
			</ul>
		</nav>
	</div>
	<div class="clear"></div>

	<div id="banner" style="background-image: url(<?php echo get_template_directory_uri(); ?>/images/bann.jpg);">

		<div style="    width: 100%;
	height: 100%;
	background: rgba(0,0,0,0.35); 
	text-align: center;
	font-size: 24px;
	line-height: 24px;">

			<?php if (is_page('about')): ?>
				<p class="slogan">古今中医 博大精深</p>
			<?php else: ?>
				<p class="slogan">医德仁心 治人为本</p>
			<?php endif; ?>

			<a class="tell" href="tel:7188880255"><button class="btn btn-primary">718 - 888 - 0255</button></a>

		</div>

	</div>

	<main>
