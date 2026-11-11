<?php
require_once __DIR__ . '/../app/database.php';
$slug=trim($_GET['slug']??'about');
$page=db_run('SELECT * FROM content_pages WHERE slug=? AND is_published=1',[$slug])->get_result()->fetch_assoc();
if(!$page)http_response_code(404);
$page_title=($page['title']??'Không tìm thấy trang').' · '.setting('site_name');
include __DIR__ . '/../app/views/layout/header.php';
?>
<?php if($page && $slug==='about'): ?>
<section class="about-hero" aria-labelledby="about-title">
 <img class="about-hero-image" src="<?= e(image_url(setting('about_image'))) ?>" alt="" fetchpriority="high" width="1664" height="941">
 <div class="container about-hero-inner">
  <div class="about-hero-copy">
   <p class="eyebrow about-enter"><?= e(setting('about_eyebrow')) ?></p>
   <h1 id="about-title" class="about-enter"><span class="about-brand"><?= e(setting('site_name')) ?></span><span class="about-promise"><?= e(setting('about_headline')) ?></span></h1>
   <p class="about-intro about-enter"><?= e(setting('about_intro')) ?></p>
   <a class="about-link about-enter" href="#our-story"><?= e($page['title']) ?> <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
  </div>
 </div>
</section>
<section class="container about-story" id="our-story" aria-labelledby="story-title">
 <div class="about-story-heading reveal"><p class="eyebrow"><?= e(setting('about_story_label')) ?></p><h2 id="story-title"><?= e(setting('about_story_title')) ?></h2><span class="about-story-rule" aria-hidden="true"></span></div>
 <article class="about-story-body reveal">
  <?php foreach(preg_split('/\R\s*\R/u',trim($page['body']),-1,PREG_SPLIT_NO_EMPTY) as $paragraph): ?>
   <?php if(mb_strlen($paragraph)<=90 && !preg_match('/[.!?。]$/u',trim($paragraph))): ?><h3><?= e($paragraph) ?></h3><?php else: ?><p><?= nl2br(e($paragraph)) ?></p><?php endif ?>
  <?php endforeach ?>
 </article>
</section>
<section class="about-values" aria-labelledby="values-title"><div class="container">
 <h2 id="values-title" class="reveal"><?= e(setting('about_values_title')) ?></h2>
 <div class="about-values-list"><?php for($i=1;$i<=3;$i++): ?><div class="about-value reveal"><span class="about-value-number" aria-hidden="true">0<?= $i ?></span><h3><?= e(setting('about_value_'.$i.'_title')) ?></h3><p><?= e(setting('about_value_'.$i.'_body')) ?></p></div><?php endfor ?></div>
</div></section>
<section class="container about-closing reveal" aria-labelledby="closing-title">
 <h2 id="closing-title"><?= e(setting('about_cta_title')) ?></h2>
 <div><p><?= e(setting('about_cta_body')) ?></p><div class="about-closing-actions"><a class="btn btn-dark" href="catalog.php"><?= e(setting('about_cta_button')) ?> <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><a class="about-text-link" href="contact.php"><?= e(setting('about_contact_button')) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></div>
</section>
<?php else: ?>
<section class="page-heading"><div class="container"><p class="eyebrow">VALISTORE / THÔNG TIN</p><h1><?= e($page['title']??'Trang này chưa được xuất bản.') ?></h1></div></section>
<div class="container editorial-page"><nav class="info-nav" aria-label="Trang thông tin"><?php foreach($conn->query('SELECT slug,title FROM content_pages WHERE is_published=1 ORDER BY sort_order,id')->fetch_all(MYSQLI_ASSOC) as $p): ?><a class="<?= $slug===$p['slug']?'active':'' ?>" href="page.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a><?php endforeach ?><a href="faq.php">Câu hỏi thường gặp</a><a href="contact.php">Liên hệ</a></nav><article class="prose"><?= e($page['body']??'Bạn có thể quay lại cửa hàng hoặc liên hệ với chúng tôi để được hỗ trợ.') ?></article></div>
<?php endif ?>
<?php include __DIR__ . '/../app/views/layout/footer.php' ?>
