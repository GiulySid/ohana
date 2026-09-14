<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();

if (admin_credentials()) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim((string) ($_POST["username"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $confirm = (string) ($_POST["confirm"] ?? "");

    if (!preg_match("/^[A-Za-z0-9._-]{2,32}$/", $username)) {
        $error = "Username: 2–32 lettere, numeri, punti o trattini.";
    } elseif (strlen($password) < 8) {
        $error = "Usa una password di almeno 8 caratteri.";
    } elseif (!hash_equals($password, $confirm)) {
        $error = "Le due password non coincidono.";
    } elseif (!admin_write_credentials($username, $password)) {
        $error = "Impossibile salvare le credenziali. La cartella admin deve essere scrivibile.";
    } else {
        $_SESSION["admin_ok"] = 1;
        $_SESSION["admin_user"] = $username;
        session_regenerate_id(true);
        admin_flash("success", "Accesso creato. Conserva la password.");
        header("Location: index.php");
        exit;
    }
}

ob_start();
?>
<div class="admin-auth">
  <div class="admin-auth__card">
    <p class="admin-kicker">Primo accesso</p>
    <h1 class="admin-title">Crea l’accesso</h1>
    <p class="admin-lead">Imposta username e password dell’admin subito dopo il primo upload, prima che qualcun altro apra questa pagina.</p>
    <?php if ($error) { ?>
      <p class="admin-flash admin-flash--error" role="alert"><?= admin_h($error) ?></p>
    <?php } ?>
    <form class="admin-form" method="post" autocomplete="off">
      <label class="admin-field">
        <span>Username</span>
        <input type="text" name="username" required maxlength="32" value="<?= admin_h($_POST["username"] ?? "") ?>">
      </label>
      <label class="admin-field">
        <span>Password</span>
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
      </label>
      <label class="admin-field">
        <span>Conferma password</span>
        <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
      </label>
      <div class="admin-actions">
        <button class="admin-btn" type="submit">Crea accesso</button>
      </div>
    </form>
  </div>
</div>
<?php
admin_layout("Crea accesso", ob_get_clean(), ["bare" => true]);
