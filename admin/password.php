<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_csrf_check();
    $current = (string) ($_POST["current"] ?? "");
    $password = (string) ($_POST["password"] ?? "");
    $confirm = (string) ($_POST["confirm"] ?? "");
    $saved = admin_credentials();

    if (!$saved || !password_verify($current, (string) $saved["password_hash"])) {
        $error = "La password attuale non è corretta.";
    } elseif (strlen($password) < 8) {
        $error = "Usa una password di almeno 8 caratteri.";
    } elseif (!hash_equals($password, $confirm)) {
        $error = "Le due nuove password non coincidono.";
    } elseif (!admin_write_credentials((string) $saved["username"], $password)) {
        $error = "Impossibile salvare la nuova password.";
    } else {
        session_regenerate_id(true);
        admin_flash("success", "Password aggiornata.");
        header("Location: index.php");
        exit;
    }
}

ob_start();
?>
<a class="admin-back" href="index.php">← Contenuti</a>
<p class="admin-kicker">Account</p>
<h1 class="admin-title">Cambia password</h1>
<p class="admin-lead">Lo username resta <?= admin_h($_SESSION["admin_user"] ?? "") ?>. Cambia solo la password.</p>
<?php if ($error) { ?>
  <p class="admin-flash admin-flash--error" role="alert"><?= admin_h($error) ?></p>
<?php } ?>
<form class="admin-form" method="post">
  <?= admin_csrf_field() ?>
  <label class="admin-field">
    <span>Password attuale</span>
    <input type="password" name="current" required autocomplete="current-password">
  </label>
  <label class="admin-field">
    <span>Nuova password</span>
    <input type="password" name="password" required minlength="8" autocomplete="new-password">
  </label>
  <label class="admin-field">
    <span>Conferma nuova password</span>
    <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
  </label>
  <div class="admin-actions">
    <button class="admin-btn" type="submit">Aggiorna password</button>
  </div>
</form>
<?php
admin_layout("Password", ob_get_clean(), ["nav" => true]);
