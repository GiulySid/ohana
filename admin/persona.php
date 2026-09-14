<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

$id = trim((string) ($_GET["id"] ?? $_POST["id"] ?? ""));
$people = data_people(true);
$person = [
    "id" => "",
    "nome" => "",
    "ruoli" => [],
    "attivoDal" => "",
    "attivoAl" => "",
    "bio" => "",
    "foto" => "",
    "fotoThumb" => "",
    "hidden" => false,
];
$editing = $id !== "";
if ($editing) {
    $found = data_person($id, true);
    if (!$found) {
        admin_flash("error", "Persona non trovata.");
        header("Location: compagnia.php");
        exit;
    }
    $person = array_merge($person, $found);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_reject_if_post_too_large("persona.php" . ($editing ? "?id=" . rawurlencode($id) : ""));
    admin_csrf_check();

    $person["nome"] = trim((string) ($_POST["nome"] ?? ""));
    $person["ruoli"] = array_values(array_intersect(array_keys(PERSON_ROLES), (array) ($_POST["ruoli"] ?? [])));
    $person["attivoDal"] = trim((string) ($_POST["attivoDal"] ?? ""));
    $person["attivoAl"] = trim((string) ($_POST["attivoAl"] ?? ""));
    $person["bio"] = trim((string) ($_POST["bio"] ?? ""));
    $person["hidden"] = !empty($_POST["hidden"]);

    $error = null;
    if ($person["nome"] === "") {
        $error = "Il nome è obbligatorio.";
    }

    if (!$error && !$editing) {
        $used = array_column($people, "id");
        $newId = data_unique_id($used, $person["nome"]);
        if ($newId === null) {
            $error = "Impossibile creare l’id.";
        } else {
            $person["id"] = $newId;
            $id = $newId;
        }
    }

    if (!$error) {
        if (!empty($_POST["delete_foto"])) {
            data_delete_rel($person["foto"]);
            data_delete_rel($person["fotoThumb"]);
            $person["foto"] = "";
            $person["fotoThumb"] = "";
        }
        [$image, $uploadError] = admin_store_upload($_FILES["foto"] ?? null, "media/compagnia", $person["id"]);
        if ($uploadError) {
            $error = $uploadError;
        } elseif ($image) {
            data_delete_rel($person["foto"]);
            data_delete_rel($person["fotoThumb"]);
            $person["foto"] = $image["src"];
            $person["fotoThumb"] = $image["thumb"];
        }
    }

    if (!$error) {
        $saved = false;
        foreach ($people as $index => $row) {
            if (($row["id"] ?? "") === $person["id"]) {
                $people[$index] = $person;
                $saved = true;
                break;
            }
        }
        if (!$saved) {
            $people[] = $person;
        }
        if (!data_write("compagnia", ["people" => array_values($people)])) {
            $error = "Impossibile scrivere compagnia.json. Controlla i permessi della cartella data/.";
        }
    }

    if ($error) {
        admin_flash("error", $error);
    } else {
        admin_flash("success", "Profilo salvato.");
        header("Location: persona.php?id=" . rawurlencode($person["id"]));
        exit;
    }
}

$thumb = data_asset($person["fotoThumb"] ?: $person["foto"]);

ob_start();
?>
<a class="admin-back" href="compagnia.php">← Persone</a>
<p class="admin-kicker">Compagnia</p>
<h1 class="admin-title"><?= $editing ? admin_h($person["nome"] ?: "Modifica") : "Nuova persona" ?></h1>
<form class="admin-form" method="post" enctype="multipart/form-data">
  <?= admin_csrf_field() ?>
  <?php if ($editing) { ?>
    <input type="hidden" name="id" value="<?= admin_h($person["id"]) ?>">
  <?php } ?>
  <label class="admin-field">
    <span>Nome</span>
    <input type="text" name="nome" required value="<?= admin_h($person["nome"]) ?>">
  </label>
  <fieldset class="admin-field">
    <span>Ruoli</span>
    <div class="admin-roles">
      <?php foreach (PERSON_ROLES as $key => $label) { ?>
        <label class="admin-check">
          <input type="checkbox" name="ruoli[]" value="<?= admin_h($key) ?>"<?= in_array($key, $person["ruoli"], true) ? " checked" : "" ?>>
          <?= admin_h($label) ?>
        </label>
      <?php } ?>
    </div>
  </fieldset>
  <label class="admin-field">
    <span>Attivo dal (anno)</span>
    <input type="text" name="attivoDal" inputmode="numeric" placeholder="2019" value="<?= admin_h($person["attivoDal"]) ?>">
  </label>
  <label class="admin-field">
    <span>Attivo fino a</span>
    <input type="text" name="attivoAl" inputmode="numeric" placeholder="Lascia vuoto se è ancora in compagnia" value="<?= admin_h($person["attivoAl"]) ?>">
    <small class="admin-help">Se il campo “fino a” è vuoto, la persona risulta attiva.</small>
  </label>
  <label class="admin-field">
    <span>Bio / curriculum</span>
    <textarea name="bio"><?= admin_h($person["bio"]) ?></textarea>
  </label>
  <div class="admin-field">
    <span>Foto</span>
    <?php if ($thumb) { ?>
      <img class="admin-thumb" src="../<?= admin_h($thumb) ?>" alt="">
      <label class="admin-check"><input type="checkbox" name="delete_foto" value="1"> Elimina foto</label>
    <?php } ?>
    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
    <small class="admin-help">JPG/PNG. Verrà ridimensionata e convertita in WebP, con miniatura per le liste. <?= admin_h(admin_upload_limits_note()) ?></small>
  </div>
  <label class="admin-check">
    <input type="checkbox" name="hidden" value="1"<?= !empty($person["hidden"]) ? " checked" : "" ?>>
    Nascosto dal sito pubblico
  </label>
  <div class="admin-actions">
    <button class="admin-btn" type="submit">Salva</button>
  </div>
</form>
<?php
admin_layout($editing ? "Modifica persona" : "Nuova persona", ob_get_clean(), ["nav" => true]);
