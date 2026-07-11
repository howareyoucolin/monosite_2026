<?php
$pageTitle = '相冊';
$pageClass = 'photos-page';

$galleryImages = [
    ['title' => '活動留影 1', 'thumb' => '/assets/images/2024/12/WechatIMG120-300x225.jpg', 'full' => '/assets/images/2024/12/WechatIMG120.jpg'],
    ['title' => '活動留影 2', 'thumb' => '/assets/images/2024/12/WechatIMG122-300x200.jpg', 'full' => '/assets/images/2024/12/WechatIMG122.jpg'],
    ['title' => '活動留影 3', 'thumb' => '/assets/images/2024/12/WechatIMG139-300x225.jpg', 'full' => '/assets/images/2024/12/WechatIMG139.jpg'],
    ['title' => '活動留影 4', 'thumb' => '/assets/images/2024/12/WechatIMG135-300x200.jpg', 'full' => '/assets/images/2024/12/WechatIMG135.jpg'],
    ['title' => '活動留影 5', 'thumb' => '/assets/images/2024/12/WechatIMG136-300x200.jpg', 'full' => '/assets/images/2024/12/WechatIMG136.jpg'],
    ['title' => '活動留影 6', 'thumb' => '/assets/images/2024/12/WechatIMG138-300x223.jpg', 'full' => '/assets/images/2024/12/WechatIMG138.jpg'],
    ['title' => '活動留影 7', 'thumb' => '/assets/images/2024/12/WechatIMG142-300x231.jpg', 'full' => '/assets/images/2024/12/WechatIMG142.jpg'],
    ['title' => '活動留影 8', 'thumb' => '/assets/images/2024/12/WechatIMG140-300x225.jpg', 'full' => '/assets/images/2024/12/WechatIMG140.jpg'],
    ['title' => '活動留影 9', 'thumb' => '/assets/images/2024/12/WechatIMG215-300x200.jpg', 'full' => '/assets/images/2024/12/WechatIMG215-scaled.jpg'],
    ['title' => '活動留影 10', 'thumb' => '/assets/images/2024/12/WechatIMG217-300x200.jpg', 'full' => '/assets/images/2024/12/WechatIMG217-scaled.jpg'],
    ['title' => '活動留影 11', 'thumb' => '/assets/images/2024/12/WechatIMG244-300x168.jpg', 'full' => '/assets/images/2024/12/WechatIMG244.jpg'],
    ['title' => '活動留影 12', 'thumb' => '/assets/images/2024/12/WechatIMG248-232x300.jpg', 'full' => '/assets/images/2024/12/WechatIMG248-scaled.jpg'],
    ['title' => '活動留影 13', 'thumb' => '/assets/images/2024/12/WechatIMG245-300x270.jpg', 'full' => '/assets/images/2024/12/WechatIMG245.jpg'],
    ['title' => '活動留影 14', 'thumb' => '/assets/images/2024/12/WechatIMG242-300x200.jpg', 'full' => '/assets/images/2024/12/WechatIMG242.jpg'],
    ['title' => '活動留影 15', 'thumb' => '/assets/images/2024/12/WechatIMG250-271x300.jpg', 'full' => '/assets/images/2024/12/WechatIMG250.jpg'],
    ['title' => '活動留影 16', 'thumb' => '/assets/images/2024/12/WechatIMG247-235x300.jpg', 'full' => '/assets/images/2024/12/WechatIMG247.jpg'],
    ['title' => '活動留影 17', 'thumb' => '/assets/images/2024/12/WechatIMG252-300x174.jpg', 'full' => '/assets/images/2024/12/WechatIMG252.jpg'],
    ['title' => '活動留影 18', 'thumb' => '/assets/images/2024/12/WechatIMG246-300x225.jpg', 'full' => '/assets/images/2024/12/WechatIMG246.jpg'],
];

