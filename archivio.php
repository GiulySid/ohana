<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$groups = data_archive_by_year();

site_header("archivio", "Archivio", "Gli spettacoli di Ohana, stagione dopo stagione.");
?>
<section class="split-hero" data-split-hero>
  <div class="split-hero__pin">
    <div class="split-hero__split">
      <div class="split-hero__half split-hero__half--left">
        <div class="split-hero__text">
          <p class="kicker">Palco</p>
          <h1>Archivio</h1>
          <p>Una linea nel tempo. Ogni titolo è una sera in cui le luci si sono accese.</p>
        </div>
      </div>
      <div class="split-hero__half split-hero__half--right" aria-hidden="true">
        <div class="split-hero__text">
          <p class="kicker">Palco</p>
          <h1>Archivio</h1>
          <p>Una linea nel tempo. Ogni titolo è una sera in cui le luci si sono accese.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="split-hero__cast">
    <?php if (!$groups) { ?>
      <p class="empty">L’archivio è ancora in attesa. I primi spettacoli arriveranno da qui.</p>
    <?php } else { ?>
      <div class="timeline">
        <?php foreach ($groups as $year => $shows) { ?>
          <section class="year-block">
            <h2><?= data_h((string) $year) ?></h2>
            <div class="year-shows">
              <?php foreach ($shows as $show) {
                  site_show_card($show);
              } ?>
            </div>
          </section>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
</section>
<?php
site_footer();
