<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

$site = data_site();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_csrf_check();
    $site["nome"] = trim((string) ($_POST["nome"] ?? ""));
    $site["tagline"] = trim((string) ($_POST["tagline"] ?? ""));
    $site["chiSiamo"] = trim((string) ($_POST["chiSiamo"] ?? ""));
    $site["email"] = trim((string) ($_POST["email"] ?? ""));
    $site["instagram"] = trim((string) ($_POST["instagram"] ?? ""));
    $site["facebook"] = trim((string) ($_POST["facebook"] ?? ""));
    $site["telefono"] = trim((string) ($_POST["telefono"] ?? ""));
    $site["indirizzo"] = trim((string) ($_POST["indirizzo"] ?? ""));
    $site["orariProve"] = trim((string) ($_POST["orariProve"] ?? ""));

    if ($site["nome"] === "") {
        admin_flash("error", "Il nome della compagnia è obbligatorio.");
    } elseif (!data_write("sito", $site)) {
        admin_flash("error", "Impossibile scrivere sito.json.");
    } else {
        admin_flash("success", "Testi del sito salvati.");
        header("Location: sito.php");
        exit;
    }
}

ob_start();
?>
<a class="admin-back" href="index.php">← Contenuti</a>
<p class="admin-kicker">Sito</p>
<h1 class="admin-title">Testi e contatti</h1>
<form class="admin-form" method="post">
  <?= admin_csrf_field() ?>
  <label class="admin-field">
    <span>Nome</span>
    <input type="text" name="nome" required value="<?= admin_h($site["nome"]) ?>">
  </label>
  <label class="admin-field">
    <span>Tagline</span>
    <input type="text" name="tagline" value="<?= admin_h($site["tagline"]) ?>">
  </label>
  <label class="admin-field">
    <span>Chi siamo</span>
    <textarea name="chiSiamo"><?= admin_h($site["chiSiamo"]) ?></textarea>
  </label>
  <label class="admin-field">
    <span>Email</span>
    <input type="email" name="email" value="<?= admin_h($site["email"]) ?>">
  </label>
  <label class="admin-field">
    <span>Telefono</span>
    <input type="text" name="telefono" value="<?= admin_h($site["telefono"]) ?>">
  </label>
  <label class="admin-field">
    <span>Instagram</span>
    <input type="url" name="instagram" value="<?= admin_h($site["instagram"]) ?>">
  </label>
  <label class="admin-field">
    <span>Facebook</span>
    <input type="url" name="facebook" value="<?= admin_h($site["facebook"]) ?>">
  </label>
  <label class="admin-field">
    <span>Indirizzo / sede</span>
    <input type="text" name="indirizzo" value="<?= admin_h($site["indirizzo"]) ?>">
  </label>
  <label class="admin-field">
    <span>Orari prove</span>
    <input type="text" name="orariProve" value="<?= admin_h($site["orariProve"]) ?>">
  </label>
  <div class="admin-actions">
    <button class="admin-btn" type="submit">Salva</button>
  </div>
</form>
<?php
admin_layout("Sito", ob_get_clean(), ["nav" => true]);
