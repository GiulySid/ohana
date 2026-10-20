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
$values = [
    "nome" => "",
    "soprannome" => "",
    "pronomi" => "",
    "ruoli" => [],
    "attivoDal" => "",
    "attivoAl" => "",
    "bio" => "",
];

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
        $values = reg_collect_values();

        if ($honeypot !== "") {
            header("Location: registrazione?inviata=1");
            exit;
        }
        if ($values["nome"] === "") {
            $error = "Il nome è obbligatorio.";
        } else {
            $basename = "reg-" . date("Ymd-His") . "-" . bin2hex(random_bytes(3));
            [$image, $uploadError] = reg_store_photo($_FILES["foto"] ?? null, $basename);
            if ($uploadError) {
                $error = $uploadError;
            } else {
                $person = [
                    "nome" => $values["nome"],
                    "soprannome" => $values["soprannome"],
                    "pronomi" => $values["pronomi"],
                    "ruoli" => $values["ruoli"],
                    "attivoDal" => $values["attivoDal"],
                    "attivoAl" => $values["attivoAl"],
                    "bio" => $values["bio"],
                    "foto" => is_array($image) ? (string) ($image["src"] ?? "") : "",
                    "fotoThumb" => is_array($image) ? (string) ($image["thumb"] ?? "") : "",
                    "hidden" => true,
                ];
                if (!data_inbox_write($person)) {
                    if ($image) {
                        data_delete_rel($image["src"] ?? "");
                        data_delete_rel($image["thumb"] ?? "");
                    }
                    $error = "Impossibile salvare la scheda. Riprova tra un momento.";
                } else {
                    header("Location: registrazione?inviata=1");
                    exit;
                }
            }
        }
    }
}

site_header("registrazione", "Registrazione", "Scheda compagnia Ohana Musical Company.", [
    "robots" => "noindex, nofollow",
    "guest" => "shrek",
    "hideNav" => true,
]);
?>
<header class="page-head">
  <p class="kicker">Compagnia</p>
  <h1><?= $done ? "Scheda ricevuta" : "Registrati" ?></h1>
  <?php if (!$open) { ?>
    <p>La registrazione non è ancora aperta.</p>
  <?php } elseif ($done) { ?>
    <p>Grazie. La scheda è arrivata: la controlliamo e poi la pubblichiamo sul sito.</p>
  <?php } elseif (!$unlocked) { ?>
    <p>Inserisci la password che vi diamo alle prove, poi compila la tua scheda.</p>
  <?php } else { ?>
    <p>Compila la tua scheda. La pubblichiamo dopo averla letta.</p>
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
    <?php } else { ?>
      <form class="contact-form reg-form" method="post" enctype="multipart/form-data">
        <p class="kicker">La tua scheda</p>
        <input type="hidden" name="csrf" value="<?= data_h(reg_token()) ?>">
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
        <label>
          <span>Foto</span>
          <input type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
          <small class="reg-help">JPG o PNG, massimo 16 MB. Facoltativa.</small>
        </label>
        <button class="btn" type="submit">Invia scheda</button>
      </form>
    <?php } ?>
  </section>
<?php } ?>
<?php
site_footer();
