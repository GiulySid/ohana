<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

$id = trim((string) ($_GET["id"] ?? $_POST["id"] ?? ""));
$shows = data_shows(true);
$people = data_people(true);
$show = [
    "id" => "",
    "titolo" => "",
    "stagione" => "",
    "anno" => (int) date("Y"),
    "stato" => "archivio",
    "sinossi" => "",
    "locandina" => "",
    "locandinaThumb" => "",
    "trailerYoutube" => "",
    "repliche" => [],
    "cast" => [],
    "staff" => [],
    "gallery" => [],
    "hidden" => false,
];
$editing = $id !== "";
if ($editing) {
    $found = data_show($id, true);
    if (!$found) {
        admin_flash("error", "Spettacolo non trovato.");
        header("Location: spettacoli.php");
        exit;
    }
    $show = array_merge($show, $found);
}

function admin_person_options($people, $selected = "") {
    $html = '<option value="">— persona —</option>';
    foreach ($people as $person) {
        $sel = ($person["id"] ?? "") === $selected ? " selected" : "";
        $html .= '<option value="' . admin_h($person["id"]) . '"' . $sel . ">" . admin_h($person["nome"]) . "</option>";
    }
    return $html;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_reject_if_post_too_large("spettacolo.php" . ($editing ? "?id=" . rawurlencode($id) : ""));
    admin_csrf_check();

    $show["titolo"] = trim((string) ($_POST["titolo"] ?? ""));
    $show["stagione"] = trim((string) ($_POST["stagione"] ?? ""));
    $show["anno"] = (int) ($_POST["anno"] ?? date("Y"));
    $show["stato"] = (($_POST["stato"] ?? "") === "prossimo") ? "prossimo" : "archivio";
    $show["sinossi"] = trim((string) ($_POST["sinossi"] ?? ""));
    $show["trailerYoutube"] = trim((string) ($_POST["trailerYoutube"] ?? ""));
    $show["hidden"] = !empty($_POST["hidden"]);
    $show["repliche"] = admin_collect_rows("replica_data", [
        "data" => "replica_data",
        "ora" => "replica_ora",
        "luogo" => "replica_luogo",
        "citta" => "replica_citta",
        "biglietti" => "replica_biglietti",
    ]);
    $show["cast"] = array_values(array_filter(admin_collect_rows("cast_person", [
        "personId" => "cast_person",
        "ruoloInScena" => "cast_role",
    ]), static function ($row) {
        return ($row["personId"] ?? "") !== "";
    }));
    $show["staff"] = array_values(array_filter(admin_collect_rows("staff_person", [
        "personId" => "staff_person",
        "credito" => "staff_credit",
    ]), static function ($row) {
        return ($row["personId"] ?? "") !== "";
    }));

    $error = null;
    if ($show["titolo"] === "") {
        $error = "Il titolo è obbligatorio.";
    } elseif ($show["trailerYoutube"] !== "" && data_youtube_id($show["trailerYoutube"]) === "") {
        $error = "Il trailer deve essere un URL YouTube valido.";
    }

    if (!$error && !$editing) {
        $used = array_column($shows, "id");
        $newId = data_unique_id($used, $show["titolo"]);
        if ($newId === null) {
            $error = "Impossibile creare l’id.";
        } else {
            $show["id"] = $newId;
            $id = $newId;
        }
    }

    $dir = "media/spettacoli/" . $show["id"];

    if (!$error) {
        if (!empty($_POST["delete_locandina"])) {
            data_delete_rel($show["locandina"]);
            data_delete_rel($show["locandinaThumb"]);
            $show["locandina"] = "";
            $show["locandinaThumb"] = "";
        }
        [$image, $uploadError] = admin_store_upload($_FILES["locandina"] ?? null, $dir, $show["id"] . "-locandina");
        if ($uploadError) {
            $error = $uploadError;
        } elseif ($image) {
            data_delete_rel($show["locandina"]);
            data_delete_rel($show["locandinaThumb"]);
            $show["locandina"] = $image["src"];
            $show["locandinaThumb"] = $image["thumb"];
        }
    }

    if (!$error) {
        $deleteGallery = $_POST["delete_gallery"] ?? [];
        if (is_array($deleteGallery)) {
            $keep = [];
            foreach ($show["gallery"] as $file) {
                if (in_array($file, $deleteGallery, true)) {
                    data_delete_rel($file);
                    continue;
                }
                $keep[] = $file;
            }
            $show["gallery"] = $keep;
        }
        [$added, $galleryError] = admin_store_multi($_FILES["gallery"] ?? null, $dir, $show["id"] . "-gallery");
        if ($galleryError) {
            $error = $galleryError;
        } else {
            $show["gallery"] = array_values(array_merge($show["gallery"], $added));
        }
    }

    if (!$error) {
        $saved = false;
        foreach ($shows as $index => $row) {
            if (($row["id"] ?? "") === $show["id"]) {
                $shows[$index] = $show;
                $saved = true;
                break;
            }
        }
        if (!$saved) {
            $shows[] = $show;
        }
        if (!data_write("spettacoli", ["shows" => array_values($shows)])) {
            $error = "Impossibile scrivere spettacoli.json.";
        }
    }

    if ($error) {
        admin_flash("error", $error);
    } else {
        admin_flash("success", "Spettacolo salvato.");
        header("Location: spettacolo.php?id=" . rawurlencode($show["id"]));
        exit;
    }
}

