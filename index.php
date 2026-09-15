<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "dispatch.php";
site_dispatch();

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$site = data_site();
$upcoming = data_upcoming_show();
$next = $upcoming ? data_next_replica($upcoming) : null;
$aboutPhoto = site_about_photo();
$showreel = site_showreel_src();
$curtain = site_curtain_src();
$name = $site["nome"] ?? "Ohana Musical Company";

$brand = site_brand();
site_header("home", $name, $site["tagline"] ?? "");
?>
<section class="home-stage" data-home-stage<?= $curtain === "" ? " data-home-open" : "" ?>>
  <div class="home-stage__pin">
    <div class="home-stage__showreel">
      <?php if ($showreel !== "") { ?>
        <video muted loop playsinline preload="auto" poster="<?= data_h($aboutPhoto ?: "gs.jpeg") ?>" data-showreel-video>
          <source src="<?= data_h($showreel) ?>">
        </video>
        <button class="home-stage__sound" type="button" data-showreel-sound aria-pressed="false">
          <span data-sound-label>Attiva audio</span>
        </button>
      <?php } ?>
    </div>

    <?php if ($curtain !== "") { ?>
      <div class="curtain" data-curtain aria-hidden="true">
        <div class="curtain__pane curtain__pane--left">
          <video class="curtain__video" muted loop playsinline autoplay preload="auto" data-curtain-video>
            <source src="<?= data_h($curtain) ?>" type="video/mp4">
          </video>
        </div>
        <div class="curtain__pane curtain__pane--right">
          <video class="curtain__video" muted loop playsinline autoplay preload="auto" data-curtain-follow>
            <source src="<?= data_h($curtain) ?>" type="video/mp4">
          </video>
        </div>
        <div class="curtain__shade"></div>
        <div class="curtain__logo">
          <img src="<?= data_h($brand["lockup"]) ?>" alt="<?= data_h($name) ?>">
        </div>
        <p class="curtain__hint">Scorri</p>
      </div>
    <?php } ?>
  </div>
</section>

<?php if ($upcoming || !empty($site["chiSiamo"])) { ?>
  <div class="home-follow">
    <?php if ($upcoming) {
      $guest = site_show_guest($upcoming);
      ?>
      <article class="playbill next-show<?= $guest !== "" ? " playbill--guest" : "" ?>">
        <p class="kicker">Prossimo spettacolo</p>
        <?php $titleMark = site_show_title_mark($upcoming); ?>
        <h2><?php if ($titleMark) { ?><img class="title-mark" src="<?= data_h($titleMark) ?>" alt="<?= data_h($upcoming["titolo"]) ?>"><?php } else { ?><?= data_h($upcoming["titolo"]) ?><?php } ?></h2>
        <?php if ($next) { ?>
          <p>
            <?= data_h(data_format_date($next["data"])) ?>
            <?php if (!empty($next["ora"])) { ?> — <?= data_h($next["ora"]) ?><?php } ?>
            <?php if (!empty($next["luogo"]) || !empty($next["citta"])) { ?>
              · <?= data_h(trim(($next["luogo"] ?? "") . (empty($next["citta"]) ? "" : ", " . $next["citta"]), ", ")) ?>
            <?php } ?>
          </p>
        <?php } elseif (!empty($upcoming["stagione"])) { ?>
          <p><?= data_h($upcoming["stagione"]) ?></p>
        <?php } ?>
        <a class="btn" href="spettacolo?id=<?= data_h($upcoming["id"]) ?>" data-nav>Scopri lo spettacolo</a>
      </article>
    <?php } ?>

    <?php if (!empty($site["chiSiamo"])) { ?>
      <section class="band band--house about-panel" data-about style="--about: 0">
        <div class="about-grid">
          <div>
            <p class="kicker">Chi siamo</p>
            <p class="lead"><?= data_h($site["chiSiamo"]) ?></p>
            <a class="btn" href="compagnia" data-nav>La compagnia</a>
          </div>
          <?php if ($aboutPhoto) { ?>
            <figure class="about-photo">
              <img src="<?= data_h($aboutPhoto) ?>" alt="" decoding="async">
            </figure>
          <?php } ?>
        </div>
      </section>
    <?php } ?>
  </div>
<?php } ?>
<?php
site_footer();
