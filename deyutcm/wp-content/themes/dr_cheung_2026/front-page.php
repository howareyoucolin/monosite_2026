<?php get_header();?>
<div class="crumbbread">
	<div class="container">
	德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a>
	</div>
</div>

<div class="h30"></div>

<div class="mid">
	<div class="container">
	
		<div class="row">
		
			<div class="col-md-6">
				<div class="unit">
					<h2>德誉堂 - 纽约中医老店</h2>
					<p>张德超，湖北中医药大学针灸硕士毕业。师从中国针灸名家丶全国教材主编孙国杰教授。</p>
					<p>张德超出身于国医世家，幼承庭训，技承岐黄，家学渊源。其父张思楠为中国第一代针灸感应电疗机发明人，曾获中华人民共和国卫生部嘉奖。「虎父无犬子」，张德超自幼习文弄武，精通音律，善良而又富有灵性，随父上武当山，进神农架采药出诊，故对认药炮制疗疾，无一不精。长大后，再经过医学院一番专业训练，成为一名良医，行医足迹遍及中国丶澳门丶巴西丶美国等。在来自世界各地的患者中，创下了不少治愈疑难杂症的传奇。在美国，他用针灸和独家秘制的“龙凤膏”，让47岁的不孕顽症白人妇女生下健康宝宝。</p>
					<p><img src="<?php echo get_template_directory_uri();?>/images/newyork-people-love-deyutcm.jpeg" /></p>
					<p>德誉堂致力于中医的研究与临床应用，深得纽约民众的好评。</p>
				</div>
				<div class="h30"></div>
				<div class="unit">
					<h2>主治项目</h2>
					<p>张德超医师主治妇科疾病丶不孕保胎丶产后调理丶前列腺炎丶顽咳哮喘丶癌症调理丶肾炎丶尿毒丶各型肝炎丶鼻炎丶花粉丶中风瘫痪丶失眠忧郁丶心脏病丶糖尿病丶颈肩腰痛丶运动损伤丶小儿多动丶小儿自闭丶小儿厌食丶小儿咳嗽丶；独家秘制: 滋补药酒丶珍珠养胃宝丶五参金咳宝丶天然百忧散丶养颜补血膏丶神奇感冒茶丶生娃龙凤膏丶神奇鼻炎丹丶降糖活胰丹。</p>
				</div>
				<div class="h30"></div>
				<div class="unit">
					<h2>病人好评(视频)</h2>
					<iframe width="100%" height="450" src="https://www.youtube.com/embed/mFPdj1Sko2s" title="德誉堂纽约中医老店 - 妙手仁心" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				</div>
				<div class="h30"></div>
			</div>
		
			<div class="col-md-6">
				<div class="unit" style="margin-bottom:0;padding-bottom:0;">
					<h2 style="margin-bottom:0;padding-bottom:0;">视频 - 神针中医张德超</h2>
				</div>
				
					<iframe style="border:1px solid #F5F5F5;" src="https://www.youtube.com/embed/lGyKY4Cmi04" width="100%" height="320" frameborder="0" allowfullscreen="allowfullscreen"></iframe>

					想了解更多关于张德超医生的视频，请<a href="https://www.youtube.com/channel/UCv0bVJxIwkQVaPn087EfQ9w">点击这儿</a>.