include __DIR__ . '/includes/header.php';
?>
<div class="intro">
    <h2>相冊</h2>
    <p class="photos-lead" style="text-align: left">在美國信陽同鄉會的各類活動中，我們用鏡頭記錄每一個珍貴瞬間。<br />歡迎點開照片，細看同鄉相聚、文化交流與公益活動的溫暖畫面。</p>

    <div class="photo-gallery" id="photo-gallery">
        <?php foreach ($galleryImages as $index => $image): ?>
            <button
                type="button"
                class="photo-card"
                data-gallery-trigger
                data-index="<?php echo $index; ?>"
                data-full="<?php echo htmlspecialchars($image['full'], ENT_QUOTES, 'UTF-8'); ?>"
                data-title="<?php echo htmlspecialchars($image['title'], ENT_QUOTES, 'UTF-8'); ?>"
                aria-label="查看 <?php echo htmlspecialchars($image['title'], ENT_QUOTES, 'UTF-8'); ?>"
            >
                <img
                    src="<?php echo htmlspecialchars($image['thumb'], ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($image['title'], ENT_QUOTES, 'UTF-8'); ?>"
                    loading="lazy"
                />
                <span class="photo-card-label"><?php echo htmlspecialchars($image['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<div class="gallery-lightbox" id="gallery-lightbox" hidden>
    <button type="button" class="gallery-lightbox-backdrop" data-gallery-close aria-label="關閉相冊彈窗"></button>
    <div class="gallery-lightbox-dialog" role="dialog" aria-modal="true" aria-label="相冊大圖預覽">
        <button type="button" class="gallery-lightbox-close" data-gallery-close aria-label="關閉">×</button>
        <button type="button" class="gallery-lightbox-nav prev" data-gallery-prev aria-label="上一張">‹</button>
        <figure class="gallery-lightbox-figure">
            <img id="gallery-lightbox-image" src="" alt="" />
            <figcaption id="gallery-lightbox-caption"></figcaption>
        </figure>
        <button type="button" class="gallery-lightbox-nav next" data-gallery-next aria-label="下一張">›</button>
    </div>
</div>

<script>
(() => {
    const triggers = Array.from(document.querySelectorAll('[data-gallery-trigger]'));
    const lightbox = document.getElementById('gallery-lightbox');
    const image = document.getElementById('gallery-lightbox-image');
    const caption = document.getElementById('gallery-lightbox-caption');
    const closeButtons = document.querySelectorAll('[data-gallery-close]');
    const prevButton = document.querySelector('[data-gallery-prev]');
    const nextButton = document.querySelector('[data-gallery-next]');
    let activeIndex = 0;

    if (!triggers.length || !lightbox || !image || !caption || !prevButton || !nextButton) {
        return;
    }

    const renderImage = (index) => {
        const item = triggers[index];
        if (!item) {
            return;
        }

        activeIndex = index;
        image.src = item.dataset.full || '';
        image.alt = item.dataset.title || '';
        caption.textContent = item.dataset.title || '';
    };

    const openLightbox = (index) => {
        renderImage(index);
        lightbox.hidden = false;
        document.body.classList.add('lightbox-open');
    };

    const closeLightbox = () => {
        lightbox.hidden = true;
        image.src = '';
        document.body.classList.remove('lightbox-open');
    };

    const showNext = () => renderImage((activeIndex + 1) % triggers.length);
    const showPrev = () => renderImage((activeIndex - 1 + triggers.length) % triggers.length);

    triggers.forEach((trigger, index) => {
        trigger.addEventListener('click', () => openLightbox(index));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeLightbox);
    });

    nextButton.addEventListener('click', showNext);
    prevButton.addEventListener('click', showPrev);

    document.addEventListener('keydown', (event) => {
        if (lightbox.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            closeLightbox();
        } else if (event.key === 'ArrowRight') {
            showNext();
        } else if (event.key === 'ArrowLeft') {
            showPrev();
        }
    });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
