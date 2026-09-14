<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_csrf_check();
    $id = (string) ($_POST["id"] ?? "");
    $action = (string) ($_POST["action"] ?? "");
    $shows = data_shows(true);
    $index = null;
    foreach ($shows as $i => $show) {
        if (($show["id"] ?? "") === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        admin_flash("error", "Spettacolo non trovato.");
        header("Location: spettacoli.php");
        exit;
    }

    if ($action === "delete") {
        data_delete_rel($shows[$index]["locandina"] ?? "");
        data_delete_rel($shows[$index]["locandinaThumb"] ?? "");
        foreach ($shows[$index]["gallery"] ?? [] as $file) {
            data_delete_rel($file);
        }
        array_splice($shows, $index, 1);
        data_write("spettacoli", ["shows" => array_values($shows)]);
        admin_flash("success", "Spettacolo eliminato.");
    } elseif ($action === "hide" || $action === "show") {
        $shows[$index]["hidden"] = $action === "hide";
        data_write("spettacoli", ["shows" => array_values($shows)]);
        admin_flash("success", $action === "hide" ? "Nascosto dal sito." : "Di nuovo visibile.");
    }

    header("Location: spettacoli.php");
    exit;
}

$shows = data_shows(true);

ob_start();
?>
<a class="admin-back" href="index.php">← Contenuti</a>
<div class="admin-toolbar">
  <div>
    <p class="admin-kicker">Palco</p>
    <h1 class="admin-title">Spettacoli</h1>
  </div>
  <a class="admin-btn" href="spettacolo.php">Aggiungi</a>
</div>
<?php if (!$shows) { ?>
  <p class="admin-empty">Nessuno spettacolo. Aggiungi il primo titolo.</p>
<?php } ?>
<?php foreach ($shows as $show) { ?>
  <article class="admin-item">
    <div>
      <h2>
        <?= admin_h($show["titolo"] ?? $show["id"]) ?>
        <?php if (!empty($show["hidden"])) { ?><span class="admin-badge">Nascosto</span><?php } ?>
        <?php if (($show["stato"] ?? "") === "prossimo") { ?><span class="admin-badge">In cartellone</span><?php } ?>
      </h2>
      <p><?= admin_h(($show["stagione"] ?: (string) ($show["anno"] ?? "")) . " · " . count($show["repliche"] ?? []) . " date") ?></p>
    </div>
    <div class="admin-item__actions">
      <a class="admin-btn-ghost" href="spettacolo.php?id=<?= admin_h($show["id"]) ?>">Modifica</a>
      <form method="post">
        <?= admin_csrf_field() ?>
        <input type="hidden" name="id" value="<?= admin_h($show["id"]) ?>">
        <input type="hidden" name="action" value="<?= !empty($show["hidden"]) ? "show" : "hide" ?>">
        <button class="admin-btn-ghost" type="submit"><?= !empty($show["hidden"]) ? "Mostra" : "Nascondi" ?></button>
      </form>
      <form method="post" data-confirm="Eliminare questo spettacolo e le sue foto?">
        <?= admin_csrf_field() ?>
        <input type="hidden" name="id" value="<?= admin_h($show["id"]) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="admin-btn-ghost" type="submit">Elimina</button>
      </form>
    </div>
  </article>
<?php } ?>
<?php
admin_layout("Spettacoli", ob_get_clean(), ["nav" => true, "wide" => true]);
