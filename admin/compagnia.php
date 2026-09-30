<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_csrf_check();
    $action = (string) ($_POST["action"] ?? "");

    if ($action === "import") {
        $result = data_inbox_import();
        if (!$result["ok"]) {
            admin_flash("error", $result["error"]);
        } elseif ((int) $result["added"] === 0 && (int) $result["invalid"] > 0) {
            admin_flash("error", "Nessuna scheda valida in inbox.");
        } elseif ((int) $result["added"] === 0) {
            admin_flash("success", "Nessuna persona nuova da importare.");
        } else {
            $added = (int) $result["added"];
            $message = $added === 1
                ? "Importata 1 persona. Resta nascosta finché non premi Mostra."
                : "Importate {$added} persone. Restano nascoste finché non premi Mostra.";
            if ((int) $result["invalid"] > 0) {
                $message .= " " . (int) $result["invalid"] . " file non validi sono rimasti in inbox.";
            }
            if ((int) $result["left"] > (int) $result["invalid"]) {
                $message .= " Qualche file non è stato cancellato: un secondo Importa non le duplica.";
            }
            admin_flash("success", $message);
        }
        header("Location: compagnia.php");
        exit;
    }

    if ($action === "reg_password") {
        $password = (string) ($_POST["password"] ?? "");
        $confirm = (string) ($_POST["confirm"] ?? "");
        if (strlen($password) < 8) {
            admin_flash("error", "Usa una password di almeno 8 caratteri.");
        } elseif (!hash_equals($password, $confirm)) {
            admin_flash("error", "Le due password non coincidono.");
        } elseif (!data_registration_set_password($password)) {
            admin_flash("error", "Impossibile salvare la password. Controlla i permessi della cartella data/.");
        } else {
            admin_flash("success", "Password della registrazione aggiornata.");
        }
        header("Location: compagnia.php");
        exit;
    }

    if ($action === "reg_close") {
        if (!data_registration_close()) {
            admin_flash("error", "Impossibile chiudere la registrazione. Controlla i permessi della cartella data/.");
        } else {
            admin_flash("success", "Registrazione chiusa. La pagina /registrazione non accetta più schede.");
        }
        header("Location: compagnia.php");
        exit;
    }

    $id = (string) ($_POST["id"] ?? "");
    $people = data_people(true);
    $index = null;
    foreach ($people as $i => $person) {
        if (($person["id"] ?? "") === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        admin_flash("error", "Persona non trovata.");
        header("Location: compagnia.php");
        exit;
    }

    if ($action === "delete") {
        $used = admin_people_used_in($id);
        if ($used) {
            admin_flash("error", "Non puoi cancellare chi è ancora nel cast di: " . implode(", ", $used) . ".");
        } else {
            data_delete_rel($people[$index]["foto"] ?? "");
            data_delete_rel($people[$index]["fotoThumb"] ?? "");
            array_splice($people, $index, 1);
            data_write("compagnia", ["people" => array_values($people)]);
            admin_flash("success", "Profilo eliminato.");
        }
    } elseif ($action === "hide" || $action === "show") {
        $people[$index]["hidden"] = $action === "hide";
        data_write("compagnia", ["people" => array_values($people)]);
        admin_flash("success", $action === "hide" ? "Nascosto dal sito." : "Di nuovo visibile.");
    }

    header("Location: compagnia.php");
    exit;
}

$people = data_people(true);
$inboxCount = count(data_inbox_files());
$registrationOpen = data_registration_access() !== null;

ob_start();
?>
<a class="admin-back" href="index.php">← Contenuti</a>
<div class="admin-toolbar">
  <div>
    <p class="admin-kicker">Compagnia</p>
    <h1 class="admin-title">Persone</h1>
  </div>
  <div class="admin-toolbar__actions">
    <?php if ($inboxCount > 0) { ?>
      <form method="post">
        <?= admin_csrf_field() ?>
        <input type="hidden" name="action" value="import">
        <button class="admin-btn" type="submit" data-confirm="Importare le schede in inbox? Restano nascoste sul sito.">Importa <?= (int) $inboxCount ?></button>
      </form>
    <?php } ?>
    <a class="admin-btn" href="persona.php">Aggiungi</a>
  </div>
</div>
<section class="admin-reg">
  <p>Link per il QR: <a href="../registrazione" target="_blank" rel="noopener">/registrazione</a><?= $registrationOpen ? "" : ". La pagina è chiusa finché non imposti una password." ?></p>
  <form method="post">
    <?= admin_csrf_field() ?>
    <input type="hidden" name="action" value="reg_password">
    <div class="admin-row">
      <label class="admin-field">
        <span><?= $registrationOpen ? "Nuova password registrazione" : "Password registrazione" ?></span>
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
      </label>
      <label class="admin-field">
        <span>Conferma</span>
        <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
      </label>
    </div>
    <div class="admin-actions">
      <button class="admin-btn-ghost" type="submit"><?= $registrationOpen ? "Cambia password" : "Attiva registrazione" ?></button>
    </div>
  </form>
  <?php if ($registrationOpen) { ?>
    <form method="post" data-confirm="Chiudere la registrazione? La pagina non accetterà più schede finché non imposti una nuova password.">
      <?= admin_csrf_field() ?>
      <input type="hidden" name="action" value="reg_close">
      <button class="admin-btn-ghost" type="submit">Chiudi registrazione</button>
    </form>
  <?php } ?>
</section>
<?php if (!$people) { ?>
  <p class="admin-empty">Nessun profilo. Aggiungi la prima persona.</p>
<?php } ?>
<?php foreach ($people as $person) { ?>
  <article class="admin-item">
    <div>
      <h2>
        <?= admin_h($person["nome"] ?? $person["id"]) ?>
        <?php if (!empty($person["hidden"])) { ?><span class="admin-badge">Nascosto</span><?php } ?>
        <?php if (!data_person_is_active($person)) { ?><span class="admin-badge">Passato</span><?php } ?>
      </h2>
      <p><?= admin_h(implode(" · ", data_role_labels($person["ruoli"] ?? []))) ?> · <?= admin_h(data_person_period($person) ?: "periodo non indicato") ?></p>
    </div>
    <div class="admin-item__actions">
      <a class="admin-btn-ghost" href="persona.php?id=<?= admin_h($person["id"]) ?>">Modifica</a>
      <form method="post">
        <?= admin_csrf_field() ?>
        <input type="hidden" name="id" value="<?= admin_h($person["id"]) ?>">
        <input type="hidden" name="action" value="<?= !empty($person["hidden"]) ? "show" : "hide" ?>">
        <button class="admin-btn-ghost" type="submit"><?= !empty($person["hidden"]) ? "Mostra" : "Nascondi" ?></button>
      </form>
      <form method="post" data-confirm="Eliminare questo profilo?">
        <?= admin_csrf_field() ?>
        <input type="hidden" name="id" value="<?= admin_h($person["id"]) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="admin-btn-ghost" type="submit">Elimina</button>
      </form>
    </div>
  </article>
<?php } ?>
<?php
admin_layout("Compagnia", ob_get_clean(), ["nav" => true, "wide" => true]);
