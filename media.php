<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$photos = data_media_photos();
$first = array_slice($photos, 0, 6);
$rest = array_slice($photos, 6);
$shot_kinds = ["wide", "offset", "tall"];

site_header("media", "Media", "Foto di Ohana Musical Company.");
?>
<header class="page-head">
  <p class="kicker">Scatti</p>
  <h1>Media</h1>
  <p>Le fotografie della compagnia, in un flusso irregolare.</p>
</header>

<?php if (!$photos) { ?>
  <p class="empty">Ancora nessuna fotografia. Arriveranno da qui.</p>
<?php } else { ?>
  <div class="media-stream" data-media-stream>
    <?php foreach ($first as $index => $src) { ?>
      <figure class="media-shot media-shot--<?= $shot_kinds[$index % 3] ?>">
        <img
          src="<?= data_h($src) ?>"
          alt=""
          decoding="async"
          <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
        >
      </figure>
    <?php } ?>
  </div>
  <?php if ($rest) { ?>
    <div class="media-sentinel" data-media-sentinel aria-hidden="true"></div>
    <script type="application/json" id="media-queue"><?= json_encode($rest, JSON_UNESCAPED_SLASHES) ?></script>
  <?php } ?>
<?php } ?>
<?php
site_footer();
