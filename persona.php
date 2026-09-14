<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$id = (string) ($_GET["id"] ?? "");
$person = $id !== "" ? data_person($id) : null;
if (!$person) {
    http_response_code(404);
    site_header("compagnia", "Non trovato");
    echo '<header class="page-head"><h1>Non in scena</h1><p>Questa persona non è in compagnia, o la pagina non esiste.</p><a class="btn" href="compagnia.php" data-nav>Torna alla compagnia</a></header>';
    site_footer();
    exit;
}

$photo = data_asset($person["foto"] ?? "") ?: data_asset($person["fotoThumb"] ?? "");
$cv = data_person_curriculum($person["id"]);
$period = data_person_period($person);

site_header("compagnia", $person["nome"], $person["bio"] ?: $person["nome"]);
?>
<header class="detail-hero">
  <p class="kicker"><?= data_h(implode(" · ", data_role_labels($person["ruoli"] ?? []))) ?></p>
  <h1><?= data_h($person["nome"]) ?></h1>
  <?php if ($period !== "") { ?>
    <p class="lead"><?= data_h($period) ?><?= data_person_is_active($person) ? "" : " · non più in compagnia" ?></p>
  <?php } ?>
</header>

<div class="detail-grid">
  <div class="portrait-frame">
    <?php if ($photo) { ?>
      <img src="<?= data_h($photo) ?>" alt="<?= data_h($person["nome"]) ?>">
    <?php } else { ?>
      <span><?= data_h(data_initials($person["nome"])) ?></span>
    <?php } ?>
  </div>
  <div>
    <?php if (!empty($person["bio"])) { ?>
      <div class="prose">
        <p><?= nl2br(data_h($person["bio"])) ?></p>
      </div>
    <?php } ?>

    <h2 class="section-title">In compagnia</h2>
    <?php if (!$cv) { ?>
      <p class="muted">Ancora nessuno spettacolo collegato.</p>
    <?php } else { ?>
      <div class="cv-list">
        <?php foreach ($cv as $item) {
            $show = $item["show"];
            ?>
          <a href="spettacolo.php?id=<?= data_h($show["id"]) ?>" data-nav>
            <div>
              <h3><?= data_h($show["titolo"]) ?></h3>
              <p class="muted"><?= data_h($show["stagione"] ?: (string) $show["anno"]) ?></p>
            </div>
            <p><?= data_h($item["credit"]) ?></p>
          </a>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
</div>
<?php
site_footer();
