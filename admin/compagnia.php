<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    admin_csrf_check();
    $id = (string) ($_POST["id"] ?? "");
    $action = (string) ($_POST["action"] ?? "");
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

ob_start();
?>
<a class="admin-back" href="index.php">← Contenuti</a>
<div class="admin-toolbar">
  <div>
    <p class="admin-kicker">Compagnia</p>
    <h1 class="admin-title">Persone</h1>
  </div>
  <a class="admin-btn" href="persona.php">Aggiungi</a>
</div>
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
