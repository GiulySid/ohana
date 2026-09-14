<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$site = data_site();
$to = trim((string) ($site["email"] ?? ""));
$sent = false;
$error = "";
$values = [
    "nome" => "",
    "email" => "",
    "messaggio" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $values["nome"] = trim((string) ($_POST["nome"] ?? ""));
    $values["email"] = trim((string) ($_POST["email"] ?? ""));
    $values["messaggio"] = trim((string) ($_POST["messaggio"] ?? ""));
    $honeypot = trim((string) ($_POST["website"] ?? ""));

    $clean = static function ($value) {
        return str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], "", $value);
    };

    if ($honeypot !== "") {
        $sent = true;
    } elseif ($values["nome"] === "" || $values["email"] === "" || $values["messaggio"] === "") {
        $error = "Compila nome, email e messaggio.";
    } elseif (!filter_var($values["email"], FILTER_VALIDATE_EMAIL)) {
        $error = "Inserisci un’email valida.";
    } elseif ($to === "" || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = "Il form non è ancora collegato a un’email. Scrivici direttamente.";
    } else {
        $subject = "Messaggio dal sito Ohana — " . $clean($values["nome"]);
        $body = "Nome: " . $values["nome"] . "\nEmail: " . $values["email"] . "\n\n" . $values["messaggio"] . "\n";
        $headers = [
            "From: " . $clean($to),
            "Reply-To: " . $clean($values["email"]),
            "Content-Type: text/plain; charset=UTF-8",
            "X-Mailer: PHP/" . PHP_VERSION,
        ];
        $ok = @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, implode("\r\n", $headers));
        if ($ok) {
            $sent = true;
            $values = ["nome" => "", "email" => "", "messaggio" => ""];
        } else {
            $error = "Invio non riuscito. Scrivici a " . $to . ".";
        }
    }
}

site_header("contatti", "Contatti", "Scrivi a Ohana Musical Company.");
?>
<section class="contact-stage">
  <div class="contact-copy">
    <p class="kicker">Un saluto</p>
    <h1>Restiamo<br>in contatto</h1>
    <div class="contact-list">
      <?php if (!empty($site["email"])) { ?>
        <a href="mailto:<?= data_h($site["email"]) ?>"><?= data_h($site["email"]) ?></a>
      <?php } ?>
      <?php if (!empty($site["telefono"])) { ?>
        <a href="tel:<?= data_h(preg_replace("/\s+/", "", $site["telefono"])) ?>"><?= data_h($site["telefono"]) ?></a>
      <?php } ?>
      <?php if (!empty($site["instagram"])) { ?>
        <a href="<?= data_h($site["instagram"]) ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
      <?php } ?>
      <?php if (!empty($site["facebook"])) { ?>
        <a href="<?= data_h($site["facebook"]) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
      <?php } ?>
      <?php if (!empty($site["indirizzo"])) { ?>
        <p><?= data_h($site["indirizzo"]) ?></p>
      <?php } ?>
      <?php if (!empty($site["orariProve"])) { ?>
        <p><?= data_h($site["orariProve"]) ?></p>
      <?php } ?>
    </div>
  </div>

  <form class="contact-form" method="post" novalidate>
    <p class="kicker">Inquire</p>
    <?php if ($sent) { ?>
      <p class="form-note form-note--ok">Grazie. Ti rispondiamo appena possibile.</p>
    <?php } ?>
    <?php if ($error !== "") { ?>
      <p class="form-note form-note--err"><?= data_h($error) ?></p>
    <?php } ?>
    <label class="hp" for="website">Sito
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </label>
    <label>
      <span>Nome</span>
      <input type="text" name="nome" required maxlength="120" value="<?= data_h($values["nome"]) ?>">
    </label>
    <label>
      <span>Email</span>
      <input type="email" name="email" required maxlength="160" value="<?= data_h($values["email"]) ?>">
    </label>
    <label>
      <span>Messaggio</span>
      <textarea name="messaggio" required maxlength="4000"><?= data_h($values["messaggio"]) ?></textarea>
    </label>
    <button class="btn" type="submit">Invia</button>
  </form>
</section>
<?php
site_footer();
