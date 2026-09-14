<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$id = (string) ($_GET["id"] ?? "");
$show = $id !== "" ? data_show($id) : null;
if (!$show) {
    http_response_code(404);
    site_header("archivio", "Non trovato");
    echo '<header class="page-head"><h1>Fuori cartellone</h1><p>Questo spettacolo non è in archivio.</p><a class="btn" href="archivio" data-nav>Torna all’archivio</a></header>';
    site_footer();
    exit;
}

$poster = data_asset($show["locandina"] ?? "") ?: data_asset($show["locandinaThumb"] ?? "");
$people = data_person_map();
$embed = data_youtube_embed($show["trailerYoutube"] ?? "");
$gallery = array_values(array_filter($show["gallery"] ?? [], static function ($file) {
    return data_asset($file) !== "";
}));

site_header("archivio", $show["titolo"], $show["sinossi"] ?: $show["titolo"]);
?>
<header class="detail-hero">
  <p class="kicker"><?= data_h(($show["stato"] ?? "") === "prossimo" ? "Prossimo spettacolo" : (string) ($show["stagione"] ?: $show["anno"])) ?></p>
  <h1><?= data_h($show["titolo"]) ?></h1>
</header>

<div class="detail-grid">
  <div class="poster-frame">
    <?php if ($poster) { ?>
      <img src="<?= data_h($poster) ?>" alt="<?= data_h($show["titolo"]) ?>">
    <?php } else { ?>
      <span><?= data_h(data_first_letter($show["titolo"])) ?></span>
    <?php } ?>
  </div>
  <div>
    <?php if (!empty($show["sinossi"])) { ?>
      <div class="prose">
        <p><?= nl2br(data_h($show["sinossi"])) ?></p>
      </div>
    <?php } ?>

    <?php if (!empty($show["repliche"])) { ?>
      <h2 class="section-title">Date</h2>
      <ul class="meta-list">
        <?php foreach ($show["repliche"] as $replica) {
            $place = trim(($replica["luogo"] ?? "") . (empty($replica["citta"]) ? "" : ", " . $replica["citta"]), ", ");
            ?>
          <li class="replica">
            <strong><?= data_h(data_format_date($replica["data"] ?? "")) ?></strong>
            <?php if (!empty($replica["ora"])) { ?> — <?= data_h($replica["ora"]) ?><?php } ?>
            <?php if ($place !== "") { ?><br><?= data_h($place) ?><?php } ?>
            <?php if (!empty($replica["biglietti"])) { ?>
              <br><a href="<?= data_h($replica["biglietti"]) ?>" target="_blank" rel="noopener noreferrer">Biglietti</a>
            <?php } ?>
          </li>
        <?php } ?>
      </ul>
    <?php } ?>

    <?php if ($embed) { ?>
      <h2 class="section-title">Trailer</h2>
      <div class="video-frame">
        <iframe src="<?= data_h($embed) ?>" title="Trailer <?= data_h($show["titolo"]) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
      </div>
    <?php } ?>

    <?php
    $groups = [
        "cast" => "Cast",
        "staff" => "Compagnia tecnica",
    ];
    foreach ($groups as $key => $label) {
        $rows = array_values(array_filter($show[$key] ?? [], static function ($row) use ($people) {
            return isset($people[$row["personId"] ?? ""]);
        }));
        if (!$rows) {
            continue;
        }
        ?>
      <h2 class="section-title"><?= data_h($label) ?></h2>
      <div class="people-grid people-grid--nested">
        <?php foreach ($rows as $row) {
            $person = $people[$row["personId"]];
            $credit = trim((string) ($row["ruoloInScena"] ?? $row["credito"] ?? ""));
            echo '<div>';
            site_person_card($person);
            if ($credit !== "") {
                echo '<p class="muted">' . data_h($credit) . "</p>";
            }
            echo "</div>";
        } ?>
      </div>
    <?php } ?>

    <?php if ($gallery) { ?>
      <h2 class="section-title">Gallery</h2>
      <div class="gallery">
        <?php foreach ($gallery as $file) { ?>
          <img src="<?= data_h(data_asset($file)) ?>" alt="" loading="lazy">
        <?php } ?>
      </div>
    <?php } ?>
  </div>
</div>
<?php
site_footer();
