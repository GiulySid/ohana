<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "dispatch.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "registrazione-lib.php";

reg_boot();

$access = data_registration_access();
$open = $access !== null;
if (!$open) {
    unset($_SESSION["reg_ok"]);
}
$unlocked = $open && !empty($_SESSION["reg_ok"]);
$error = "";
$done = isset($_GET["inviata"]);
$people = data_people(true);
usort($people, static function ($a, $b) {
    return strcasecmp((string) ($a["nome"] ?? ""), (string) ($b["nome"] ?? ""));
});

$selectedId = trim((string) ($_GET["id"] ?? $_POST["id"] ?? ""));
$selected = $selectedId !== "" ? data_person($selectedId, true) : null;
if ($selectedId !== "" && !$selected) {
    $selectedId = "";
}

$values = $selected ? reg_values_from_person($selected) : [
    "nome" => "",
    "soprannome" => "",
    "pronomi" => "",
    "ruoli" => [],
    "attivoDal" => "",
    "attivoAl" => "",
    "bio" => "",
];
$currentThumb = $selected
    ? (data_asset($selected["fotoThumb"] ?? "") ?: data_asset($selected["foto"] ?? ""))
    : "";

if ($open && $_SERVER["REQUEST_METHOD"] === "POST") {
    if (reg_post_too_large()) {
        $error = "La foto è troppo grande per il server. Prova con un'immagine più leggera.";
    } elseif (!reg_token_ok()) {
        $error = "Il modulo è scaduto. Riprova.";
    } elseif (isset($_POST["password"]) && !$unlocked) {
        if (reg_try_unlock($access, $error)) {
            $unlocked = true;
        }
    } elseif ($unlocked && isset($_POST["nome"])) {
        $honeypot = trim((string) ($_POST["website"] ?? ""));
        $selectedId = trim((string) ($_POST["id"] ?? ""));
        $selected = $selectedId !== "" ? data_person($selectedId, true) : null;
        $values = reg_collect_values();
        $currentThumb = $selected
            ? (data_asset($selected["fotoThumb"] ?? "") ?: data_asset($selected["foto"] ?? ""))
            : "";

        if ($honeypot !== "") {
            header("Location: modifica-registrazione?inviata=1");
            exit;
        }
        if (!$selected) {
            $error = "Scegli un profilo dalla lista.";
            $selectedId = "";
        } elseif ($values["nome"] === "") {
            $error = "Il nome è obbligatorio.";
        } else {
            $foto = (string) ($selected["foto"] ?? "");
            $fotoThumb = (string) ($selected["fotoThumb"] ?? "");
            $newImage = null;

            if (!empty($_POST["delete_foto"])) {
                $foto = "";
                $fotoThumb = "";
                $currentThumb = "";
            }

            [$image, $uploadError] = reg_store_photo(
                $_FILES["foto"] ?? null,
                "edit-" . $selected["id"] . "-" . date("Ymd-His") . "-" . bin2hex(random_bytes(2))
            );
            if ($uploadError) {
                $error = $uploadError;
            } elseif ($image) {
                $newImage = $image;
                $foto = (string) ($image["src"] ?? "");
                $fotoThumb = (string) ($image["thumb"] ?? "");
                $currentThumb = data_asset($fotoThumb) ?: data_asset($foto);
            }

            if ($error === "") {
                $person = [
                    "id" => (string) $selected["id"],
                    "nome" => $values["nome"],
                    "soprannome" => $values["soprannome"],
                    "pronomi" => $values["pronomi"],
                    "ruoli" => $values["ruoli"],
                    "attivoDal" => $values["attivoDal"],
                    "attivoAl" => $values["attivoAl"],
                    "bio" => $values["bio"],
                    "foto" => $foto,
                    "fotoThumb" => $fotoThumb,
                    "hidden" => !empty($selected["hidden"]),
                ];
                if (!data_inbox_write($person)) {
                    if ($newImage) {
                        data_delete_rel($newImage["src"] ?? "");
                        data_delete_rel($newImage["thumb"] ?? "");
                    }
                    $error = "Impossibile salvare le modifiche. Riprova tra un momento.";
                } else {
                    header("Location: modifica-registrazione?inviata=1");
                    exit;
                }
            }
        }
    }
}

