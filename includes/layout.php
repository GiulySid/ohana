<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "data.php";

function site_nav_items() {
    return [
        "compagnia" => ["label" => "Compagnia", "href" => "compagnia"],
        "archivio" => ["label" => "Archivio", "href" => "archivio"],
        "media" => ["label" => "Media", "href" => "galleria"],
        "contatti" => ["label" => "Contatti", "href" => "contatti"],
    ];
}

function site_show_guest($show) {
    $title = is_array($show) ? (string) ($show["titolo"] ?? "") : (string) $show;
    return preg_match("/shrek/i", $title) ? "shrek" : "";
}

function site_brand($guest = "") {
    $dir = "img/brand/";
    if ($guest === "shrek") {
        return [
            "mark" => $dir . "ohana-shrek-mark.png",
            "lockup" => $dir . "ohana-shrek-lockup-on-dark.png",
        ];
    }
    return [
        "mark" => $dir . "ohana-mark-on-dark.png",
        "lockup" => $dir . "ohana-lockup-on-dark.png",
        "lockupLight" => $dir . "ohana-lockup-on-light.png",
    ];
}

function site_guest_logo($guest) {
    return site_brand($guest)["mark"];
}

function site_show_title_mark($show) {
    $title = is_array($show) ? (string) ($show["titolo"] ?? "") : (string) $show;
    return preg_match("/greatest\s*show/i", $title) ? "img/brand/greatest-show-wordmark.png" : "";
}

function site_header($current, $title, $description = "", $opts = []) {
    $site = data_site();
    $name = $site["nome"] ?: "Ohana Musical Company";
    $fullTitle = $current === "home" ? $name : ($title . " — " . $name);
    $description = $description !== "" ? $description : ($site["tagline"] ?: $name);
    $nav = site_nav_items();
    $guest = trim((string) ($opts["guest"] ?? ""));
    $brand = site_brand($guest);
    $logo = !empty($opts["logo"]) ? (string) $opts["logo"] : $brand["mark"];
    $lockup = !empty($opts["lockup"]) ? (string) $opts["lockup"] : $brand["lockup"];
    $GLOBALS["site_guest"] = $guest;
    $root = dirname(__DIR__);
    $GLOBALS["site_css_v"] = (int) @filemtime($root . DIRECTORY_SEPARATOR . "css" . DIRECTORY_SEPARATOR . "site.css");
    $GLOBALS["site_js_v"] = (int) @filemtime($root . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "site.js");

    header("Content-Type: text/html; charset=utf-8");
    ?>
<!DOCTYPE html>
<html lang="it"<?= $current === "home" ? "" : ' class="is-booting"' ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= data_h($fullTitle) ?></title>
  <meta name="description" content="<?= data_h($description) ?>">
  <link rel="icon" href="img/brand/favicon.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/fonts.css">
  <link rel="stylesheet" href="css/site.css?v=<?= (int) ($GLOBALS["site_css_v"] ?? 0) ?>">
  <noscript><style>.page-veil{display:none!important}body[data-page="home"] .site-header{opacity:1!important;transform:none!important;pointer-events:auto!important}.curtain{display:none!important}.home-stage{height:100vh!important}</style></noscript>
</head>
<body data-page="<?= data_h($current) ?>"<?= $guest !== "" ? ' data-guest="' . data_h($guest) . '"' : "" ?>>
  <div class="scene" aria-hidden="true"></div>
  <div class="veil" aria-hidden="true"></div>
  <div class="page-veil" aria-hidden="true">
    <div class="page-veil__blur"></div>
    <div class="page-veil__dim"></div>
    <div class="page-veil__mark">
      <img src="<?= data_h($lockup) ?>" alt="">
    </div>
  </div>

  <header class="site-header">
    <a class="brand" href="./" data-nav>
      <img src="<?= data_h($logo) ?>" alt="<?= data_h($name) ?>" width="64" height="64">
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
    $guest = (string) ($GLOBALS["site_guest"] ?? "");
    $footerLogo = $guest === "shrek"
        ? "img/brand/ohana-shrek-lockup-on-dark.png"
        : "img/brand/ohana-white.png";
    ?>
  </main>
  <footer class="site-footer site-footer--velvet">
   <div class="footer-content">
    <div class="footer-brand">
      <img class="footer-mark" src="<?= data_h($footerLogo) ?>" alt="<?= data_h($name) ?>">
      <div class="footer-meta">
        <?php if (!empty($site["orariProve"])) { ?>
          <p><?= data_h($site["orariProve"]) ?></p>
        <?php } ?>
        <?php if (!empty($site["indirizzo"])) { ?>
          <p><?= data_h($site["indirizzo"]) ?></p>
        <?php } ?>
      </div>
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

  </div> 
  </footer>
  <script src="js/site.js?v=<?= (int) ($GLOBALS["site_js_v"] ?? 0) ?>" defer></script>
</body>
</html>
    <?php
}

function site_person_card($person) {
    $thumb = data_asset($person["fotoThumb"] ?? "") ?: data_asset($person["foto"] ?? "");
    $active = data_person_is_active($person);
    $roles = implode(",", $person["ruoli"] ?? []);
    ?>
    <a class="person-card" href="persona?id=<?= data_h($person["id"]) ?>" data-nav data-roles="<?= data_h($roles) ?>" data-active="<?= $active ? "1" : "0" ?>">
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
    if (($show["stato"] ?? "") === "prossimo" && $count === 0) {
        $dates = trim((string) ($show["stagione"] ?? "")) ?: "Prossimamente";
    } else {
        $dates = $count === 1 ? "1 data" : ($count . " date");
    }
    $guest = site_show_guest($show);
    ?>
    <a class="show-card<?= $compact ? " show-card--compact" : "" ?><?= $guest !== "" ? " show-card--guest" : "" ?>" href="spettacolo?id=<?= data_h($show["id"]) ?>" data-nav>
      <div class="show-card__poster">
        <?php if ($poster) { ?>
          <img src="<?= data_h($poster) ?>" alt="" loading="lazy">
        <?php } else { ?>
          <span><?= data_h(data_first_letter($show["titolo"] ?? "")) ?></span>
        <?php } ?>
      </div>
      <div class="show-card__body">
        <?php $titleMark = site_show_title_mark($show); ?>
        <h3><?php if ($titleMark) { ?><img class="title-mark title-mark--card" src="<?= data_h($titleMark) ?>" alt="<?= data_h($show["titolo"] ?? "") ?>"><?php } else { ?><?= data_h($show["titolo"] ?? "") ?><?php } ?></h3>
        <p><?= data_h($dates) ?></p>
      </div>
    </a>
    <?php
}
