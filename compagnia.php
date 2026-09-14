<?php

require __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "layout.php";

$people = data_people();
$activePeople = array_values(array_filter($people, static function ($person) {
    return data_person_is_active($person);
}));
$alumniPeople = array_values(array_filter($people, static function ($person) {
    return !data_person_is_active($person);
}));

site_header("compagnia", "Compagnia", "Chi ha dato voce, corpo e luce a Ohana.");
?>
<header class="page-head">
  <p class="kicker">La famiglia</p>
  <h1>Compagnia</h1>
  <p>Attori, registi, tecnici e chi ha collaborato anche solo per una stagione. Il palco ricorda tutti.</p>
</header>

<div class="filters" data-filter-group>
  <button class="filter is-on" type="button" data-filter="tutti">Tutti</button>
  <?php foreach (PERSON_ROLES as $key => $label) { ?>
    <button class="filter" type="button" data-filter="<?= data_h($key) ?>"><?= data_h($label) ?></button>
  <?php } ?>
</div>

<?php if (!$people) { ?>
  <p class="empty">La compagnia si sta ancora sistemando in scena. Torna presto.</p>
<?php } else { ?>
  <?php if ($activePeople) { ?>
    <section class="people-section">
      <h2 class="section-title">Compagnia attiva</h2>
      <div class="people-grid">
        <?php foreach ($activePeople as $person) {
            site_person_card($person);
        } ?>
      </div>
    </section>
  <?php } ?>

  <?php if ($alumniPeople) { ?>
    <section class="people-section">
      <h2 class="section-title">Alumni</h2>
      <div class="people-grid">
        <?php foreach ($alumniPeople as $person) {
            site_person_card($person);
        } ?>
      </div>
    </section>
  <?php } ?>
<?php } ?>
<?php
site_footer();
