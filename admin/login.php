<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();

if (!admin_credentials()) {
    header("Location: setup.php");
    exit;
}

if (admin_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = "";
$fails = (int) ($_SESSION["login_fails"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($fails >= 8) {
        $error = "Troppi tentativi. Aspetta un minuto e riprova.";
    } else {
        if ($fails > 2) {
            usleep(400000 * $fails);
        }
        $username = trim((string) ($_POST["username"] ?? ""));
        $password = (string) ($_POST["password"] ?? "");
        $saved = admin_credentials();
        $userOk = hash_equals((string) $saved["username"], $username);
        $passOk = password_verify($password, (string) $saved["password_hash"]);

        if ($userOk && $passOk) {
            session_regenerate_id(true);
            $_SESSION["admin_ok"] = 1;
            $_SESSION["admin_user"] = $saved["username"];
            $_SESSION["login_fails"] = 0;
            header("Location: index.php");
            exit;
        }

        $_SESSION["login_fails"] = $fails + 1;
        $error = "Username o password non corretti.";
    }
}

ob_start();
?>
<div class="admin-auth">
  <div class="admin-auth__card">
    <p class="admin-kicker">Ohana Musical Company</p>
    <h1 class="admin-title">Admin</h1>
    <p class="admin-lead">Area riservata per aggiornare compagnia, spettacoli, media e contatti.</p>
    <?php if ($error) { ?>
      <p class="admin-flash admin-flash--error" role="alert"><?= admin_h($error) ?></p>
    <?php } ?>
    <form class="admin-form" method="post">
      <label class="admin-field">
        <span>Username</span>
        <input type="text" name="username" required autocomplete="username" value="<?= admin_h($_POST["username"] ?? "") ?>">
      </label>
      <label class="admin-field">
        <span>Password</span>
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <div class="admin-actions">
        <button class="admin-btn" type="submit">Entra</button>
      </div>
    </form>
  </div>
</div>
<?php
admin_layout("Accedi", ob_get_clean(), ["bare" => true]);
