<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$groups = data_archive_by_year();
$upcoming = array_values(array_filter(data_shows(), static function ($show) {
    return ($show["stato"] ?? "") === "prossimo";
}));

site_header("archivio", "Archivio", "Gli spettacoli di Ohana, stagione dopo stagione.");
?>
<header class="page-head">
  <p class="kicker">Palco</p>
  <h1>Archivio</h1>
  <p>Una linea nel tempo. Ogni titolo è una sera in cui le luci si sono accese.</p>
</header>

<?php if ($upcoming) { ?>
  <section class="section" style="padding-top:0">
    <p class="kicker">In cartellone</p>
    <?php foreach ($upcoming as $show) {
        site_show_card($show);
    } ?>
  </section>
<?php } ?>

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
<?php
site_footer();
