<?php

require __DIR__ . DIRECTORY_SEPARATOR . "lib.php";
admin_boot();
admin_require_login();

$people = data_people(true);
$shows = data_shows(true);
$media = data_media_items(true);

ob_start();
?>
<p class="admin-kicker">Ohana</p>
<h1 class="admin-title">Cosa vuoi modificare?</h1>
<p class="admin-lead">I testi stanno nei JSON, le foto in /media. Il sito pubblico le legge da lì.</p>
<div class="admin-grid">
  <a class="admin-card" href="sito.php">
    <div>
      <p class="admin-kicker">Sito</p>
      <h2>Testi e contatti</h2>
      <p>Nome, chi siamo, email, social</p>
    </div>
    <span class="admin-count">pagina</span>
  </a>
  <a class="admin-card" href="compagnia.php">
    <div>
      <p class="admin-kicker">Compagnia</p>
      <h2>Persone</h2>
      <p>Attori, registi, tecnici, collaboratori</p>
    </div>
    <span class="admin-count"><?= count($people) ?> profili</span>
  </a>
  <a class="admin-card" href="spettacoli.php">
    <div>
      <p class="admin-kicker">Palco</p>
      <h2>Spettacoli</h2>
      <p>Cartellone, archivio, cast, gallery</p>
    </div>
    <span class="admin-count"><?= count($shows) ?> titoli</span>
  </a>
  <a class="admin-card" href="media.php">
    <div>
      <p class="admin-kicker">Media</p>
      <h2>Foto e video</h2>
      <p>YouTube o album caricati sul server</p>
    </div>
    <span class="admin-count"><?= count($media) ?> elementi</span>
  </a>
</div>
<?php
admin_layout("Dashboard", ob_get_clean(), ["nav" => true, "wide" => true]);
