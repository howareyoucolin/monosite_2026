<?php get_header();?>
<div class="crumbbread">
	<div class="container">
	德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a> >> 关于我们
	</div>
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script>
  jQuery(document).ready(function($){
  	var windowsize = $(window).width();
  	if(windowsize > 480){
	    $( "#datepicker" ).datepicker({ 
	    	minDate: 1, 
	    	maxDate: "+28D",
	    	changeMonth: false,
	        numberOfMonths: 2,
	        beforeShowDay: function(date) {
		        var day = date.getDay();
		        return [(day != 2 && day != 0)];
		    },
		    onSelect: function(dateText) {
		    	$('input[name=date]').val(this.value);
		    }
	    }).find(".ui-state-active").removeClass("ui-state-active")
	}
	else{
		$( "#datepicker" ).datepicker({ 
	    	minDate: 1, 
	    	maxDate: "+28D",
	    	changeMonth: true,
	        numberOfMonths: 1,
	        beforeShowDay: function(date) {
		        var day = date.getDay();
		        return [(day != 2 && day != 0)];
		    },
		    onSelect: function(dateText) {
		    	$('input[name=date]').val(this.value);
		    }
	    }).find(".ui-state-active").removeClass("ui-state-active")
	}	
  }); 
  
  </script>
<div class="h30"></div>

<style>
	.timepicker{
		margin-top:15px;
	}
	.timepicker span{
		display:block;
		border: 1px solid #c5c5c5;
	    background: #f6f6f6;
	    font-weight: normal;
	    color: #454545;
		margin-bottom:5px;
		padding-top:3px;
		padding-bottom:3px;
		text-align: center;
		cursor: pointer;
	}
	@media (max-width: 481px) {
		.col-sm-6{
			width: 49.99999%;
			float:left;
		}
	}
</style>
<script>
jQuery(document).ready(function($){
    $( ".timepicker span" ).click(function(){
    	$( ".timepicker span" ).css('background','#f6f6f6').css('color','#454545');
    	$(this).css('background','#007fff').css('color','#fff');
    	$('input[name=time]').val($(this).text());
    });

    $('#name').change(function(){$('input[name=name]').val($(this).val())})
    $('#phone').change(function(){$('input[name=phone]').val($(this).val())})
    $('#email').change(function(){$('input[name=email]').val($(this).val())})
    $('#other').change(function(){$('textarea[name=other]').val($(this).val())})

	function validatePhoneNumber(phone) {
		var regex = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
		return regex.test(phone)
	}

	function validateEmailAddress(email) {
		return /^[a-z0-9][a-z0-9-_\.]+@([a-z]|[a-z0-9]?[a-z0-9-]+[a-z0-9])\.[a-z0-9]{2,10}(?:\.[a-z]{2,10})?$/.test(email)
	}

    var submitErrors = [];

    $('#submit').click(function(){
    	$(this).prop('disabled', true)

    	var date = $('input[name=date]').val()
    	if(!date){
    		submitErrors.push('请选择预约日期！')
    	}

    	var time = $('input[name=time]').val()
    	if(!time){
    		submitErrors.push('请选择预约时间！')
    	}

    	var name = $('input[name=name]').val()
    	if(!name){
    		submitErrors.push('请填写你的姓名！')
    	}

    	var phone = $('input[name=phone]').val()
    	if(!phone || !validatePhoneNumber(phone)){
    		submitErrors.push('请填写正确的电话！')
    	}

    	var email = $('input[name=email]').val()
    	if(email && !validateEmailAddress(email)){
    		submitErrors.push('请填写正确的电子邮箱！')
    	}

    	if(submitErrors.length === 0){
    		$('input[type=submit]').click();
    	}
    	else{
    		$(this).prop('disabled', false)
    		var html = ''
    		submitErrors.forEach(function(error){
    			html += '<div> &bull; '+error+'</div>'
    		});
    		$('#bookErrorContent').html(html)
    		$('#bookError').css('display', 'block')
    		submitErrors = []
    	}
    })
 }); 
</script>

<div class="mid">
	<div class="container">
	
		<h2>网上预约</h2>
		<div class="h30"></div>
		<div style="width:100%;max-width:480px;margin:0 auto;text-align: left;">
<!-- 			<label>请选择一个医师：</label>
			<div>
				xxx xxx xxx
			</div>
			<div class="h30"></div> -->
			<label>请选择一个日期与时间：</label>
			<div id="datepicker"></div>
			<div class="row timepicker">
				<div class="col-md-3 col-sm-6"><span>上午 10:00</span></div>
				<div class="col-md-3 col-sm-6"><span>上午 11:00</span></div>
				<div class="col-md-3 col-sm-6"><span>中午 12:00</span></div>
				<div class="col-md-3 col-sm-6"><span>下午 1:00</span></div>
				<div class="col-md-3 col-sm-6"><span>下午 2:00</span></div>
				<div class="col-md-3 col-sm-6"><span>下午 3:00</span></div>
				<div class="col-md-3 col-sm-6"><span>下午 4:00</span></div>
				<div class="col-md-3 col-sm-6"><span>下午 5:00</span></div>
			</div>
			<div class="h30"></div>
			<label>你的名字 *：</label>
			<input id="name" class="form-control" />
			<div class="h30"></div>
			<label>你的电话号码 *：</label>
			<input id="phone" class="form-control" />
			<div class="h30"></div>
			<!-- <label>你的电子邮箱：</label> -->
			<input type="hidden" id="email" class="form-control" />
			<!-- <div class="h30"></div> -->
			<label>其它说明：</label>
			<textarea id="other" name="other" class="form-control" rows="8"></textarea>
			<div class="h30"></div>
			<button id="submit" class="btn btn-primary" style="width:100%">提交</button>
			<div class="h30"></div>

			<div id="bookError" class="panel panel-danger" style="display:none;">
				<div id="bookErrorContent" class="panel-heading"></div>
			</div>

		</div>

		

		<form action="https://www.deyutcm.com/api/appointment.php" method="POST" style="display:none;">
			<input name="date" /><br/>
			<input name="time" /><br/>
			<input name="name" /><br/>
			<input name="phone" /><br/>
			<input name="email" /><br/>
			<textarea name="other"></textarea><br/>
			<input type="submit" value="submit" />
		</form>
	
	</div>
</div>


<?php get_footer();?>