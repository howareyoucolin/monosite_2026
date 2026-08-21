<?php get_header();?>
<div class="crumbbread">
	<div class="container">
	德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a> >> 预约成功
	</div>
</div>

<div class="h30"></div>

<?php 
	$date = isset($_GET['date'])?$_GET['date']:'';
	$time = isset($_GET['time'])?$_GET['time']:'';
	$name = isset($_GET['client'])?$_GET['client']:''; // Name is a reserved word
	$phone = isset($_GET['phone'])?$_GET['phone']:'';
	$email = isset($_GET['email'])?$_GET['email']:'';
	$other = isset($_GET['other'])?$_GET['other']:'';
?>

<div class="mid">
	<div class="container">
	
		<h2>预约成功</h2>
		<div class="h30"></div>
		<div style="width:100%;max-width:480px;margin:0 auto;text-align: left;">
			<p>谢谢您！以下是您的预约信息，请确认一下。</p>
			<p>预约时间: <?php echo $date . ' ' . $time;?></p>
			<p>您的名字: <?php echo $name;?></p>
			<p>您的电话: <?php echo $phone;?></p>
			<p>附加信息: <?php echo $other;?></p>
			<p>我们到时见，祝您有健康愉快的一天！</p>
		</div>
		<div class="h30"></div>

	</div>
</div>


<?php get_footer();?>