$poster = data_asset($show["locandinaThumb"] ?: $show["locandina"]);
$optionsEmpty = admin_person_options($people);

ob_start();
?>
<a class="admin-back" href="spettacoli.php">← Spettacoli</a>
<p class="admin-kicker">Palco</p>
<h1 class="admin-title"><?= $editing ? admin_h($show["titolo"] ?: "Modifica") : "Nuovo spettacolo" ?></h1>
<form class="admin-form" method="post" enctype="multipart/form-data">
  <?= admin_csrf_field() ?>
  <?php if ($editing) { ?>
    <input type="hidden" name="id" value="<?= admin_h($show["id"]) ?>">
  <?php } ?>
  <label class="admin-field">
    <span>Titolo</span>
    <input type="text" name="titolo" required value="<?= admin_h($show["titolo"]) ?>">
  </label>
  <label class="admin-field">
    <span>Stagione</span>
    <input type="text" name="stagione" placeholder="2026/2027" value="<?= admin_h($show["stagione"]) ?>">
  </label>
  <label class="admin-field">
    <span>Anno</span>
    <input type="number" name="anno" required value="<?= admin_h((string) $show["anno"]) ?>">
  </label>
  <label class="admin-field">
    <span>Stato</span>
    <select name="stato">
      <option value="prossimo"<?= $show["stato"] === "prossimo" ? " selected" : "" ?>>In cartellone</option>
      <option value="archivio"<?= $show["stato"] !== "prossimo" ? " selected" : "" ?>>Archivio</option>
    </select>
  </label>
  <label class="admin-field">
    <span>Sinossi</span>
    <textarea name="sinossi"><?= admin_h($show["sinossi"]) ?></textarea>
  </label>
  <label class="admin-field">
    <span>Trailer YouTube</span>
    <input type="url" name="trailerYoutube" placeholder="https://www.youtube.com/watch?v=..." value="<?= admin_h($show["trailerYoutube"]) ?>">
  </label>

  <div class="admin-field">
    <span>Locandina</span>
    <?php if ($poster) { ?>
      <img class="admin-thumb" src="../<?= admin_h($poster) ?>" alt="">
      <label class="admin-check"><input type="checkbox" name="delete_locandina" value="1"> Elimina locandina</label>
    <?php } ?>
    <input type="file" name="locandina" accept="image/jpeg,image/png,image/webp,image/gif">
  </div>

  <div class="admin-field">
    <span>Date / repliche</span>
    <div class="admin-rows" id="replica-rows">
      <?php
      $repliche = $show["repliche"] ?: [["data" => "", "ora" => "", "luogo" => "", "citta" => "", "biglietti" => ""]];
      foreach ($repliche as $replica) { ?>
        <div class="admin-row" data-row>
          <input type="date" name="replica_data[]" value="<?= admin_h($replica["data"] ?? "") ?>">
          <input type="text" name="replica_ora[]" placeholder="21:00" value="<?= admin_h($replica["ora"] ?? "") ?>">
          <input type="text" name="replica_luogo[]" placeholder="Teatro" value="<?= admin_h($replica["luogo"] ?? "") ?>">
          <input type="text" name="replica_citta[]" placeholder="Città" value="<?= admin_h($replica["citta"] ?? "") ?>">
          <input type="url" name="replica_biglietti[]" placeholder="Link biglietti" value="<?= admin_h($replica["biglietti"] ?? "") ?>">
          <button type="button" class="admin-btn-ghost" data-remove-row>Rimuovi</button>
        </div>
      <?php } ?>
    </div>
    <button type="button" class="admin-btn-ghost" data-add-row="#replica-rows" data-template="#replica-tpl">Aggiungi data</button>
  </div>

  <div class="admin-field">
    <span>Cast</span>
    <div class="admin-rows" id="cast-rows">
      <?php
      $cast = $show["cast"] ?: [["personId" => "", "ruoloInScena" => ""]];
      foreach ($cast as $row) { ?>
        <div class="admin-row" data-row>
          <select name="cast_person[]"><?= admin_person_options($people, $row["personId"] ?? "") ?></select>
          <input type="text" name="cast_role[]" placeholder="Ruolo in scena" value="<?= admin_h($row["ruoloInScena"] ?? "") ?>">
          <button type="button" class="admin-btn-ghost" data-remove-row>Rimuovi</button>
        </div>
      <?php } ?>
    </div>
    <button type="button" class="admin-btn-ghost" data-add-row="#cast-rows" data-template="#cast-tpl">Aggiungi al cast</button>
  </div>

  <div class="admin-field">
    <span>Staff / tecnici / regia</span>
    <div class="admin-rows" id="staff-rows">
      <?php
      $staff = $show["staff"] ?: [["personId" => "", "credito" => ""]];
      foreach ($staff as $row) { ?>
        <div class="admin-row" data-row>
          <select name="staff_person[]"><?= admin_person_options($people, $row["personId"] ?? "") ?></select>
          <input type="text" name="staff_credit[]" placeholder="Regia, luci…" value="<?= admin_h($row["credito"] ?? "") ?>">
          <button type="button" class="admin-btn-ghost" data-remove-row>Rimuovi</button>
        </div>
      <?php } ?>
    </div>
    <button type="button" class="admin-btn-ghost" data-add-row="#staff-rows" data-template="#staff-tpl">Aggiungi staff</button>
  </div>

  <div class="admin-field">
    <span>Gallery</span>
    <?php if (!empty($show["gallery"])) { ?>
      <div class="admin-media-list">
        <?php foreach ($show["gallery"] as $file) {
            $src = data_asset($file);
            if (!$src) {
                continue;
            }
            ?>
          <label>
            <img src="../<?= admin_h($src) ?>" alt="">
            <input type="checkbox" name="delete_gallery[]" value="<?= admin_h($file) ?>"> elimina
          </label>
        <?php } ?>
      </div>
    <?php } ?>
    <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
    <small class="admin-help">Le foto restano su Aruba, ridimensionate. I video lunghi vanno su YouTube. <?= admin_h(admin_upload_limits_note()) ?></small>
  </div>

  <label class="admin-check">
    <input type="checkbox" name="hidden" value="1"<?= !empty($show["hidden"]) ? " checked" : "" ?>>
    Nascosto dal sito pubblico
  </label>
  <div class="admin-actions">
    <button class="admin-btn" type="submit">Salva</button>
  </div>
