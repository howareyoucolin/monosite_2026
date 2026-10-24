<?php get_header();?>
<div class="crumbbread">
	<div class="container">
	德誉堂 >> <a href="<?php echo get_site_url();?>">首页</a> >> 网上预约
	</div>
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/css/appointment.css?v=20261024-1' ); ?>">

<script>
jQuery(document).ready(function($) {
    var compactCalendar = $(window).width() <= 600;
    $('#datepicker').datepicker({
        minDate: 1,
        maxDate: '+28D',
        dateFormat: 'mm/dd/yy',
        changeMonth: compactCalendar,
        numberOfMonths: compactCalendar ? 1 : 2,
        monthNames: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        monthNamesShort: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        dayNamesMin: ['日', '一', '二', '三', '四', '五', '六'],
        prevText: '上个月',
        nextText: '下个月',
        showMonthAfterYear: true,
        yearSuffix: '年',
        beforeShowDay: function(date) {
            var day = date.getDay();
            return [day !== 2 && day !== 0];
        },
        onSelect: function() {
            $('#appointment-request input[name=date]').val(this.value);
        }
    }).find('.ui-state-active').removeClass('ui-state-active');
});
</script>
<div class="h30"></div>


<script>
jQuery(document).ready(function($){
    $( ".dy-time-slot" ).click(function(){
	$( ".dy-time-slot" ).removeClass('is-selected').attr('aria-pressed', 'false');
	$(this).addClass('is-selected').attr('aria-pressed', 'true');
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
        $('#appointment-request input[name=name]').val($('#name').val());
        $('#appointment-request input[name=phone]').val($('#phone').val());
        $('#appointment-request textarea[name=other]').val($('#other').val());

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
		$('#appointment-request input[type=submit]').click();
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

<div class="mid dy-appointment">
    <div class="container">
        <div class="dy-appointment__intro">
            <h2>网上预约</h2>
            <p>选择到诊时间，留下您的联系方式。</p>
        </div>
        <div class="dy-appointment__card">
            <section class="dy-appointment__schedule" aria-labelledby="appointment-schedule-title">
                <h3 id="appointment-schedule-title"><span class="dy-appointment__step" aria-hidden="true">01</span>选择日期与时间</h3>
                <p class="dy-appointment__hint">可预约未来四周，周日及周二不开放预约。</p>
                <div id="datepicker" aria-label="预约日期"></div>
                <h4>到诊时间</h4>
                <div class="timepicker" role="group" aria-label="到诊时间">
                    <button class="dy-time-slot" type="button" aria-pressed="false">上午 10:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">上午 11:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">中午 12:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">下午 1:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">下午 2:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">下午 3:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">下午 4:00</button>
                    <button class="dy-time-slot" type="button" aria-pressed="false">下午 5:00</button>
                </div>
            </section>
            <section class="dy-appointment__details" aria-labelledby="appointment-details-title">
                <h3 id="appointment-details-title"><span class="dy-appointment__step" aria-hidden="true">02</span>填写联系信息</h3>
                <p class="dy-appointment__hint">标有 * 的项目为必填。</p>
                <div class="dy-appointment__field">
                    <label for="name">您的姓名 <span aria-hidden="true">*</span></label>
                    <input id="name" class="form-control" autocomplete="name" aria-required="true" placeholder="请输入姓名">
                </div>
                <div class="dy-appointment__field">
                    <label for="phone">联系电话 <span aria-hidden="true">*</span></label>
                    <input id="phone" class="form-control" type="tel" autocomplete="tel" aria-required="true" placeholder="例如：718-888-0255">
                </div>
                <input type="hidden" id="email">
                <div class="dy-appointment__field">
                    <label for="other">其它说明 <span class="dy-appointment__optional">选填</span></label>
                    <textarea id="other" class="form-control" rows="4" placeholder="如有需要，请在此留言"></textarea>
                </div>
                <div id="bookError" class="dy-appointment__error" role="alert" style="display:none;">
                    <div id="bookErrorContent"></div>
                </div>
                <button id="submit" class="dy-appointment__submit" type="button">提交预约</button>
                <p class="dy-appointment__help">需要帮助？<a href="tel:7188880255">718-888-0255</a></p>
            </section>
        </div>

		<form id="appointment-request" action="https://www.deyutcm.com/api/appointment.php" method="POST" style="display:none;">
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