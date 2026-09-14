<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "data.php";

function site_nav_items() {
    return [
        "compagnia" => ["label" => "Compagnia", "href" => "compagnia.php"],
        "archivio" => ["label" => "Archivio", "href" => "archivio.php"],
        "media" => ["label" => "Media", "href" => "media.php"],
        "contatti" => ["label" => "Contatti", "href" => "contatti.php"],
    ];
}

function site_header($current, $title, $description = "") {
    $site = data_site();
    $name = $site["nome"] ?: "Ohana Musical Company";
    $fullTitle = $current === "home" ? $name : ($title . " — " . $name);
    $description = $description !== "" ? $description : ($site["tagline"] ?: $name);
    $nav = site_nav_items();

    header("Content-Type: text/html; charset=utf-8");
    ?>
<!DOCTYPE html>
<html lang="it"<?= $current === "home" ? "" : ' class="is-booting"' ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= data_h($fullTitle) ?></title>
  <meta name="description" content="<?= data_h($description) ?>">
  <link rel="icon" href="favicon.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/site.css">
  <noscript><style>.page-veil{display:none!important}body[data-page="home"] .site-header{opacity:1!important;transform:none!important;pointer-events:auto!important}.curtain{display:none!important}.home-stage{height:100vh!important}</style></noscript>
</head>
<body data-page="<?= data_h($current) ?>">
  <div class="scene" aria-hidden="true">
    <img src="gs.jpeg" alt="" decoding="async">
  </div>
  <div class="veil" aria-hidden="true"></div>
  <div class="page-veil" aria-hidden="true">
    <div class="page-veil__blur"></div>
    <div class="page-veil__dim"></div>
    <div class="page-veil__mark">
      <img src="logo.jpg" alt="">
    </div>
  </div>

  <header class="site-header">
    <a class="brand" href="index.php" data-nav>
      <img src="logo.jpg" alt="<?= data_h($name) ?>" width="64" height="64">
      <span><?= data_h($name) ?></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
    <nav id="site-nav" class="site-nav">
      <?php foreach ($nav as $key => $item) { ?>
        <a href="<?= data_h($item["href"]) ?>" data-nav<?= $current === $key ? ' aria-current="page"' : "" ?>><?= data_h($item["label"]) ?></a>
      <?php } ?>
    </nav>
  </header>
  <main class="site-main" id="main">
    <?php
}

function site_footer() {
    $site = data_site();
    $name = $site["nome"] ?: "Ohana Musical Company";
    ?>
  </main>
  <footer class="site-footer">
    <div>
      <p class="footer-mark"><?= data_h($name) ?></p>
      <?php if (!empty($site["orariProve"])) { ?>
        <p><?= data_h($site["orariProve"]) ?></p>
      <?php } ?>
      <?php if (!empty($site["indirizzo"])) { ?>
        <p><?= data_h($site["indirizzo"]) ?></p>
      <?php } ?>
    </div>
    <div class="footer-links">
      <?php if (!empty($site["email"])) { ?>
        <a href="mailto:<?= data_h($site["email"]) ?>"><?= data_h($site["email"]) ?></a>
      <?php } ?>
      <?php if (!empty($site["instagram"])) { ?>
        <a href="<?= data_h($site["instagram"]) ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
      <?php } ?>
      <?php if (!empty($site["facebook"])) { ?>
        <a href="<?= data_h($site["facebook"]) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
      <?php } ?>
    </div>
    <p class="footer-copy">&copy; <?= date("Y") ?> <?= data_h($name) ?></p>
  </footer>
  <script src="js/site.js" defer></script>
</body>
</html>
    <?php
}

function site_person_card($person) {
    $thumb = data_asset($person["fotoThumb"] ?? "") ?: data_asset($person["foto"] ?? "");
    $active = data_person_is_active($person);
    $roles = implode(",", $person["ruoli"] ?? []);
    ?>
    <a class="person-card" href="persona.php?id=<?= data_h($person["id"]) ?>" data-nav data-roles="<?= data_h($roles) ?>" data-active="<?= $active ? "1" : "0" ?>">
      <div class="person-card__photo">
        <?php if ($thumb) { ?>
          <img src="<?= data_h($thumb) ?>" alt="" loading="lazy">
        <?php } else { ?>
          <span><?= data_h(data_initials($person["nome"] ?? "")) ?></span>
        <?php } ?>
      </div>
      <div class="person-card__meta">
        <h3><?= data_h($person["nome"] ?? "") ?></h3>
        <p><?= data_h(implode(" · ", data_role_labels($person["ruoli"] ?? []))) ?></p>
      </div>
    </a>
    <?php
}

function site_home_photos() {
    $photos = [];
    $add = static function ($src) use (&$photos) {
        $src = trim((string) $src);
        if ($src === "" || in_array($src, $photos, true)) {
            return;
        }
        $photos[] = $src;
    };

    if (is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . "gs.jpeg")) {
        $add("gs.jpeg");
    }

    foreach (data_shows() as $show) {
        $add(data_asset($show["locandina"] ?? "") ?: data_asset($show["locandinaThumb"] ?? ""));
        foreach ($show["gallery"] ?? [] as $file) {
            $add(data_asset($file));
        }
    }
    foreach (data_media_items() as $item) {
        $add(data_asset($item["cover"] ?? ""));
        foreach ($item["files"] ?? [] as $file) {
            $add(data_asset($file));
        }
    }
    foreach (data_people() as $person) {
        $add(data_asset($person["foto"] ?? "") ?: data_asset($person["fotoThumb"] ?? ""));
    }

    if (!$photos && is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . "gs.jpeg")) {
        $photos[] = "gs.jpeg";
    }

    return $photos;
}

function site_about_photo() {
    $rel = "media/gratest_show/gruppo/1.jpeg";
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $rel);
    return is_file($abs) ? $rel : "";
}

function site_media_video($basename) {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "media";
    if (!is_dir($dir)) {
        return "";
    }
    $pattern = "/^" . preg_quote($basename, "/") . "\\.(mov|mp4|webm)$/i";
    foreach (scandir($dir) ?: [] as $name) {
        if (preg_match($pattern, $name)) {
            return "media/" . $name;
        }
    }
    return "";
}

function site_showreel_src() {
    return site_media_video("showreel");
}

function site_curtain_src() {
    return site_media_video("curtain");
}

function site_show_card($show, $compact = false) {
    $poster = data_asset($show["locandinaThumb"] ?? "") ?: data_asset($show["locandina"] ?? "");
    $count = count($show["repliche"] ?? []);
    $dates = $count === 1 ? "1 data" : ($count . " date");
    ?>
    <a class="show-card<?= $compact ? " show-card--compact" : "" ?>" href="spettacolo.php?id=<?= data_h($show["id"]) ?>" data-nav>
      <div class="show-card__poster">
        <?php if ($poster) { ?>
          <img src="<?= data_h($poster) ?>" alt="" loading="lazy">
        <?php } else { ?>
          <span><?= data_h(data_first_letter($show["titolo"] ?? "")) ?></span>
        <?php } ?>
      </div>
      <div class="show-card__body">
        <h3><?= data_h($show["titolo"] ?? "") ?></h3>
        <p><?= data_h($dates) ?></p>
      </div>
    </a>
    <?php
}