</form>

<template id="replica-tpl">
  <div class="admin-row" data-row>
    <input type="date" name="replica_data[]">
    <input type="text" name="replica_ora[]" placeholder="21:00">
    <input type="text" name="replica_luogo[]" placeholder="Teatro">
    <input type="text" name="replica_citta[]" placeholder="Città">
    <input type="url" name="replica_biglietti[]" placeholder="Link biglietti">
    <button type="button" class="admin-btn-ghost" data-remove-row>Rimuovi</button>
  </div>
</template>
<template id="cast-tpl">
  <div class="admin-row" data-row>
    <select name="cast_person[]"><?= $optionsEmpty ?></select>
    <input type="text" name="cast_role[]" placeholder="Ruolo in scena">
    <button type="button" class="admin-btn-ghost" data-remove-row>Rimuovi</button>
  </div>
</template>
<template id="staff-tpl">
  <div class="admin-row" data-row>
    <select name="staff_person[]"><?= $optionsEmpty ?></select>
    <input type="text" name="staff_credit[]" placeholder="Regia, luci…">
    <button type="button" class="admin-btn-ghost" data-remove-row>Rimuovi</button>
  </div>
</template>
<?php
admin_layout($editing ? "Modifica spettacolo" : "Nuovo spettacolo", ob_get_clean(), ["nav" => true, "wide" => true]);
