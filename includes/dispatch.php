<?php

function site_public_pages() {
    return [
        "compagnia" => "compagnia.php",
        "archivio" => "archivio.php",
        "galleria" => "media.php",
        "contatti" => "contatti.php",
        "persona" => "persona.php",
        "spettacolo" => "spettacolo.php",
    ];
}

function site_request_page() {
    $uri = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);
    $page = trim(rawurldecode((string) $uri), "/");
    if (str_ends_with($page, ".php")) {
        $page = substr($page, 0, -4);
    }
    return $page;
}

function site_dispatch() {
    $page = site_request_page();
    if ($page === "" || $page === "index") {
        return;
    }

    $pages = site_public_pages();
    if (isset($pages[$page])) {
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . $pages[$page];
        exit;
    }

    http_response_code(404);
    require_once __DIR__ . DIRECTORY_SEPARATOR . "layout.php";
    site_header("home", "Non trovato");
    echo '<header class="page-head"><h1>Pagina non trovata</h1><p>Questo indirizzo non è sul sito.</p><a class="btn" href="./" data-nav>Torna alla home</a></header>';
    site_footer();
    exit;
}