$picking = $unlocked && !$done && !$selected;
$editing = $unlocked && !$done && $selected;

site_header("modifica-registrazione", "Modifica scheda", "Aggiorna la tua scheda compagnia Ohana Musical Company.", [
    "robots" => "noindex, nofollow",
    "guest" => "shrek",
    "hideNav" => true,
]);
?>
<header class="page-head">
  <p class="kicker">Compagnia</p>
  <h1><?= $done ? "Modifiche ricevute" : ($editing ? "Modifica scheda" : "Aggiorna la scheda") ?></h1>
  <?php if (!$open) { ?>
    <p>La modifica delle schede non è ancora aperta.</p>
  <?php } elseif ($done) { ?>
    <p>Grazie. Abbiamo ricevuto le modifiche: le controlliamo e poi aggiorniamo il profilo sul sito.</p>
  <?php } elseif (!$unlocked) { ?>
    <p>Inserisci la stessa password della registrazione, poi scegli il tuo profilo.</p>
  <?php } elseif ($picking) { ?>
    <p>Scegli il tuo nome dalla lista, poi aggiorna i dati.</p>
  <?php } else { ?>
    <p>Aggiorna i campi che vuoi cambiare. Le pubblichiamo dopo averle lette.</p>
  <?php } ?>
</header>