<!-- 
				<div class="h30"></div>

				<div class="unit" style="margin-bottom:0;padding-bottom:0;">
					<h2 style="margin-bottom:0;padding-bottom:0;">视频 - 德誉堂抗击新冠肺炎</h2>
				</div>

				<iframe style="border:1px solid #F5F5F5;" src="https://www.youtube.com/embed/1CWXj6oPLSQ" width="100%" height="320" frameborder="0" allowfullscreen="allowfullscreen"></iframe> -->

				<div class="h30"></div>

				<div class="unit" style="margin-bottom:0;padding-bottom:0;">
					<h2 style="margin-bottom:0;padding-bottom:0;">视频 - 最新视频</h2>
				</div>

				<iframe style="border:1px solid #F5F5F5;" src="https://www.youtube.com/embed/_gJnKczUJec" width="100%" height="320" frameborder="0" title="德誉堂最新视频" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				<div class="h30"></div>
				<iframe style="border:1px solid #F5F5F5;" src="https://www.youtube.com/embed/kpmMsIw7hv0" width="100%" height="320" frameborder="0" title="德誉堂最新视频" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
					
				<!-- 	<div class="unit" style="margin-bottom:15px;padding-bottom:0;">
						<h2 style="margin-bottom:0;padding-bottom:0;">视频最新更新</h2>
					</div>
					
					<div class="video_lists row">
						<?php
						$c = 0;
						$type = 'videos';
						$args=array(
						  'post_type' => $type,
						  'post_status' => 'publish',
						  'posts_per_page' => 3,
						  'caller_get_posts'=> 1
						);
						$my_query = null;
						$my_query = new WP_Query($args);
						if( $my_query->have_posts() ) {
						  while ($my_query->have_posts()) : $my_query->the_post(); $c++;?>
							<div class="col-md-4 vunit">
							<?php the_content();?>
							<?php the_title(); ?>
							</div>
							<?php echo '<div class="';?>
							<?php if($c%6==0) echo 'h30 ';?>
							<?php if($c%2==0) echo 'm30';?>
							<?php echo '"></div>';?>	
							<?php
						  endwhile;
						}
						wp_reset_query();  
						?>
					</div>
						 -->
			</div>
		
			<div class="h30"></div>
		
			<div class="col-md-12 testimonials">
			<div class="unit">
				<h2>病人好评</h2>
				<div class="row">
					<div class="col-md-6" style="margin-bottom:30px;">
						<div class="inner"><h3>病人，乙肝调理</h3><p>能在国外遇到一位既精研医术又恪守医德的良医，是难得的缘分。我从9年前开始看他，乙肝指数一直很高，三亿多病毒数量，这几年间阶段都在服用他熬的药膏，病毒数量也从几亿降到几千万再到现在103，我的家庭医生都不能相信这是真实的数字。</p><p>我自己也是做养生行业的，我知道张医生用的药材都是顶尖的药材，很多其他药行或者中医都不舍得用。这几年他真的成了我生命中很重要的存在，无论大小病痛、腰伤，还是睡眠问题、头痛病毒，我都去找他，第一个想到的是张医生。</p><p>这绝对不是广告，是我真实经历，我真的真心感谢他。他以前对我说，我一定会把你医好的。🙏 我感恩，希望张医生健康并造福更多像我们这样被病痛折磨的病人，我也将我许多客户推介给张医生，希望能帮到他们。</p></div>
					</div>
					<div class="col-md-6" style="margin-bottom:30px;">
						<div class="inner"><h3>病人，肝硬化</h3><p><img style="height:300px;margin:10px; float: right;" src="<?php echo get_template_directory_uri();?>/images/testimonials/001.jpeg" />感谢张德超中医师治好了我的肝硬化。刚刚拍了一个片，结果出来说只看到有一个胆息肉，肝回声很好！！
							</p></div>
					</div>
					<div class="clearfix visible-md-block visible-lg-block"></div>
					<div class="h30 visible-md-block visible-lg-block"></div>
					<div class="col-md-6" style="margin-bottom:30px;">
						<div class="inner"><h3>陈先生，32岁，前列腺炎</h3><p>去年一次体检的时候检查出自己患上了前列腺炎，我感觉情况不是很严重也就没放在心上，于是只是根据平时看到的广告到药店去买药，之后还到到附近的医院开了点药服用，经过一段时间的调理和治疗，前列腺炎的症状好像减轻了，就没有放在心上。几个月之后，我的前列腺炎又犯了，而且情况挺严重的，于是到医院检查治疗，医生给我开了许多药物、中药西药都有，可服用后仍不见好转。真的开始有点担心不知道怎么办的时候, 看到徳誉堂中药馆, 抱着试试看的心情进去了, 张德超医师耐心地解答我所提出的问题，并详细询问了我的生活情况, 给我做了详细检查，在一切都了解清楚后，他告诉我，前列腺炎症状表现多样化，症状不明显的往往容易贻误治疗时机，从而给以后治疗带来难度。针对我的情况，, 张德超给我制定了详细的治疗方案。在他的精湛技术和耐心的治疗方法下，很快我的炎症就已经开始有所好转，真的非常感谢!</p></div>
					</div>
					<div class="col-md-6" style="margin-bottom:30px;">
						<div class="inner"><h3>曹女士，42岁，不孕</h3><p>					今天第一次来找张德超医师看病，期待又满怀感恩，很耐心也很认真，给张德超医师说了我的情况之后，开了一个疗程的药，让我使用，希望可以越来越好！谢谢张德超医师，也祝你身体健康！</p></div>
					</div>
					<div class="clearfix visible-md-block visible-lg-block"></div>
					<div class="h30 visible-md-block visible-lg-block"></div>
					
					<div class="col-md-6" style="margin-bottom:30px;">
						<div class="inner"><h3>李先生，60岁，糖尿病</h3><p>您好，我今天怀着特别感恩的心给您写信，无论用多少言语也无法表达我对您的感激和崇敬之情。我父亲在用了您特地调配的中药之后，上月去医院测量了下血糖，结果是令我们全家人无比激动和幸福的数字：6.8！您可以想象我们全家人得知这样的结果是多么的高兴，我打内心里感激您这位神医！想想我父亲在纽约这么多医院检查, 治疗, 都没有多少进步, 基本上把我的信心毁灭了。直至在网上遇到了张德超医师您，现在我父亲的情况让我稍能安心了，我父亲的进一步巩固治疗，非常感谢张德超医师的悉心照料与治疗.</p></div>
					</div>
					<div class="col-md-6" style="margin-bottom:30px;">
						<div class="inner"><h3>戴先生，58岁，頑咳哮喘</h3><p>张德超医师您好，我父亲的气管哮喘在其他医院看了近半年效果不太好，去年冬天又犯病了。在您的精心调理下，感觉很不错，现在他的胃寒问题也解决很多，再次向您表示感谢，您的医德和医术值得我敬佩。祝您一切顺利。</p></div>
					</div>
				</div>
			</div>
			
		</div>
	
	</div>
</div>


<?php get_footer();?>
