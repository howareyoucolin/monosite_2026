<?php
$pageTitle = '聯繫我們';
$pageClass = 'contacts-page';
include __DIR__ . '/includes/header.php';
?>
<div class="intro">
    <h2>聯繫我們</h2>
    <p class="contacts-lead">歡迎與我們聯繫。無論是活動合作、加入協會，還是一般諮詢，我們都很樂意與您交流。</p>

    <div class="contact-layout">
        <div class="contact-map-card">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3021.8821919703564!2d-73.83649178786558!3d40.764615771266584!2m3!1f0!2f0!3f0!2m3!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c2600597f64cbd%3A0x441e92dec661ebf1!2s33-70%20Prince%20St%2C%20Flushing%2C%20NY%2011354!5e0!3m2!1sen!2sus!4v1733036900507!5m2!1sen!2sus"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="美國信陽同鄉會地圖位置"></iframe>
        </div>

        <div class="contact-info-card">
            <h3>聯絡資訊</h3>
            <div class="contact-detail-list">
                <div class="contact-detail">
                    <span class="contact-label">地址</span>
                    <p>33-70 Prince St. C11, Flushing NY 11355</p>
                </div>
                <div class="contact-detail">
                    <span class="contact-label">電話</span>
                    <p><a href="tel:+15165585788">1 (516) 558-5788</a></p>
                </div>
                <div class="contact-detail">
                    <span class="contact-label">電子郵箱</span>
                    <p><a href="mailto:chen5165585788@gmail.com">chen5165585788@gmail.com</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