<?php if ($open && !$done) { ?>
  <section class="section reg-wrap">
    <?php if ($error !== "") { ?>
      <p class="form-note form-note--err" role="alert"><?= data_h($error) ?></p>
    <?php } ?>

    <?php if (!$unlocked) { ?>
      <form class="contact-form reg-form reg-gate" method="post" autocomplete="off">
        <p class="kicker">Accesso</p>
        <input type="hidden" name="csrf" value="<?= data_h(reg_token()) ?>">
        <label>
          <span>Password</span>
          <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn" type="submit">Entra</button>
      </form>
    <?php } elseif ($picking) { ?>
      <?php if (!$people) { ?>
        <p class="form-note">Non ci sono ancora profili da modificare.</p>
      <?php } else { ?>
        <div class="reg-pick" data-reg-pick>
          <label class="reg-pick__search">
            <span>Cerca il tuo nome</span>
            <input type="search" placeholder="Cerca il tuo profilo" data-reg-pick-q autocomplete="off">
          </label>
          <ul class="reg-pick__list">
            <?php foreach ($people as $person) {
                $thumb = data_asset($person["fotoThumb"] ?? "") ?: data_asset($person["foto"] ?? "");
                $label = trim((string) ($person["nome"] ?? ""));
                $nick = trim((string) ($person["soprannome"] ?? ""));
                $hay = strtolower($label . " " . $nick);
                ?>
              <li data-reg-pick-item data-label="<?= data_h($hay) ?>">
                <a class="reg-pick__item" href="modifica-registrazione?id=<?= data_h((string) $person["id"]) ?>">
                  <span class="reg-pick__photo" aria-hidden="true">
                    <?php if ($thumb) { ?>
                      <img src="<?= data_h($thumb) ?>" alt="" loading="lazy">
                    <?php } else { ?>
                      <span><?= data_h(data_initials($label)) ?></span>
                    <?php } ?>
                  </span>
                  <span class="reg-pick__meta">
                    <strong><?= data_h($label) ?></strong>
                    <?php if ($nick !== "") { ?>
                      <small><?= data_h($nick) ?></small>
                    <?php } ?>
                  </span>
                </a>
              </li>
            <?php } ?>
          </ul>
          <p class="reg-help reg-pick__empty" hidden data-reg-pick-empty>Nessun profilo con questo nome.</p>
        </div>
        <script>
          (function () {
            var root = document.querySelector("[data-reg-pick]");
            if (!root) return;
            var input = root.querySelector("[data-reg-pick-q]");
            var empty = root.querySelector("[data-reg-pick-empty]");
            var items = Array.prototype.slice.call(root.querySelectorAll("[data-reg-pick-item]"));
            function sync() {
              var q = (input.value || "").trim().toLowerCase();
              var visible = 0;
              items.forEach(function (item) {
                var ok = !q || (item.getAttribute("data-label") || "").indexOf(q) !== -1;
                item.hidden = !ok;
                if (ok) visible += 1;
              });
              if (empty) empty.hidden = visible > 0;
            }
            input.addEventListener("input", sync);
          })();
        </script>
      <?php } ?>
    <?php } else { ?>
      <p class="reg-back"><a href="modifica-registrazione">← Scegli un altro profilo</a></p>
      <form class="contact-form reg-form" method="post" enctype="multipart/form-data">
        <p class="kicker">La tua scheda</p>
        <input type="hidden" name="csrf" value="<?= data_h(reg_token()) ?>">
        <input type="hidden" name="id" value="<?= data_h((string) $selected["id"]) ?>">
        <label class="hp" for="website">Sito
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </label>
        <label>
          <span>Nome Cognome</span>
          <input type="text" name="nome" required maxlength="120" value="<?= data_h($values["nome"]) ?>">
        </label>
        <label>
          <span>Soprannome</span>
          <input type="text" name="soprannome" maxlength="80" placeholder="Come ti chiamano in compagnia" value="<?= data_h($values["soprannome"]) ?>">
        </label>
        <label>
          <span>Pronomi</span>
          <input type="text" name="pronomi" maxlength="40" placeholder="lei/lui, they/them…" value="<?= data_h($values["pronomi"]) ?>">
        </label>
        <fieldset>
          <span>Ruoli</span>
          <div class="reg-roles">
            <?php foreach (PERSON_ROLES as $key => $label) { ?>
              <label>
                <input type="checkbox" name="ruoli[]" value="<?= data_h($key) ?>"<?= in_array($key, $values["ruoli"], true) ? " checked" : "" ?>>
                <span><?= data_h($label) ?></span>
              </label>
            <?php } ?>
          </div>
        </fieldset>
        <label>
          <span>In compagnia dal (anno)</span>
          <input type="text" name="attivoDal" inputmode="numeric" maxlength="12" placeholder="2019" value="<?= data_h($values["attivoDal"]) ?>">
        </label>
        <label>
          <span>Fino a</span>
          <input type="text" name="attivoAl" inputmode="numeric" maxlength="12" placeholder="Lascia vuoto se sei ancora in compagnia" value="<?= data_h($values["attivoAl"]) ?>">
          <small class="reg-help">Se lo lasci vuoto, risulti ancora in compagnia.</small>
        </label>
        <label>
          <span>Bio / curriculum</span>
          <textarea name="bio" maxlength="5000"><?= data_h($values["bio"]) ?></textarea>
        </label>
        <div class="reg-photo">
          <span>Foto</span>
          <?php if ($currentThumb) { ?>
            <img class="reg-photo__thumb" src="<?= data_h($currentThumb) ?>" alt="">
            <label class="reg-photo__delete">
              <input type="checkbox" name="delete_foto" value="1">
              <span>Elimina foto attuale</span>
            </label>
          <?php } ?>
          <label>
            <span><?= $currentThumb ? "Sostituisci foto" : "Foto" ?></span>
            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="reg-help">JPG o PNG, massimo 16 MB.<?= $currentThumb ? " Se non carichi nulla, resta quella attuale." : " Facoltativa." ?></small>
          </label>
        </div>
        <button class="btn" type="submit">Invia modifiche</button>
      </form>
    <?php } ?>
  </section>
<?php } ?>
<?php
site_footer();
