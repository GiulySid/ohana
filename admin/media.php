<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

$items = data_media_items(true);
$editingId = trim((string) ($_GET["id"] ?? $_POST["id"] ?? ""));
$item = [
    "id" => "",
    "titolo" => "",
    "tipo" => "youtube",
    "url" => "",
    "cover" => "",
    "files" => [],
    "hidden" => false,
];
$editing = false;
if ($editingId !== "") {
    $found = data_media_item($editingId, true);
    if ($found) {
        $item = array_merge($item, $found);
        $editing = true;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_csrf_check();
    $action = (string) ($_POST["action"] ?? "save");

    if ($action === "delete") {
        $keep = [];
        foreach ($items as $row) {
            if (($row["id"] ?? "") === $editingId) {
                data_delete_rel($row["cover"] ?? "");
                foreach ($row["files"] ?? [] as $file) {
                    data_delete_rel($file);
                }
                continue;
            }
            $keep[] = $row;
        }
        data_write("media", ["items" => array_values($keep)]);
        admin_flash("success", "Elemento media eliminato.");
        header("Location: media.php");
        exit;
    }

    admin_reject_if_post_too_large("media.php" . ($editing ? "?id=" . rawurlencode($editingId) : ""));
    $item["titolo"] = trim((string) ($_POST["titolo"] ?? ""));
    $item["tipo"] = (($_POST["tipo"] ?? "") === "gallery") ? "gallery" : "youtube";
    $item["url"] = trim((string) ($_POST["url"] ?? ""));
    $item["hidden"] = !empty($_POST["hidden"]);

    $error = null;
    if ($item["titolo"] === "") {
        $error = "Il titolo è obbligatorio.";
    } elseif ($item["tipo"] === "youtube" && data_youtube_id($item["url"]) === "") {
        $error = "Incolla un URL YouTube valido.";
    }

    if (!$error && !$editing) {
        $used = array_column($items, "id");
        $newId = data_unique_id($used, $item["titolo"]);
        if ($newId === null) {
            $error = "Impossibile creare l’id.";
        } else {
            $item["id"] = $newId;
        }
    }

    $dir = "media/gallery/" . ($item["id"] ?: "album");

    if (!$error) {
        if (!empty($_POST["delete_cover"])) {
            data_delete_rel($item["cover"]);
            $item["cover"] = "";
        }
        [$image, $uploadError] = admin_store_upload($_FILES["cover"] ?? null, $dir, ($item["id"] ?: "cover") . "-cover");
        if ($uploadError) {
            $error = $uploadError;
        } elseif ($image) {
            data_delete_rel($item["cover"]);
            $item["cover"] = $image["thumb"] ?: $image["src"];
        }
    }

    if (!$error && $item["tipo"] === "gallery") {
        $deleteFiles = $_POST["delete_files"] ?? [];
        if (is_array($deleteFiles)) {
            $keep = [];
            foreach ($item["files"] as $file) {
                if (in_array($file, $deleteFiles, true)) {
                    data_delete_rel($file);
                    continue;
                }
                $keep[] = $file;
            }
            $item["files"] = $keep;
        }
        [$added, $galleryError] = admin_store_multi($_FILES["files"] ?? null, $dir, $item["id"] . "-photo");
        if ($galleryError) {
            $error = $galleryError;
        } else {
            $item["files"] = array_values(array_merge($item["files"], $added));
        }
    } else {
        $item["files"] = [];
    }

    if (!$error) {
        $saved = false;
        foreach ($items as $index => $row) {
            if (($row["id"] ?? "") === $item["id"]) {
                $items[$index] = $item;
                $saved = true;
                break;
            }
        }
        if (!$saved) {
            $items[] = $item;
        }
        if (!data_write("media", ["items" => array_values($items)])) {
            $error = "Impossibile scrivere media.json.";
        }
    }

    if ($error) {
        admin_flash("error", $error);
    } else {
        admin_flash("success", "Media salvato.");
        header("Location: media.php?id=" . rawurlencode($item["id"]));
        exit;
    }
}

ob_start();
?>
<a class="admin-back" href="index.php">← Contenuti</a>
<div class="admin-toolbar">
  <div>
    <p class="admin-kicker">Media</p>
    <h1 class="admin-title"><?= $editing ? "Modifica" : "Foto e video" ?></h1>
  </div>
  <?php if ($editing) { ?>
    <a class="admin-btn-ghost" href="media.php">Nuovo</a>
  <?php } ?>
</div>

<?php foreach (data_media_items(true) as $row) { ?>
  <article class="admin-item">
    <div>
      <h2><?= admin_h($row["titolo"] ?? $row["id"]) ?><?php if (!empty($row["hidden"])) { ?> <span class="admin-badge">Nascosto</span><?php } ?></h2>
      <p><?= ($row["tipo"] ?? "") === "gallery" ? "Gallery" : "YouTube" ?></p>
    </div>
    <a class="admin-btn-ghost" href="media.php?id=<?= admin_h($row["id"]) ?>">Modifica</a>
  </article>
<?php } ?>

<form class="admin-form" method="post" enctype="multipart/form-data" style="margin-top:2rem">
  <?= admin_csrf_field() ?>
  <?php if ($editing) { ?>
    <input type="hidden" name="id" value="<?= admin_h($item["id"]) ?>">
  <?php } ?>
  <label class="admin-field">
    <span>Titolo</span>
    <input type="text" name="titolo" required value="<?= admin_h($item["titolo"]) ?>">
  </label>
  <label class="admin-field">
    <span>Tipo</span>
    <select name="tipo">
      <option value="youtube"<?= $item["tipo"] === "youtube" ? " selected" : "" ?>>Video YouTube</option>
      <option value="gallery"<?= $item["tipo"] === "gallery" ? " selected" : "" ?>>Gallery foto (su Aruba)</option>
    </select>
  </label>
  <label class="admin-field">
    <span>URL YouTube</span>
    <input type="url" name="url" value="<?= admin_h($item["url"]) ?>">
  </label>
  <div class="admin-field">
    <span>Cover</span>
    <?php $cover = data_asset($item["cover"]); if ($cover) { ?>
      <img class="admin-thumb" src="../<?= admin_h($cover) ?>" alt="">
      <label class="admin-check"><input type="checkbox" name="delete_cover" value="1"> Elimina cover</label>
    <?php } ?>
    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/gif">
  </div>
  <div class="admin-field">
    <span>Foto gallery</span>
    <?php if (!empty($item["files"])) { ?>
      <div class="admin-media-list">
        <?php foreach ($item["files"] as $file) {
            $src = data_asset($file);
            if (!$src) continue;
            ?>
          <label>
            <img src="../<?= admin_h($src) ?>" alt="">
            <input type="checkbox" name="delete_files[]" value="<?= admin_h($file) ?>"> elimina
          </label>
        <?php } ?>
      </div>
    <?php } ?>
    <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
  </div>
  <label class="admin-check">
    <input type="checkbox" name="hidden" value="1"<?= !empty($item["hidden"]) ? " checked" : "" ?>>
    Nascosto
  </label>
  <div class="admin-actions">
    <button class="admin-btn" type="submit" name="action" value="save">Salva</button>
    <?php if ($editing) { ?>
      <button class="admin-btn-ghost" type="submit" name="action" value="delete" data-confirm="Eliminare questo elemento media?">Elimina</button>
    <?php } ?>
  </div>
</form>
<?php
admin_layout("Media", ob_get_clean(), ["nav" => true, "wide" => true]);
