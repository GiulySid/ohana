<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "dispatch.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

header("X-Robots-Tag: noindex, nofollow");

$https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
$base = trim(str_replace("\\", "/", site_base_path()), "/");
session_name("ohana_reg");
session_set_cookie_params([
    "lifetime" => 0,
    "path" => $base === "" ? "/" : "/" . $base,
    "secure" => $https,
    "httponly" => true,
    "samesite" => "Lax",
]);
session_start();

function reg_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function reg_token_ok() {
    $token = $_POST["csrf"] ?? "";
    return is_string($token) && hash_equals($_SESSION["csrf"] ?? "", $token);
}

function reg_line($value, $max) {
    $value = str_replace(["\r", "\n"], " ", trim((string) $value));
    $value = preg_replace("/\s+/", " ", $value) ?? $value;
    if (function_exists("mb_substr")) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function reg_store_photo($file, $basename) {
    if (!is_array($file) || (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $code = (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        return [null, "La foto non è arrivata. Prova con un file più piccolo."];
    }
    if ((int) ($file["size"] ?? 0) > 16777216) {
        return [null, "La foto supera i 16 MB."];
    }
    $tmp = (string) ($file["tmp_name"] ?? "");
    if ($tmp === "" || !is_uploaded_file($tmp)) {
        return [null, "Impossibile leggere la foto."];
    }
    return data_store_image($tmp, "media/compagnia", $basename);
}

$access = data_registration_access();
$open = $access !== null;
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
    $contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
    $postEmpty = empty($_POST) && empty($_FILES);
    if ($contentLength > 0 && $postEmpty) {
        $error = "La foto è troppo grande per il server. Prova con un'immagine più leggera.";
    } elseif (!reg_token_ok()) {
        $error = "Il modulo è scaduto. Riprova.";
    } elseif (isset($_POST["password"]) && !$unlocked) {
        $fails = (int) ($_SESSION["reg_fails"] ?? 0);
        if ($fails >= 8) {
            $error = "Troppi tentativi. Aspetta un minuto e riprova.";
        } else {
            if ($fails > 2) {
                usleep(400000 * $fails);
            }
            $password = (string) $_POST["password"];
            if (password_verify($password, (string) $access["password_hash"])) {
                session_regenerate_id(true);
                $_SESSION["reg_ok"] = 1;
                $_SESSION["reg_fails"] = 0;
                $_SESSION["csrf"] = bin2hex(random_bytes(32));
                $unlocked = true;
            } else {
                $_SESSION["reg_fails"] = $fails + 1;
                $error = "Password non corretta.";
            }
        }
    } elseif ($unlocked && isset($_POST["nome"])) {
        $honeypot = trim((string) ($_POST["website"] ?? ""));
        $values["nome"] = reg_line($_POST["nome"] ?? "", 120);
        $values["soprannome"] = reg_line($_POST["soprannome"] ?? "", 80);
        $values["pronomi"] = reg_line($_POST["pronomi"] ?? "", 40);
        $values["ruoli"] = array_values(array_intersect(array_keys(PERSON_ROLES), (array) ($_POST["ruoli"] ?? [])));
        $values["attivoDal"] = reg_line($_POST["attivoDal"] ?? "", 12);
        $values["attivoAl"] = reg_line($_POST["attivoAl"] ?? "", 12);
        $bio = trim((string) ($_POST["bio"] ?? ""));
        $values["bio"] = function_exists("mb_substr") ? mb_substr($bio, 0, 5000) : substr($bio, 0, 5000);

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

site_header("registrazione", "Registrazione", "Scheda compagnia Ohana Musical Company.", ["robots" => "noindex, nofollow"]);
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
          <span>Nome</span>
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
                <?= data_h($label) ?>
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
